<?php
// partial/get_users.php
require_once 'db_conn.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$role = $_SESSION['role'] ?? '';
$isAdmin = ($role === 'admin');
$isSuperAdmin = ($role === 'super_admin');

if (!isset($_SESSION['user_id']) || !in_array($role, ['admin', 'super_admin'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

function table_exists(mysqli $conn, string $table): bool
{
    $tableEsc = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$tableEsc}'");
    if (!$res) return false;
    $exists = $res->num_rows > 0;
    $res->free();
    return $exists;
}

function get_table_columns(mysqli $conn, string $table): array
{
    $cols = [];
    $tableEsc = $conn->real_escape_string($table);
    $res = $conn->query("DESCRIBE `{$tableEsc}`");
    if (!$res) return $cols;

    while ($row = $res->fetch_assoc()) {
        $cols[] = $row['Field'];
    }

    $res->free();
    return $cols;
}

function pick_first_existing(array $candidates, array $available): ?string
{
    foreach ($candidates as $c) {
        if (in_array($c, $available, true)) {
            return $c;
        }
    }
    return null;
}

/*
|--------------------------------------------------------------------------
| Detail endpoint: ?user_id=123
|--------------------------------------------------------------------------
*/
if (isset($_GET['user_id'])) {
    $userId = (int)($_GET['user_id'] ?? 0);

    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid user_id']);
        exit;
    }

    $userCols = get_table_columns($conn, 'users');

    $desiredUserCols = [
        'id',
        'u_uid',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'address',
        'mobile',
        'phone',
        'birthday',
        'birthdate',
        'date_of_birth',
        'gender',
        'created_at',
        'updated_at',
        'profile_image',
        'role',
        'is_active',
        'is_deleted'
    ];

    $selectCols = [];
    foreach ($desiredUserCols as $c) {
        if (in_array($c, $userCols, true)) {
            $selectCols[] = "`$c`";
        }
    }

    if (!in_array('id', $userCols, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'users table is missing id column']);
        exit;
    }

    if (empty($selectCols)) {
        $selectCols = ['`id`'];
    }

    $hasIsDeleted = in_array('is_deleted', $userCols, true);

    $sqlUser = "SELECT " . implode(', ', $selectCols) . "
                FROM users
                WHERE id = ?" . ($hasIsDeleted ? " AND is_deleted = 0" : "") . "
                LIMIT 1";

    $stmt = $conn->prepare($sqlUser);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $userRes = $stmt->get_result();
    $user = $userRes->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Exam attempts
    |--------------------------------------------------------------------------
    */
    $attempts = [];
    $attemptsSummary = null;

    if (table_exists($conn, 'user_exam_attempts')) {
        $aCols = get_table_columns($conn, 'user_exam_attempts');
        $examCols = table_exists($conn, 'exams') ? get_table_columns($conn, 'exams') : [];

        $scoreCol = pick_first_existing(['score', 'exam_score', 'points'], $aCols);
        $correctCol = pick_first_existing(['total_correct', 'correct', 'correct_count'], $aCols);
        $answeredCol = pick_first_existing(['total_answered', 'answered', 'answered_count', 'total_questions_answered'], $aCols);
        $examIdCol = pick_first_existing(['exam_id', 'quiz_id', 'test_id'], $aCols);
        $timeCol = pick_first_existing(['submitted_at', 'attempted_at', 'finished_at', 'updated_at', 'created_at', 'started_at'], $aCols);

        $examTitleCol = pick_first_existing(['title', 'exam_title', 'name'], $examCols);

        $attemptSelect = [];
        if ($examIdCol) $attemptSelect[] = "uea.`$examIdCol` AS exam_id";
        if ($examTitleCol && $examIdCol && !empty($examCols)) $attemptSelect[] = "e.`$examTitleCol` AS exam_title";
        if ($scoreCol) $attemptSelect[] = "uea.`$scoreCol` AS score";
        if ($correctCol) $attemptSelect[] = "uea.`$correctCol` AS total_correct";
        if ($answeredCol) $attemptSelect[] = "uea.`$answeredCol` AS total_answered";
        if ($timeCol) $attemptSelect[] = "uea.`$timeCol` AS attempted_at";

        if (!empty($attemptSelect)) {
            $attemptSql = "SELECT " . implode(', ', $attemptSelect) . "
                           FROM user_exam_attempts uea " .
                           (($examIdCol && $examTitleCol && !empty($examCols))
                               ? "LEFT JOIN exams e ON e.id = uea.`$examIdCol` "
                               : "") . "
                           WHERE uea.user_id = ? " .
                           ($timeCol ? "ORDER BY uea.`$timeCol` DESC " : "ORDER BY uea.user_id DESC ") . "
                           LIMIT 10";

            $stmtA = $conn->prepare($attemptSql);
            $stmtA->bind_param('i', $userId);
            $stmtA->execute();
            $resA = $stmtA->get_result();

            while ($row = $resA->fetch_assoc()) {
                $attempts[] = $row;
            }

            $stmtA->close();
        }

        $agg = ["COUNT(*) AS total_attempts"];
        if ($scoreCol) {
            $agg[] = "AVG(`$scoreCol`) AS avg_score";
            $agg[] = "MAX(`$scoreCol`) AS best_score";
        }
        if ($correctCol) $agg[] = "SUM(`$correctCol`) AS sum_total_correct";
        if ($answeredCol) $agg[] = "SUM(`$answeredCol`) AS sum_total_answered";

        $sumSql = "SELECT " . implode(', ', $agg) . "
                   FROM user_exam_attempts
                   WHERE user_id = ?";

        $stmtAS = $conn->prepare($sumSql);
        $stmtAS->bind_param('i', $userId);
        $stmtAS->execute();
        $attemptsSummary = $stmtAS->get_result()->fetch_assoc();
        $stmtAS->close();
    }

    /*
    |--------------------------------------------------------------------------
    | User progress
    |--------------------------------------------------------------------------
    */
    $progressRows = [];
    $progressSummary = null;

    if (
        table_exists($conn, 'user_progress') &&
        table_exists($conn, 'study_material_files') &&
        table_exists($conn, 'study_materials')
    ) {
        $pCols = get_table_columns($conn, 'user_progress');
        $fCols = get_table_columns($conn, 'study_material_files');
        $mCols = get_table_columns($conn, 'study_materials');

        $progressCol = pick_first_existing(['progress', 'progress_percent', 'percentage', 'percent'], $pCols);
        $completedCol = pick_first_existing(['is_completed', 'completed'], $pCols);
        $statusCol = pick_first_existing(['status'], $pCols);
        $timeColP = pick_first_existing(['updated_at', 'created_at'], $pCols);

        $fileIdCol = pick_first_existing(['file_id'], $pCols);
        $fileTitleCol = pick_first_existing(['title', 'name'], $fCols);
        $materialTitleCol = pick_first_existing(['title', 'name'], $mCols);
        $materialCategoryCol = pick_first_existing(['category'], $mCols);
        $materialModuleCol = pick_first_existing(['module'], $mCols);

        $pSelect = [];
        if ($materialTitleCol) $pSelect[] = "sm.`$materialTitleCol` AS material_title";
        if ($materialCategoryCol) $pSelect[] = "sm.`$materialCategoryCol` AS material_category";
        if ($materialModuleCol) $pSelect[] = "sm.`$materialModuleCol` AS material_module";
        if ($fileTitleCol) $pSelect[] = "smf.`$fileTitleCol` AS file_title";
        if ($progressCol) $pSelect[] = "up.`$progressCol` AS progress";
        if ($completedCol) $pSelect[] = "up.`$completedCol` AS is_completed";
        if ($statusCol) $pSelect[] = "up.`$statusCol` AS status";
        if ($timeColP) $pSelect[] = "up.`$timeColP` AS updated_at";

        if (!empty($pSelect) && $fileIdCol) {
            $pSql = "SELECT " . implode(', ', $pSelect) . "
                     FROM user_progress up
                     INNER JOIN study_material_files smf
                        ON smf.id = up.`$fileIdCol`
                     INNER JOIN study_materials sm
                        ON sm.id = smf.material_id
                     WHERE up.user_id = ? " .
                     ($timeColP ? "ORDER BY up.`$timeColP` DESC " : "ORDER BY up.user_id DESC ") . "
                     LIMIT 10";

            $stmtP = $conn->prepare($pSql);
            $stmtP->bind_param('i', $userId);
            $stmtP->execute();
            $resP = $stmtP->get_result();

            while ($row = $resP->fetch_assoc()) {
                $progressRows[] = $row;
            }

            $stmtP->close();
        }

        $aggP = ["COUNT(*) AS total_records"];
        if ($progressCol) $aggP[] = "AVG(up.`$progressCol`) AS avg_progress";
        if ($completedCol) {
            $aggP[] = "SUM(CASE WHEN up.`$completedCol` = 1 THEN 1 ELSE 0 END) AS completed_count";
        } elseif ($statusCol) {
            $aggP[] = "SUM(CASE WHEN LOWER(up.`$statusCol`) IN ('completed','done','finished') THEN 1 ELSE 0 END) AS completed_count";
        }
        if ($timeColP) $aggP[] = "MAX(up.`$timeColP`) AS last_updated";

        $sumPSql = "SELECT " . implode(', ', $aggP) . "
                    FROM user_progress up
                    WHERE up.user_id = ?";

        $stmtPS = $conn->prepare($sumPSql);
        $stmtPS->bind_param('i', $userId);
        $stmtPS->execute();
        $progressSummary = $stmtPS->get_result()->fetch_assoc();
        $stmtPS->close();
    }

    echo json_encode([
        'user' => $user,
        'exam_attempts' => $attempts,
        'exam_attempts_summary' => $attemptsSummary,
        'progress_rows' => $progressRows,
        'progress_summary' => $progressSummary
    ]);

    $conn->close();
    exit;
}

/*
|--------------------------------------------------------------------------
| List endpoint: ?page=1&limit=10&search=foo
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| List endpoint
|--------------------------------------------------------------------------
*/
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = (int)($_GET['limit'] ?? 10);
if ($limit <= 0) $limit = 10;
if ($limit > 100) $limit = 100;

$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$scope = trim($_GET['scope'] ?? 'all'); // all | users | admins

$userCols = get_table_columns($conn, 'users');
$hasIsDeleted = in_array('is_deleted', $userCols, true);
$hasRole = in_array('role', $userCols, true);
$hasIsActive = in_array('is_active', $userCols, true);

$whereParts = [];
$params = [];
$types = '';

if ($hasIsDeleted) {
    $whereParts[] = "is_deleted = 0";
}

if ($hasRole) {
    if ($scope === 'admins') {
        $whereParts[] = "role IN ('admin')";
    } elseif ($scope === 'users') {
        $whereParts[] = "role NOT IN ('admin', 'super_admin')";
    } else {
        if ($isAdmin) {
            $whereParts[] = "role NOT IN ('admin', 'super_admin')";
        }
    }
}

if ($search !== '') {
    $searchableParts = [];

    if (in_array('u_uid', $userCols, true)) $searchableParts[] = "u_uid LIKE ?";
    if (in_array('full_name', $userCols, true)) $searchableParts[] = "full_name LIKE ?";
    if (in_array('email', $userCols, true)) $searchableParts[] = "email LIKE ?";
    if ($hasRole) $searchableParts[] = "role LIKE ?";

    if (!empty($searchableParts)) {
        $whereParts[] = "(" . implode(' OR ', $searchableParts) . ")";
        $searchTerm = "%{$search}%";

        foreach ($searchableParts as $_) {
            $params[] = $searchTerm;
            $types .= 's';
        }
    }
}

$where = !empty($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';

$countSql = "SELECT COUNT(*) FROM users $where";
$countStmt = $conn->prepare($countSql);
if ($countStmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare count query']);
    exit;
}
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_row()[0] ?? 0;
$countStmt->close();

$selectList = [
    "id",
    "u_uid",
    "full_name",
    "email",
    "created_at",
    "profile_image"
];

if ($hasRole) $selectList[] = "role";
if ($hasIsActive) $selectList[] = "is_active";

$sql = "SELECT " . implode(', ', $selectList) . "
        FROM users
        $where
        ORDER BY id DESC";

$bindParams = $params;
$bindTypes = $types;

if ($scope !== 'admins') {
    $sql .= " LIMIT ? OFFSET ?";
    $bindParams[] = $limit;
    $bindParams[] = $offset;
    $bindTypes .= 'ii';
}

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare users query']);
    exit;
}

if (!empty($bindParams)) {
    $stmt->bind_param($bindTypes, ...$bindParams);
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    if (!isset($row['role'])) {
        $row['role'] = 'user';
    }

    if (!isset($row['is_active'])) {
        $row['is_active'] = 1;
    }

    $users[] = $row;
}
$stmt->close();

echo json_encode([
    'users' => $users,
    'total' => (int)$total,
    'page' => $scope === 'admins' ? 1 : $page,
    'limit' => $scope === 'admins' ? count($users) : $limit,
    'scope' => $scope
]);

$conn->close();