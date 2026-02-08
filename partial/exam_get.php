<?php
// partial/exam_get.php
session_start();
require_once 'db_conn.php';
header('Content-Type: application/json');

$examId = (int)($_GET['id'] ?? 0);
if ($examId <= 0) {
    echo json_encode(['success' => false, 'msg' => 'Invalid exam ID']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->bind_param('i', $examId);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

if (!$exam) {
    echo json_encode(['success' => false, 'msg' => 'Exam not found']);
    exit;
}

// Calculate completions (finished attempts)
$compResult = $conn->query("SELECT COUNT(*) FROM user_exam_attempts WHERE exam_id = $examId AND finished_at IS NOT NULL");
$exam['completions'] = $compResult ? (int)$compResult->fetch_row()[0] : 0;

// Calculate average score (safe: 0 if no attempts)
$avgResult = $conn->query("SELECT COALESCE(AVG(score), 0) FROM user_exam_attempts WHERE exam_id = $examId AND score IS NOT NULL AND finished_at IS NOT NULL");
$exam['avg_score'] = $avgResult ? (int)round($avgResult->fetch_row()[0]) : 0;

$questions = [];
$qstmt = $conn->prepare("SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY id");
$qstmt->bind_param('i', $examId);
$qstmt->execute();
$qres = $qstmt->get_result();

while ($qrow = $qres->fetch_assoc()) {
    $choices = [];
    $cstmt = $conn->prepare("SELECT * FROM exam_answers WHERE question_id = ? ORDER BY order_index");
    $cstmt->bind_param('i', $qrow['id']);
    $cstmt->execute();
    $cres = $cstmt->get_result();

    while ($crow = $cres->fetch_assoc()) {
        $choices[] = [
            'text' => $crow['answer_text'],
            'correct' => (bool)$crow['is_correct']
        ];
    }

    $questions[] = [
        'text' => $qrow['question_text'],
        'type' => $qrow['type'],
        'image_path' => $qrow['image_path'],
        'attachment_path' => $qrow['attachment_path'],
        'choices' => $choices
    ];
}

$exam['questions'] = $questions;
echo json_encode(['success' => true, 'exam' => $exam]);
?>