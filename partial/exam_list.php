<?php
// partial/exam_list.php
// require_once 'db_conn.php';
// session_start();
// $userId = $_SESSION['user_id'] ?? 0;

// $stmt = $conn->prepare("
//     SELECT e.*,
//            COALESCE(a.total_correct,0) AS user_correct,
//            COALESCE(a.total_answered,0) AS user_answered,
//            COALESCE(a.score,0) AS user_score,
//            COUNT(q.id) AS actual_questions
//     FROM exams e
//     LEFT JOIN user_exam_attempts a ON a.exam_id=e.id AND a.user_id=?
//     LEFT JOIN exam_questions q ON q.exam_id=e.id
//     GROUP BY e.id
//     ORDER BY e.created_at DESC
// ");
// $stmt->bind_param('i',$userId);
// $stmt->execute();
// $res = $stmt->get_result();
// $exams = [];
// while($row = $res->fetch_assoc()){
//     $row['completions'] = (int)$conn->query("SELECT COUNT(*) FROM user_exam_attempts WHERE exam_id={$row['id']}")->fetch_row()[0];
//     $row['avg_score'] = (int)$conn->query("SELECT AVG(score) FROM user_exam_attempts WHERE exam_id={$row['id']} AND score IS NOT NULL")->fetch_row()[0] ?: 0;
//     $exams[] = $row;
// }
// echo json_encode($exams);
require_once 'db_conn.php';
session_start();

header('Content-Type: application/json');

$userId = (int) ($_SESSION['user_id'] ?? 0);
if (!$userId) {
    echo json_encode(['data' => []]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        e.id,
        e.title,
        e.description,
        e.created_at,
        e.difficulty,
        e.duration_minutes,
        e.passing_score,
        e.topic,
        e.category,

        COALESCE(ua.total_correct, 0)   AS user_correct,
        COALESCE(ua.total_answered, 0)  AS user_answered,
        COALESCE(ua.score, 0)           AS user_score,

        COUNT(DISTINCT q.id)            AS actual_questions,

        COUNT(DISTINCT ua2.id)          AS completions,
        COALESCE(AVG(ua2.score), 0)     AS avg_score

    FROM exams e

    LEFT JOIN user_exam_attempts ua 
        ON ua.exam_id = e.id 
       AND ua.user_id = ?

    LEFT JOIN exam_questions q 
        ON q.exam_id = e.id

    LEFT JOIN user_exam_attempts ua2
        ON ua2.exam_id = e.id

    GROUP BY 
        e.id, 
        e.title, 
        e.description, 
        e.topic,
        e.created_at,
        e.difficulty,
        e.passing_score,
        e.category,
        e.duration_minutes,
        ua.total_correct,
        ua.total_answered,
        ua.score

    ORDER BY e.created_at DESC
");

$stmt->bind_param('i', $userId);
$stmt->execute();

$res = $stmt->get_result();
$exams = [];

while ($row = $res->fetch_assoc()) {
    $exams[] = [
        'id'                => (int) $row['id'],
        'title'             => $row['title'],
        'description'       => $row['description'],
        'created_at'        => $row['created_at'],
        'difficulty'        => $row['difficulty'],
        'topic'             => $row['topic'],
        'passing_score'     => $row['passing_score'],
        'category'          => $row['category'],

        'user_correct'      => (int) $row['user_correct'],
        'user_answered'     => (int) $row['user_answered'],
        'user_score'        => (int) $row['user_score'],

        'actual_questions' => (int) $row['actual_questions'],
        'completions'      => (int) $row['completions'],
        'avg_score'        => round((float) $row['avg_score'], 2),
        'duration_minutes' => (int) $row['duration_minutes']
    ];
}

$stmt->close();

echo json_encode([
    'data' => $exams
]);
