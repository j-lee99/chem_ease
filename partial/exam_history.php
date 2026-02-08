<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id || !isset($conn)) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

/* ---------------------------------------------------------
   SAFE QUERY HELPER
--------------------------------------------------------- */
function query($sql, $params = [], $types = "") {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        // In production: log error
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
    // Score: NULL → 0, same as analytics
    $score = ($row['score'] !== null) ? (int) round((float)$row['score']) : 0;

    // ── DATE HANDLING - same format as analytics ──
    $started_timestamp = strtotime($row['started_at']);
    
    if ($started_timestamp === false || $started_timestamp <= 0) {
        // Fallback in case of invalid date
        $date_display = '—';
    } else {
        $date_display = date('M j, Y', $started_timestamp);  // ← "Jan 18, 2026"
    }

    // Time taken - exact same logic as analytics
    $start = new DateTime($row['started_at']);
    $end   = $row['finished_at'] ? new DateTime($row['finished_at']) : null;
    
    $time_taken = $end 
        ? $start->diff($end)->format('%i min %s sec')
        : '—';

    // Status - you seem to want Passed/Failed
    $passing = (int)($row['passing_score'] ?? 75); // fallback if column missing
    $status = ($score >= $passing && $score > 0) ? 'Passed' : 'Failed';

    $history[] = [
        'id'            => (int)$row['id'],
        'title'         => $row['title'] ?? '—',
        'category'      => $row['category'] ?? '—',
        'score'         => $score,
        'date'          => $date_display,           // This is what you want: "Jan 18, 2026"
        'started_at'    => $row['started_at'],      // raw value - good for debugging
        'finished_at'   => $row['finished_at'],
        'time_taken'    => $time_taken,
        'passing_score' => $passing,
        'status'        => $status
    ];
}

// Fallback when no records
if (empty($history)) {
    $history[] = [
        'title'       => 'No exam attempts yet',
        'category'    => '—',
        'score'       => 0,
        'date'        => '—',
        'time_taken'  => '—',
        'status'      => '—',
        'passing_score' => 0
    ];
}

echo json_encode($history);
?>