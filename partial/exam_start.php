<?php
// partial/exam_start.php
require_once 'db_conn.php';
session_start();

header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);
$examId = (int)($_GET['exam_id'] ?? 0);

if ($userId <= 0 || $examId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Get exam info
$exam = $conn->query("SELECT * FROM exams WHERE id = $examId")->fetch_assoc();
if (!$exam) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Exam not found']);
    exit;
}

// Check for existing attempt
$stmt = $conn->prepare("
    SELECT id 
    FROM user_exam_attempts 
    WHERE user_id = ? AND exam_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $userId, $examId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Existing attempt → reuse it & RESET started_at + clear old answers
    $attempt = $result->fetch_assoc();
    $attemptId = $attempt['id'];

    // Reset started_at to NOW()
    $resetStmt = $conn->prepare("UPDATE user_exam_attempts SET started_at = NOW() WHERE id = ?");
    $resetStmt->bind_param("i", $attemptId);
    $resetStmt->execute();
    $resetStmt->close();

    // Clear previous responses (important for retakes)
    $conn->query("DELETE FROM user_exam_responses WHERE attempt_id = $attemptId");
} else {
    // No previous attempt → create new
    $stmt_insert = $conn->prepare("
        INSERT INTO user_exam_attempts 
        (user_id, exam_id, started_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt_insert->bind_param("ii", $userId, $examId);
    $stmt_insert->execute();
    $attemptId = $conn->insert_id;
    $stmt_insert->close();
}

$stmt->close();

// Get questions (unchanged)
$questions = $conn->query("
    SELECT 
        q.id,
        q.question_text,
        q.type,
        q.image_path,
        q.attachment_path,
        a.id AS answer_id,
        a.answer_text,
        a.is_correct
    FROM exam_questions q
    LEFT JOIN exam_answers a ON a.question_id = q.id
    WHERE q.exam_id = $examId
    ORDER BY q.id, a.order_index, a.id
")->fetch_all(MYSQLI_ASSOC);

$out = [
    'success' => true,
    'exam' => $exam,
    'attempt_id' => $attemptId,
    'questions' => []
];

$currentQ = null;
foreach ($questions as $row) {
    if ($currentQ && $currentQ['id'] != $row['id']) {
        $out['questions'][] = $currentQ;
        $currentQ = null;
    }
    if (!$currentQ) {
        $currentQ = [
            'id' => $row['id'],
            'text' => $row['question_text'],
            'type' => $row['type'],
            'image_path' => $row['image_path'],
            'attachment_path' => $row['attachment_path'],
            'choices' => []
        ];
    }
    if ($row['answer_id']) {
        $currentQ['choices'][] = [
            'id' => $row['answer_id'],
            'text' => $row['answer_text'],
            'correct' => (bool)$row['is_correct']
        ];
    }
}
if ($currentQ) {
    $out['questions'][] = $currentQ;
}

echo json_encode($out);
?>