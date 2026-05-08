<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
session_start();

$user_id = (int)($_SESSION['user_id'] ?? 0);
$role = (string)($_SESSION['role'] ?? '');
$is_guest = ($role === 'guest');

if (!isset($conn)) {
    echo json_encode(['error' => 'Database connection unavailable']);
    exit;
}

/* ---------------------------------------------------------
   GUEST HISTORY
   - Guest exam attempts are stored in $_SESSION['guest_exam_progress'].
   - This keeps the same shape expected by practical-exams.php / take-exam.php.
--------------------------------------------------------- */
if ($is_guest) {
    $attempts = $_SESSION['guest_exam_progress']['attempts'] ?? [];
    $historyRows = $_SESSION['guest_exam_progress']['history'] ?? [];

    if (is_array($historyRows) && !empty($historyRows)) {
        $source = array_reverse($historyRows);
    } elseif (is_array($attempts) && !empty($attempts)) {
        $source = array_reverse(array_values($attempts));
    } else {
        $source = [];
    }

    $history = [];
    foreach (array_slice($source, 0, 50) as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $score = isset($entry['best_score'])
            ? (int)round((float)$entry['best_score'])
            : (int)round((float)($entry['last_score'] ?? $entry['score'] ?? 0));

        $passing = (int)round((float)($entry['passing_score'] ?? 75));
        $finishedRaw = (string)($entry['finished_at'] ?? '');
        $finishedTs = $finishedRaw !== '' ? strtotime($finishedRaw) : false;

        $history[] = [
            'id' => 'guest-' . (int)($entry['exam_id'] ?? 0),
            'exam_id' => (int)($entry['exam_id'] ?? 0),
            'title' => $entry['title'] ?? 'Guest Exam Attempt',
            'category' => $entry['category'] ?? '—',
            'score' => $score,
            'date' => $finishedTs ? date('M j, Y', $finishedTs) : '—',
            'started_at' => $entry['started_at'] ?? null,
            'finished_at' => $entry['finished_at'] ?? null,
            'time_taken' => $entry['time_taken'] ?? '—',
            'passing_score' => $passing,
            'status' => ($score >= $passing && $score > 0) ? 'Passed' : 'Failed'
        ];
    }

    if (empty($history)) {
        $history = [];
    }

    echo json_encode($history);
    exit;
}

if (!$user_id) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

/* ---------------------------------------------------------
   SAFE QUERY HELPER
--------------------------------------------------------- */
function query($sql, $params = [], $types = "")
{
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    if ($params && $types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

/* ---------------------------------------------------------
   EXAM HISTORY - matching analytics date & style
--------------------------------------------------------- */
$history = [];

$result = query("
    SELECT 
        uea.id,
        uea.exam_id,
        uea.score,
        uea.started_at,
        uea.finished_at,
        e.title,
        e.category,
        e.passing_score
    FROM user_exam_attempts uea
    JOIN exams e ON uea.exam_id = e.id
    WHERE uea.user_id = ?
    ORDER BY uea.started_at DESC
    LIMIT 50
", [$user_id], "i");

if ($result === false) {
    echo json_encode(['error' => 'Database query failed']);
    exit;
}

while ($row = mysqli_fetch_assoc($result)) {
    $score = ($row['score'] !== null) ? (int) round((float)$row['score']) : 0;
    $started_timestamp = strtotime($row['started_at']);
    $date_display = ($started_timestamp === false || $started_timestamp <= 0)
        ? '—'
        : date('M j, Y', $started_timestamp);

    $start = new DateTime($row['started_at']);
    $end = $row['finished_at'] ? new DateTime($row['finished_at']) : null;
    $time_taken = $end ? $start->diff($end)->format('%i min %s sec') : '—';

    $passing = (int)($row['passing_score'] ?? 75);
    $status = ($score >= $passing && $score > 0) ? 'Passed' : 'Failed';

    $history[] = [
        'id' => (int)$row['id'],
        'exam_id' => (int)$row['exam_id'],
        'title' => $row['title'] ?? '—',
        'category' => $row['category'] ?? '—',
        'score' => $score,
        'date' => $date_display,
        'started_at' => $row['started_at'],
        'finished_at' => $row['finished_at'],
        'time_taken' => $time_taken,
        'passing_score' => $passing,
        'status' => $status
    ];
}

if (empty($history)) {
    $history = [];
}

echo json_encode($history);
