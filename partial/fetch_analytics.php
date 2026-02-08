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
    if ($params && $types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}
/* ---------------------------------------------------------
   1. USER STATS
--------------------------------------------------------- */
$stats = [
    'overall_score' => ['value' => 0, 'change' => 0, 'trend' => 'neutral', 'period' => 'overall'],
    'exams_completed' => ['value' => 0, 'change' => 0, 'trend' => 'neutral', 'period' => 'overall'],
    'study_time' => ['value' => 0, 'change' => 0, 'trend' => 'neutral', 'period' => 'overall']
];
// ── Exams Completed (just count ─ no percentage)
$r = query("SELECT COUNT(*) FROM user_exam_attempts WHERE user_id = ?", [$user_id], "i");
$stats['exams_completed']['value'] = (int) mysqli_fetch_row($r)[0];
// ── Overall Score = average of ALL scores from finished attempts
$r = query("
    SELECT AVG(score) AS avg_score
    FROM user_exam_attempts
    WHERE user_id = ? AND score IS NOT NULL
", [$user_id], "i");
$avg = mysqli_fetch_assoc($r)['avg_score'] ?? 0;
$stats['overall_score']['value'] = $avg ? (int) round($avg) : 0;
// ── Materials Completed (progress = 100%) – no unit/word
$r = query("
    SELECT COUNT(*)
    FROM user_progress up
    JOIN study_material_files smf ON up.file_id = smf.id
    WHERE up.user_id = ? AND up.progress = 100
", [$user_id], "i");
$stats['study_time']['value'] = (int) mysqli_fetch_row($r)[0];
/* ---------------------------------------------------------
   2. TOPIC PERFORMANCE (GROUPED BY CATEGORY)
--------------------------------------------------------- */
$topic_performance = [];
$r = query("
    SELECT
        e.category,
        AVG(uea.score) AS avg_score
    FROM user_exam_attempts uea
    JOIN exams e ON uea.exam_id = e.id
    WHERE uea.user_id = ? AND uea.score IS NOT NULL
    GROUP BY e.category
    ORDER BY e.category ASC
", [$user_id], "i");
while ($row = mysqli_fetch_assoc($r)) {
    $score = (int) round($row['avg_score'] ?? 0);
    $topic_performance[] = [
        'topic' => $row['category'],
        'score' => $score,
        'color' => $score >= 80 ? 'success' : ($score >= 70 ? 'warning' : 'danger')
    ];
}
if (empty($topic_performance)) {
    $topic_performance = [[
        'topic' => 'No category data yet',
        'score' => 0,
        'color' => 'secondary'
    ]];
}
/* ---------------------------------------------------------
   3. EXAM HISTORY (LAST 10)
--------------------------------------------------------- */
$history = [];
$r = query("
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
while ($row = mysqli_fetch_assoc($r)) {
    $row['score'] = $row['score'] !== null ? (int) round($row['score']) : 0;
    $row['date'] = date('M j, Y', strtotime($row['started_at']));
    $start = new DateTime($row['started_at']);
    $end = $row['finished_at'] ? new DateTime($row['finished_at']) : null;
    $row['time_taken'] = $end ? $start->diff($end)->format('%i min %s sec') : '—';
    $history[] = $row;
}
/* ---------------------------------------------------------
   4. RECOMMENDATIONS
--------------------------------------------------------- */
$recommendations = [];
$weak = array_filter($topic_performance, fn($t) => $t['score'] < 70 && $t['score'] > 0);
foreach ($weak as $t) {
    $recommendations[] = "Focus on <strong>{$t['topic']}</strong> — your average score is {$t['score']}%.";
}
if (empty($recommendations)) {
    $recommendations[] = "Excellent performance! Keep going!";
}
/* ---------------------------------------------------------
   5. FINAL OUTPUT
--------------------------------------------------------- */
echo json_encode([
    'stats' => $stats,
    'topic_performance' => $topic_performance,
    'history' => $history,
    'recommendations' => $recommendations
]);
?>