<?php
header('Content-Type: application/json');
require_once 'db_conn.php';

$result = $conn->query("
    SELECT 
        COUNT(*) AS total_attempts,
        ROUND(COALESCE(AVG(score), 0)) AS avg_score,
        COUNT(CASE WHEN score IS NOT NULL THEN 1 END) AS scored_attempts
    FROM user_exam_attempts
    WHERE finished_at IS NOT NULL
");

if (!$result) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

$row = $result->fetch_assoc();

echo json_encode([
    'success'         => true,
    'total_attempts'  => (int)$row['total_attempts'],
    'avg_score'       => (int)$row['avg_score'],
    'scored_attempts' => (int)$row['scored_attempts']
]);
?>