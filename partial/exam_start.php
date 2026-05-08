<?php
require_once 'db_conn.php';
session_start();

header('Content-Type: application/json');

function json_error(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

function is_post_test_title(string $title): bool
{
    return (bool)preg_match('/POST\s*TEST\s*\(\s*Module\s+[A-Za-z0-9IVXLCDM]+\s*\)/i', $title);
}

function guest_attempt_id(int $examId): string
{
    try {
        return 'guest-' . $examId . '-' . bin2hex(random_bytes(8));
    } catch (Throwable $e) {
        return 'guest-' . $examId . '-' . time() . '-' . mt_rand(1000, 9999);
    }
}

function build_questions_for_ids(mysqli $conn, array $questionIds): array
{
    $questionIds = array_values(array_filter(array_map('intval', $questionIds), fn($id) => $id > 0));
    if (empty($questionIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
    $types = str_repeat('i', count($questionIds));

    $stmt = $conn->prepare("
        SELECT
            q.id,
            q.question_text,
            q.type,
            q.image_path,
            q.attachment_path,
            a.id AS answer_id,
            a.answer_text
        FROM exam_questions q
        LEFT JOIN exam_answers a ON a.question_id = q.id
        WHERE q.id IN ($placeholders)
        ORDER BY FIELD(q.id, $placeholders), a.order_index ASC, a.id ASC
    ");

    if (!$stmt) {
        throw new Exception('Failed to prepare selected questions query.');
    }

    $bindValues = array_merge($questionIds, $questionIds);
    $bindTypes = $types . $types;
    $stmt->bind_param($bindTypes, ...$bindValues);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $questions = [];
    $current = null;

    foreach ($rows as $row) {
        if ($current && (int)$current['id'] !== (int)$row['id']) {
            $questions[] = $current;
            $current = null;
        }

        if (!$current) {
            $current = [
                'id' => (int)$row['id'],
                'text' => $row['question_text'],
                'type' => $row['type'],
                'image_path' => $row['image_path'],
                'attachment_path' => $row['attachment_path'],
                'choices' => []
            ];
        }

        if (!empty($row['answer_id'])) {
            $current['choices'][] = [
                'id' => (int)$row['answer_id'],
                'text' => $row['answer_text']
            ];
        }
    }

    if ($current) {
        $questions[] = $current;
    }

    return $questions;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$role = (string)($_SESSION['role'] ?? '');
$isGuest = ($role === 'guest');
$examId = (int)($_GET['exam_id'] ?? 0);

if ($examId <= 0) {
    json_error('Invalid request', 400);
}

/* ---------------------------------------------------------
   GUEST EXAM START
   Guests may start module Post Tests only. No DB attempt rows are created.
--------------------------------------------------------- */
if ($isGuest) {
    try {
        $examStmt = $conn->prepare("
            SELECT id, title, category, duration_minutes, passing_score, total_questions
            FROM exams
            WHERE id = ?
            LIMIT 1
        ");

        if (!$examStmt) {
            throw new Exception('Failed to prepare exam query.');
        }

        $examStmt->bind_param('i', $examId);
        $examStmt->execute();
        $exam = $examStmt->get_result()->fetch_assoc();
        $examStmt->close();

        if (!$exam) {
            throw new Exception('Exam not found.');
        }

        if (!is_post_test_title((string)$exam['title'])) {
            json_error('Guest mode can only access module Post Tests.', 403);
        }

        $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM exam_questions WHERE exam_id = ?");
        if (!$countStmt) {
            throw new Exception('Failed to prepare question count query.');
        }
        $countStmt->bind_param('i', $examId);
        $countStmt->execute();
        $availableQuestions = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $countStmt->close();

        if ($availableQuestions <= 0) {
            throw new Exception('No questions found for this exam.');
        }

        $totalQuestions = (int)($exam['total_questions'] ?? 0);
        if ($totalQuestions <= 0) {
            $totalQuestions = $availableQuestions;
        }
        $questionLimit = min($totalQuestions, $availableQuestions);

        $randomStmt = $conn->prepare("
            SELECT id
            FROM exam_questions
            WHERE exam_id = ?
            ORDER BY RAND()
            LIMIT ?
        ");
        if (!$randomStmt) {
            throw new Exception('Failed to prepare random question query.');
        }
        $randomStmt->bind_param('ii', $examId, $questionLimit);
        $randomStmt->execute();
        $randomResult = $randomStmt->get_result();

        $selectedQuestionIds = [];
        while ($row = $randomResult->fetch_assoc()) {
            $selectedQuestionIds[] = (int)$row['id'];
        }
        $randomStmt->close();

        if (empty($selectedQuestionIds)) {
            throw new Exception('No randomized questions were selected.');
        }

        $attemptId = guest_attempt_id($examId);
        if (!isset($_SESSION['guest_exam_runtime']) || !is_array($_SESSION['guest_exam_runtime'])) {
            $_SESSION['guest_exam_runtime'] = [];
        }

        $_SESSION['guest_exam_runtime'][$attemptId] = [
            'attempt_id' => $attemptId,
            'exam_id' => $examId,
            'question_ids' => $selectedQuestionIds,
            'started_at' => date('Y-m-d H:i:s')
        ];

        $exam['total_questions'] = $questionLimit;
        $questions = build_questions_for_ids($conn, $selectedQuestionIds);

        echo json_encode([
            'success' => true,
            'exam' => [
                'id' => (int)$exam['id'],
                'title' => $exam['title'],
                'category' => $exam['category'],
                'duration_minutes' => (int)($exam['duration_minutes'] ?? 0),
                'passing_score' => (float)($exam['passing_score'] ?? 0),
                'total_questions' => (int)$questionLimit,
            ],
            'attempt_id' => $attemptId,
            'questions' => $questions
        ]);
        exit;
    } catch (Throwable $e) {
        json_error($e->getMessage(), 500);
    }
}

if ($userId <= 0) {
    json_error('Invalid request', 400);
}

try {
    $conn->begin_transaction();

    $examStmt = $conn->prepare("
        SELECT id, title, category, duration_minutes, passing_score, total_questions
        FROM exams
        WHERE id = ?
        LIMIT 1
    ");

    if (!$examStmt) {
        throw new Exception('Failed to prepare exam query.');
    }

    $examStmt->bind_param("i", $examId);
    $examStmt->execute();
    $exam = $examStmt->get_result()->fetch_assoc();
    $examStmt->close();

    if (!$exam) {
        throw new Exception('Exam not found.');
    }

    $totalQuestions = (int)($exam['total_questions'] ?? 0);

    if ($totalQuestions <= 0) {
        throw new Exception('Exam total_questions is invalid.');
    }

    /*
     * IMPORTANT:
     * Always create a NEW attempt row for every exam start.
     *
     * The previous implementation looked for an existing row by user_id + exam_id,
     * reset its score to NULL, deleted its responses, and reused the same attempt id.
     * That erased previous attempts whenever the user retook or intentionally left an exam.
     *
     * Keeping one row per attempt allows:
     * - complete attempt history
     * - best score calculation
     * - average score calculation
     * - failed/abandoned attempts without deleting old passing scores
     */
    $insertAttemptStmt = $conn->prepare("
        INSERT INTO user_exam_attempts (user_id, exam_id, started_at)
        VALUES (?, ?, NOW())
    ");

    if (!$insertAttemptStmt) {
        throw new Exception('Failed to prepare attempt insert query.');
    }

    $insertAttemptStmt->bind_param("ii", $userId, $examId);
    $insertAttemptStmt->execute();
    $attemptId = (int)$conn->insert_id;
    $insertAttemptStmt->close();

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM exam_questions WHERE exam_id = ?");
    if (!$countStmt) {
        throw new Exception('Failed to prepare question count query.');
    }
    $countStmt->bind_param("i", $examId);
    $countStmt->execute();
    $availableQuestions = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $countStmt->close();

    if ($availableQuestions <= 0) {
        throw new Exception('No questions found for this exam.');
    }

    $questionLimit = min($totalQuestions, $availableQuestions);

    $randomStmt = $conn->prepare("SELECT id FROM exam_questions WHERE exam_id = ? ORDER BY RAND() LIMIT ?");
    if (!$randomStmt) {
        throw new Exception('Failed to prepare random question query.');
    }
    $randomStmt->bind_param("ii", $examId, $questionLimit);
    $randomStmt->execute();
    $randomResult = $randomStmt->get_result();

    $selectedQuestionIds = [];
    while ($row = $randomResult->fetch_assoc()) {
        $selectedQuestionIds[] = (int)$row['id'];
    }
    $randomStmt->close();

    if (empty($selectedQuestionIds)) {
        throw new Exception('No randomized questions were selected.');
    }

    $insertAttemptQuestionStmt = $conn->prepare("INSERT INTO attempt_questions (attempt_id, question_id, order_index) VALUES (?, ?, ?)");
    if (!$insertAttemptQuestionStmt) {
        throw new Exception('Failed to prepare attempt question insert query.');
    }

    foreach ($selectedQuestionIds as $index => $questionId) {
        $orderIndex = $index + 1;
        $insertAttemptQuestionStmt->bind_param("iii", $attemptId, $questionId, $orderIndex);
        $insertAttemptQuestionStmt->execute();
    }
    $insertAttemptQuestionStmt->close();

    $questionsStmt = $conn->prepare("
        SELECT 
            aq.order_index,
            q.id,
            q.question_text,
            q.type,
            q.image_path,
            q.attachment_path,
            a.id AS answer_id,
            a.answer_text
        FROM attempt_questions aq
        INNER JOIN exam_questions q ON q.id = aq.question_id
        LEFT JOIN exam_answers a ON a.question_id = q.id
        WHERE aq.attempt_id = ?
        ORDER BY aq.order_index ASC, a.order_index ASC, a.id ASC
    ");

    if (!$questionsStmt) {
        throw new Exception('Failed to prepare selected questions query.');
    }

    $questionsStmt->bind_param("i", $attemptId);
    $questionsStmt->execute();
    $questionRows = $questionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $questionsStmt->close();

    if (empty($questionRows)) {
        throw new Exception('No question data found for this attempt.');
    }

    $conn->commit();

    $out = [
        'success' => true,
        'exam' => [
            'id' => (int)$exam['id'],
            'title' => $exam['title'],
            'category' => $exam['category'],
            'duration_minutes' => (int)($exam['duration_minutes'] ?? 0),
            'passing_score' => (float)($exam['passing_score'] ?? 0),
            'total_questions' => (int)($exam['total_questions'] ?? 0),
        ],
        'attempt_id' => $attemptId,
        'questions' => []
    ];

    $currentQ = null;

    foreach ($questionRows as $row) {
        if ($currentQ && $currentQ['id'] !== (int)$row['id']) {
            $out['questions'][] = $currentQ;
            $currentQ = null;
        }

        if (!$currentQ) {
            $currentQ = [
                'id' => (int)$row['id'],
                'order_index' => (int)$row['order_index'],
                'text' => $row['question_text'],
                'type' => $row['type'],
                'image_path' => $row['image_path'],
                'attachment_path' => $row['attachment_path'],
                'choices' => []
            ];
        }

        if (!empty($row['answer_id'])) {
            $currentQ['choices'][] = [
                'id' => (int)$row['answer_id'],
                'text' => $row['answer_text']
            ];
        }
    }

    if ($currentQ) {
        $out['questions'][] = $currentQ;
    }

    echo json_encode($out);
} catch (Throwable $e) {
    if (isset($conn)) {
        $conn->rollback();
    }

    json_error($e->getMessage(), 500);
}

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $tableEsc = $conn->real_escape_string($table);
    $colEsc = $conn->real_escape_string($column);

    $sql = "SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$tableEsc}'
              AND COLUMN_NAME = '{$colEsc}'
            LIMIT 1";

    $res = $conn->query($sql);

    if (!$res) {
        return false;
    }

    $exists = $res->num_rows > 0;
    $res->free();

    return $exists;
}
?>
