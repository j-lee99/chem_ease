<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id || !isset($conn)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

function jsonError($message, $status = 500) {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

function query($sql, $params = [], $types = "") {
    global $conn;

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}

function fetchScalar($sql, $params = [], $types = "", $default = 0) {
    $result = query($sql, $params, $types);
    if (!$result) {
        return $default;
    }

    $row = mysqli_fetch_row($result);
    return $row[0] ?? $default;
}

$stats = [
    'overall_score' => [
        'value' => 0,
        'change' => 0,
        'trend' => 'neutral',
        'period' => 'overall'
    ],
    'exams_completed' => [
        'value' => 0,
        'change' => 0,
        'trend' => 'neutral',
        'period' => 'overall'
    ],
    'materials_completed' => [
        'value' => 0,
        'change' => 0,
        'trend' => 'neutral',
        'period' => 'overall'
    ]
];

$stats['exams_completed']['value'] = (int) fetchScalar(
    "SELECT COUNT(*) FROM user_exam_attempts WHERE user_id = ? AND finished_at IS NOT NULL",
    [$user_id],
    "i"
);

$avgScore = fetchScalar(
    "SELECT AVG(score) FROM user_exam_attempts WHERE user_id = ? AND score IS NOT NULL",
    [$user_id],
    "i",
    0
);
$stats['overall_score']['value'] = $avgScore !== null ? (int) round((float) $avgScore) : 0;

$stats['materials_completed']['value'] = (int) fetchScalar(
    "SELECT COUNT(*) FROM user_progress WHERE user_id = ? AND progress = 100",
    [$user_id],
    "i"
);

$topic_performance = [];
$result = query("
    SELECT
        e.category,
        AVG(uea.score) AS avg_score
    FROM user_exam_attempts uea
    JOIN exams e ON uea.exam_id = e.id
    WHERE uea.user_id = ? AND uea.score IS NOT NULL
    GROUP BY e.category
    ORDER BY e.category ASC
", [$user_id], "i");

if ($result === false) {
    jsonError('Failed to fetch topic performance.');
}

while ($row = mysqli_fetch_assoc($result)) {
    $score = (int) round((float) ($row['avg_score'] ?? 0));

    $topic_performance[] = [
        'topic' => $row['category'] ?: 'Uncategorized',
        'score' => $score,
        'color' => $score >= 80 ? 'success' : ($score >= 70 ? 'warning' : 'danger')
    ];
}

if (empty($topic_performance)) {
    $topic_performance[] = [
        'topic' => 'No category data yet',
        'score' => 0,
        'color' => 'secondary'
    ];
}

$history = [];
$result = query("
    SELECT
        uea.score,
        uea.started_at,
        uea.finished_at,
        e.title,
        e.category
    FROM user_exam_attempts uea
    JOIN exams e ON uea.exam_id = e.id
    WHERE uea.user_id = ?
    ORDER BY uea.started_at DESC
    LIMIT 10
", [$user_id], "i");

if ($result === false) {
    jsonError('Failed to fetch exam history.');
}

while ($row = mysqli_fetch_assoc($result)) {
    $formattedDate = '—';
    $timeTaken = '—';

    if (!empty($row['started_at'])) {
        $timestamp = strtotime($row['started_at']);
        if ($timestamp !== false) {
            $formattedDate = date('M j, Y', $timestamp);
        }

        try {
            $start = new DateTime($row['started_at']);
            $end = !empty($row['finished_at']) ? new DateTime($row['finished_at']) : null;
            $timeTaken = $end ? $start->diff($end)->format('%i min %s sec') : '—';
        } catch (Exception $e) {
            $timeTaken = '—';
        }
    }

    $history[] = [
        'title' => $row['title'],
        'category' => $row['category'],
        'score' => $row['score'] !== null ? (int) round((float) $row['score']) : null,
        'started_at' => $row['started_at'],
        'finished_at' => $row['finished_at'],
        'date' => $formattedDate,
        'time_taken' => $timeTaken
    ];
}

$recommendations = [];
$weakTopics = array_filter($topic_performance, fn($topic) => $topic['score'] < 70 && $topic['score'] > 0);

foreach ($weakTopics as $topic) {
    $recommendations[] = "Focus on {$topic['topic']} — your average score is {$topic['score']}%.";
}

if (empty($recommendations)) {
    $recommendations[] = "Excellent performance! Keep going!";
}

echo json_encode([
    'stats' => $stats,
    'topic_performance' => $topic_performance,
    'history' => $history,
    'recommendations' => $recommendations
]);