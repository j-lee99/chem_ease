<?php
// partial/get_users.php
require_once 'db_conn.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

// Only allow admins
if (!isset($_SESSION['user_id'])) {
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
        if (in_array($c, $available, true)) return $c;
    }
    return null;
}

// ------------------------
// Detail endpoint: ?user_id=123
// ------------------------
if (isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid user_id']);
        exit;
    }

    $userCols = get_table_columns($conn, 'users');

    // Select only columns that exist to avoid SQL errors across schema changes
    $desiredUserCols = [
        'id',
        'u_uid',
        'full_name',
        'email',
        'address',
        'mobile',
        'phone',
        'birthday',
        'created_at',
        'updated_at',
        'profile_image',
        'role'
    ];
    $selectCols = [];
    foreach ($desiredUserCols as $c) {
        if (in_array($c, $userCols, true)) $selectCols[] = "`$c`";
    }
    if (!in_array('id', $userCols, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'users table is missing id column']);
        exit;
    }
    if (empty($selectCols)) $selectCols = ['`id`'];

    $sqlUser = "SELECT " . implode(', ', $selectCols) . " FROM users WHERE id = ? AND is_deleted = 0 AND role != 'admin' LIMIT 1";
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

    // ------------------------
    // Exam attempts stats
    // ------------------------
    $attempts = [];
    $attemptsSummary = null;

    if (table_exists($conn, 'user_exam_attempts')) {
        $aCols = get_table_columns($conn, 'user_exam_attempts');
        $scoreCol = pick_first_existing(['score', 'exam_score', 'points'], $aCols);
        $correctCol = pick_first_existing(['total_correct', 'correct', 'correct_count'], $aCols);
        $answeredCol = pick_first_existing(['total_answered', 'answered', 'answered_count', 'total_questions_answered'], $aCols);
        $examIdCol = pick_first_existing(['exam_id', 'quiz_id', 'test_id'], $aCols);
        $timeCol = pick_first_existing(['created_at', 'attempted_at', 'submitted_at', 'updated_at', 'started_at'], $aCols);

        $attemptSelect = [];
        if ($examIdCol) $attemptSelect[] = "`$examIdCol` AS exam_id";
        if ($scoreCol) $attemptSelect[] = "`$scoreCol` AS score";
        if ($correctCol) $attemptSelect[] = "`$correctCol` AS total_correct";
        if ($answeredCol) $attemptSelect[] = "`$answeredCol` AS total_answered";
        if ($timeCol) $attemptSelect[] = "`$timeCol` AS attempted_at";

        if (!empty($attemptSelect)) {
            $attemptSql = "SELECT " . implode(', ', $attemptSelect) . " FROM user_exam_attempts WHERE user_id = ? " .
                ($timeCol ? "ORDER BY `$timeCol` DESC " : "ORDER BY user_id DESC ") .
                "LIMIT 10";
            $stmtA = $conn->prepare($attemptSql);
            $stmtA->bind_param('i', $userId);
            $stmtA->execute();
            $resA = $stmtA->get_result();
            while ($row = $resA->fetch_assoc()) {
                $attempts[] = $row;
            }
            $stmtA->close();
        }

        // Summary (only include aggregates for columns that exist)
        $agg = [];
        if ($scoreCol) {
            $agg[] = "AVG(`$scoreCol`) AS avg_score";
            $agg[] = "MAX(`$scoreCol`) AS best_score";
        }
        if ($correctCol) $agg[] = "SUM(`$correctCol`) AS sum_total_correct";
        if ($answeredCol) $agg[] = "SUM(`$answeredCol`) AS sum_total_answered";
        $agg[] = "COUNT(*) AS total_attempts";

        $sumSql = "SELECT " . implode(', ', $agg) . " FROM user_exam_attempts WHERE user_id = ?";
        $stmtAS = $conn->prepare($sumSql);
        $stmtAS->bind_param('i', $userId);
        $stmtAS->execute();
        $attemptsSummary = $stmtAS->get_result()->fetch_assoc();
        $stmtAS->close();
    }

    // ------------------------
    // User progress stats
    // ------------------------
    $progressRows = [];
    $progressSummary = null;

    if (table_exists($conn, 'user_progress')) {
        $pCols = get_table_columns($conn, 'user_progress');

        $progressCol = pick_first_existing(['progress', 'progress_percent', 'percentage', 'percent'], $pCols);
        $completedCol = pick_first_existing(['is_completed', 'completed'], $pCols);
        $statusCol = pick_first_existing(['status'], $pCols);
        $moduleCol = pick_first_existing(['module', 'lesson_id', 'topic_id', 'chapter_id', 'course_id'], $pCols);
        $timeColP = pick_first_existing(['updated_at', 'created_at'], $pCols);

        $pSelect = [];
        if ($moduleCol) $pSelect[] = "`$moduleCol` AS item";
        if ($progressCol) $pSelect[] = "`$progressCol` AS progress";
        if ($completedCol) $pSelect[] = "`$completedCol` AS is_completed";
        if ($statusCol) $pSelect[] = "`$statusCol` AS status";
        if ($timeColP) $pSelect[] = "`$timeColP` AS updated_at";

        if (!empty($pSelect)) {
            $pSql = "SELECT " . implode(', ', $pSelect) . " FROM user_progress WHERE user_id = ? " .
                ($timeColP ? "ORDER BY `$timeColP` DESC " : "ORDER BY user_id DESC ") .
                "LIMIT 10";
            $stmtP = $conn->prepare($pSql);
            $stmtP->bind_param('i', $userId);
            $stmtP->execute();
            $resP = $stmtP->get_result();
            while ($row = $resP->fetch_assoc()) {
                $progressRows[] = $row;
            }
            $stmtP->close();
        }

        // Summary
        $aggP = ["COUNT(*) AS total_records"];
        if ($progressCol) $aggP[] = "AVG(`$progressCol`) AS avg_progress";
        if ($completedCol) {
            $aggP[] = "SUM(CASE WHEN `$completedCol` = 1 THEN 1 ELSE 0 END) AS completed_count";
        } elseif ($statusCol) {
            $aggP[] = "SUM(CASE WHEN LOWER(`$statusCol`) IN ('completed','done','finished') THEN 1 ELSE 0 END) AS completed_count";
        }
        if ($timeColP) $aggP[] = "MAX(`$timeColP`) AS last_updated";

        $sumPSql = "SELECT " . implode(', ', $aggP) . " FROM user_progress WHERE user_id = ?";
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

// ------------------------
// List endpoint: ?page=1&limit=10&search=foo
// ------------------------
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = (int)($_GET['limit'] ?? 10);
if ($limit <= 0) $limit = 10;
if ($limit > 50) $limit = 50;

$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

$where = "WHERE is_deleted = 0 AND role != 'admin'";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (u_uid LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

// Count total
$countSql = "SELECT COUNT(*) FROM users $where";
$countStmt = $conn->prepare($countSql);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_row()[0] ?? 0;
$countStmt->close();

// Fetch users
$sql = "SELECT id, u_uid, full_name, email, created_at, profile_image
        FROM users
        $where
        ORDER BY id DESC
        LIMIT ? OFFSET ?";
$bindParams = $params;
$bindParams[] = $limit;
$bindParams[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$bindParams);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

echo json_encode([
    'users' => $users,
    'total' => (int)$total,
    'page'  => $page,
    'limit' => $limit
]);

$conn->close();
