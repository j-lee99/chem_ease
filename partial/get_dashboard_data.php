<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once 'db_conn.php';

$user_id = (int)$_SESSION['user_id'];

// ─────────────────────────────────────────────
// Leaderboard mode (used by dashboard widget)
// GET params: mode=leaderboard&page=&limit=&search=
// Returns: { success, leaderboard: { top3, users, total, page, limit, my_rank } }
// ─────────────────────────────────────────────
function tableHasColumn(mysqli $conn, string $table, string $column): bool
{
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    if (!$res) return false;
    $ok = $res->num_rows > 0;
    $res->free();
    return $ok;
}

function buildPointsExpression(mysqli $conn): string
{
    $candidates = ['points', 'score', 'total_points', 'total_score', 'user_points'];
    foreach ($candidates as $col) {
        if (tableHasColumn($conn, 'users', $col)) {
            return "COALESCE(u.`{$col}`, 0)";
        }
    }

    return "(
        (SELECT COALESCE(SUM(CASE WHEN up.progress >= 100 THEN 10 ELSE ROUND(up.progress / 10) END), 0)
         FROM user_progress up
         WHERE up.user_id = u.id)
        +
        (SELECT COALESCE(SUM(CASE WHEN uea.finished_at IS NOT NULL THEN uea.score ELSE 0 END), 0)
         FROM user_exam_attempts uea
         WHERE uea.user_id = u.id)
    )";
}

if (($_GET['mode'] ?? '') === 'leaderboard') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = (int)($_GET['limit'] ?? 10);
    if ($limit < 1) $limit = 10;
    if ($limit > 50) $limit = 50;
    $offset = ($page - 1) * $limit;
    $search = trim((string)($_GET['search'] ?? ''));

    $pointsExpr = buildPointsExpression($conn);

    $where = "WHERE u.role = 'user'";
    if ($search !== '') {
        $searchEsc = $conn->real_escape_string($search);
        $where .= " AND (u.full_name LIKE '%{$searchEsc}%')";
    }

    // Total
    $total = 0;
    $totalRes = $conn->query("SELECT COUNT(*) AS total FROM users u {$where}");
    if ($totalRes) {
        $row = $totalRes->fetch_assoc();
        $total = (int)($row['total'] ?? 0);
        $totalRes->free();
    }

    // Page rows
    $users = [];
    $sql = "
        SELECT u.id, u.full_name, profile_image, {$pointsExpr} AS points
        FROM users u
        {$where}
        ORDER BY points DESC, u.id ASC
        LIMIT {$offset}, {$limit}
    ";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $users[] = [
                'id' => (int)$r['id'],
                'full_name' => $r['full_name'],
                'points' => (int)round($r['points'] ?? 0),
                'profile_pic' => $r['profile_image'] ?? '',
            ];
        }
        $res->free();
    }

    $top3 = [];
    $topRes = $conn->query("
        SELECT u.id, u.full_name, u.profile_image, {$pointsExpr} AS points
        FROM users u
        WHERE u.role = 'user'
        ORDER BY points DESC, u.id ASC
        LIMIT 3
    ");
    if ($topRes) {
        while ($r = $topRes->fetch_assoc()) {
            $top3[] = [
                'id' => (int)$r['id'],
                'full_name' => $r['full_name'],
                'points' => (int)round($r['points'] ?? 0),
                'profile_pic' => $r['profile_image'] ?? '',
            ];
        }
        $topRes->free();
    }

    $myRank = null;
    $myPoints = 0;
    $myRes = $conn->query("SELECT {$pointsExpr} AS points FROM users u WHERE u.id = {$user_id} LIMIT 1");
    if ($myRes) {
        $row = $myRes->fetch_assoc();
        $myPoints = (int)round($row['points'] ?? 0);
        $myRes->free();

        $rankRes = $conn->query("SELECT (COUNT(*) + 1) AS r FROM users u WHERE u.role='user' AND ({$pointsExpr}) > {$myPoints}");
        if ($rankRes) {
            $rrow = $rankRes->fetch_assoc();
            $myRank = (int)($rrow['r'] ?? null);
            $rankRes->free();
        }
    }

    echo json_encode([
        'success' => true,
        'leaderboard' => [
            'top3' => $top3,
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'my_rank' => $myRank,
        ]
    ]);
    exit;
}

$response = [
    'success' => true,
    'stats' => [],
    'progress' => [],
    'recent_activities' => [],
    'performance' => []
];

$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM study_material_files");
$total_files = $total_result ? mysqli_fetch_assoc($total_result)['total'] : 0;

$comp_result = mysqli_query($conn, "
    SELECT COUNT(*) as completed 
    FROM user_progress 
    WHERE user_id = $user_id AND progress >= 100
");
$completed_files = $comp_result ? mysqli_fetch_assoc($comp_result)['completed'] : 0;

$overall_completion = $total_files > 0 ? round(($completed_files / $total_files) * 100) : 0;

$streak_result = mysqli_query($conn, "
    SELECT DISTINCT DATE(updated_at) as d 
    FROM user_progress 
    WHERE user_id = $user_id AND progress > 0
    UNION
    SELECT DISTINCT DATE(started_at) as d 
    FROM user_exam_attempts 
    WHERE user_id = $user_id
    ORDER BY d DESC
");

$dates = [];
if ($streak_result) {
    while ($row = mysqli_fetch_assoc($streak_result)) {
        $dates[] = $row['d'];
    }
}

$study_streak = 0;
if (!empty($dates)) {
    $today = date('Y-m-d');
    $check_date = $today;
    $streak = 0;
    foreach ($dates as $d) {
        if ($d === $check_date) {
            $streak++;
            $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
        } elseif ($d < $check_date) {
            break;
        }
    }
    $study_streak = $streak;
}

$days_result = mysqli_query($conn, "
    SELECT COUNT(DISTINCT study_date) as total_active
    FROM (
        SELECT DATE(updated_at) as study_date 
        FROM user_progress 
        WHERE user_id = $user_id AND progress > 0
        UNION
        SELECT DATE(started_at) 
        FROM user_exam_attempts 
        WHERE user_id = $user_id
    ) combined
");
$days_active = $days_result ? mysqli_fetch_assoc($days_result)['total_active'] : 0;

$total_attempts_result = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM user_exam_attempts 
    WHERE user_id = $user_id AND finished_at IS NOT NULL
");
$total_attempts = $total_attempts_result ? mysqli_fetch_assoc($total_attempts_result)['total'] : 0;

$response['stats'] = [
    'overall_completion' => $overall_completion,
    'study_streak' => $study_streak,
    'days_active' => $days_active,
    'topics_completed' => "$completed_files/$total_files",
    'total_attempts' => $total_attempts
];

$progress_result = mysqli_query($conn, "
    SELECT
        sm.category,
        COUNT(smf.id) AS total_files,
        COUNT(CASE WHEN COALESCE(up.progress, 0) >= 100 THEN 1 END) AS completed_files,
        ROUND(
            (COUNT(CASE WHEN COALESCE(up.progress, 0) >= 100 THEN 1 END) * 100.0 / NULLIF(COUNT(smf.id), 0))
        ) AS percentage
    FROM study_materials sm
    LEFT JOIN study_material_files smf ON sm.id = smf.material_id
    LEFT JOIN user_progress up ON smf.id = up.file_id AND up.user_id = $user_id
    GROUP BY sm.category
    ORDER BY sm.category
");

if ($progress_result) {
    while ($row = mysqli_fetch_assoc($progress_result)) {
        $response['progress'][] = [
            'category' => $row['category'],
            'percentage' => (int)($row['percentage'] ?? 0),
            'completed' => (int)$row['completed_files'],
            'total' => (int)$row['total_files']
        ];
    }
}

$activities = [];

$study_res = mysqli_query($conn, "
    SELECT 
        sm.title, 
        sm.category, 
        up.progress, 
        up.updated_at as ts,
        CASE WHEN up.progress >= 100 THEN 'Completed' ELSE 'In Progress' END as status
    FROM user_progress up
    JOIN study_material_files smf ON up.file_id = smf.id
    JOIN study_materials sm ON smf.material_id = sm.id
    WHERE up.user_id = $user_id AND up.progress > 0
    ORDER BY up.updated_at DESC
    LIMIT 10
");

if ($study_res) {
    while ($a = mysqli_fetch_assoc($study_res)) {
        $activities[] = [
            'type' => 'study',
            'title' => $a['title'],
            'description' => 'Studied ' . $a['category'],
            'status' => $a['status'],
            'time_ago' => timeAgo(strtotime($a['ts'])),
            'icon' => 'book-open',
            'color' => $a['status'] === 'Completed' ? 'success' : 'warning',
            'timestamp' => strtotime($a['ts'])
        ];
    }
}

// Exam Activities
$exam_res = mysqli_query($conn, "
    SELECT 
        e.title, 
        e.category, 
        uea.score, 
        uea.total_correct, 
        uea.total_answered,
        COALESCE(uea.finished_at, uea.started_at) as ts,
        CASE 
            WHEN uea.finished_at IS NULL THEN 'In Progress'
            WHEN uea.score >= e.passing_score THEN 'Passed'
            ELSE 'Failed' 
        END as status
    FROM user_exam_attempts uea
    JOIN exams e ON uea.exam_id = e.id
    WHERE uea.user_id = $user_id
    ORDER BY ts DESC
    LIMIT 10
");

// if ($exam_res) {
//     while ($a = mysqli_fetch_assoc($exam_res)) {
//         $desc = $a['finished_at'] ? "Score: {$a['score']}% ({$a['total_correct']}/{$a['total_answered']})" : "Exam in progress";
//         $activities[] = [
//             'type' => 'exam',
//             'title' => $a['title'],
//             'description' => $desc,
//             'status' => $a['status'],
//             'icon' => 'flask',
//             'color' => $a['status'] === 'Passed' ? 'success' : ($a['status'] === 'Failed' ? 'danger' : 'info'),
//             'time_ago' => timeAgo(strtotime($a['ts'])),
//             'timestamp' => strtotime($a['ts'])
//         ];
//     }
// }

if ($exam_res) {
    while ($a = mysqli_fetch_assoc($exam_res)) {
        $isFinished = !empty($a['finished_at']);
        $desc = $isFinished ? "Score: {$a['score']}% ({$a['total_correct']}/{$a['total_answered']})" : "Exam in progress";
        $activities[] = [
            'type' => 'exam',
            'title' => $a['title'],
            'description' => $desc,
            'status' => $a['status'],
            'icon' => 'flask',
            'color' => $a['status'] === 'Passed' ? 'success' : ($a['status'] === 'Failed' ? 'danger' : 'info'),
            'time_ago' => timeAgo(strtotime($a['ts'])),
            'timestamp' => strtotime($a['ts'])
        ];
    }
}

// Sort and limit recent activities
usort($activities, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
$response['recent_activities'] = array_slice($activities, 0, 5);

$perf_res = mysqli_query($conn, "
    SELECT
        e.category,
        AVG(uea.score) as avg_score,
        COUNT(*) as total_exams,
        SUM(uea.total_correct) as total_correct,
        SUM(uea.total_answered) as total_answered
    FROM user_exam_attempts uea
    JOIN exams e ON uea.exam_id = e.id
    WHERE uea.user_id = $user_id AND uea.finished_at IS NOT NULL
    GROUP BY e.category
");

if ($perf_res) {
    while ($p = mysqli_fetch_assoc($perf_res)) {
        $response['performance'][] = [
            'category' => $p['category'],
            'avg_score' => round($p['avg_score'] ?? 0),
            'total_correct' => (int)($p['total_correct'] ?? 0),
            'total_answered' => (int)($p['total_answered'] ?? 0),
            'total_exams' => (int)$p['total_exams']
        ];
    }
}

function timeAgo($ts)
{
    $diff = time() - $ts;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min' . (floor($diff / 60) > 1 ? 's' : '') . ' ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
    return date('M j', $ts);
}

echo json_encode($response);
