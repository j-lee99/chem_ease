<?php
// partial/exam_submit.php
require_once 'db_conn.php';
session_start();

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $tableEsc = $conn->real_escape_string($table);
    $colEsc = $conn->real_escape_string($column);
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tableEsc}' AND COLUMN_NAME = '{$colEsc}'
            LIMIT 1";
    $res = $conn->query($sql);
    if (!$res) return false;
    $exists = $res->num_rows > 0;
    $res->free();
    return $exists;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$attemptId = (int)($data['attempt_id'] ?? 0);
$responses = $data['responses'] ?? [];

if (!$attemptId || !$userId) {
    exit(json_encode(['success' => false, 'msg' => 'Invalid request']));
}

$conn->begin_transaction();
try {
    $totalCorrect = 0;
    $totalAnswered = 0;

    foreach ($responses as $r) {
        $qid = (int)$r['question_id'];
        $aid = $r['answer_id'] === null ? null : (int)$r['answer_id'];
        $correct = $aid ? ($conn->query("SELECT is_correct FROM exam_answers WHERE id = $aid")->fetch_row()[0] ?? 0) : 0;

        if ($aid !== null) $totalAnswered++;
        if ($correct) $totalCorrect++;

        $stmt = $conn->prepare("INSERT INTO user_exam_responses (attempt_id, question_id, selected_answer_id, is_correct) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE selected_answer_id=VALUES(selected_answer_id), is_correct=VALUES(is_correct)");
        $stmt->bind_param('iiii', $attemptId, $qid, $aid, $correct);
        $stmt->execute();
    }

    $examInfo = $conn->query("
        SELECT e.total_questions, e.passing_score 
        FROM exams e 
        JOIN user_exam_attempts a ON a.exam_id = e.id 
        WHERE a.id = $attemptId
    ")->fetch_assoc();

    $totalQ = (int)($examInfo['total_questions'] ?? 0);
    $passingScore = (float)($examInfo['passing_score'] ?? 0);
    $score = (int)$totalCorrect;

    // -----------------------------
    // Linear Transmutation (Base-60)
    // 0% -> 60, 100% -> 100
    // grade = 60 + 40 * (score / total_items)
    // -----------------------------
    $rawPercent = $totalQ > 0 ? ($score / $totalQ) * 100 : 0;
    $transmutedGrade = $totalQ > 0 ? 60 + (40 * ($score / $totalQ)) : 60;

    if ($transmutedGrade < 60) $transmutedGrade = 60;
    if ($transmutedGrade > 100) $transmutedGrade = 100;
    $transmutedGrade = round($transmutedGrade, 2);
    $rawPercent = round($rawPercent, 2);

    $setParts = [
        "finished_at = NOW()",
        "score = ?",
        "total_correct = ?",
        "total_answered = ?"
    ];
    $types = "iii";
    $values = [$score, $totalCorrect, $totalAnswered];

    if (column_exists($conn, 'user_exam_attempts', 'raw_percent')) {
        $setParts[] = "raw_percent = ?";
        $types .= "d";
        $values[] = $rawPercent;
    }
    if (column_exists($conn, 'user_exam_attempts', 'transmuted_grade')) {
        $setParts[] = "transmuted_grade = ?";
        $types .= "d";
        $values[] = $transmutedGrade;
    }

    $sql = "UPDATE user_exam_attempts SET " . implode(", ", $setParts) . " WHERE id = ?";
    $types .= "i";
    $values[] = $attemptId;

    $stmtUp = $conn->prepare($sql);
    $stmtUp->bind_param($types, ...$values);
    $stmtUp->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'score' => $score,
        'correct' => $totalCorrect,
        'total' => $totalQ,
        'passing_score' => $passingScore,
        'raw_percent' => $rawPercent,
        'grade' => $transmutedGrade,
        'passed' => ($passingScore > 0 ? ($transmutedGrade >= $passingScore) : null)
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'msg' => 'Server error']);
}
