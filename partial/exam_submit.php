<?php
// partial/exam_submit.php
require_once 'db_conn.php';
session_start();

header('Content-Type: application/json');

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

function json_error(string $message, int $status = 400, ?string $debug = null): void
{
    http_response_code($status);

    $payload = [
        'success' => false,
        'error' => $message,
    ];

    if ($debug !== null) {
        $payload['message'] = $debug;
    }

    echo json_encode($payload);
    exit;
}

function is_post_test_title(string $title): bool
{
    return (bool)preg_match('/POST\s*TEST\s*\(\s*Module\s+[A-Za-z0-9IVXLCDM]+\s*\)/i', $title);
}

function post_test_module_code(string $title): string
{
    if (preg_match('/POST\s*TEST\s*\(\s*Module\s+([A-Za-z0-9IVXLCDM]+)\s*\)/i', $title, $m)) {
        return strtoupper(trim($m[1]));
    }
    return '';
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$role = (string)($_SESSION['role'] ?? '');
$isGuest = ($role === 'guest');
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    json_error('Invalid JSON payload', 400);
}

$attemptIdRaw = $data['attempt_id'] ?? null;
$responses = $data['responses'] ?? [];

if (!is_array($responses)) {
    json_error('Invalid request', 400);
}

/* ---------------------------------------------------------
   GUEST EXAM SUBMIT
   Uses $_SESSION['guest_exam_runtime'] instead of DB attempt tables.
   Guests can submit module Post Tests only.
--------------------------------------------------------- */
if ($isGuest) {
    $attemptKey = is_string($attemptIdRaw) ? $attemptIdRaw : (string)$attemptIdRaw;
    $runtime = $_SESSION['guest_exam_runtime'][$attemptKey] ?? null;

    if (!$attemptKey || !is_array($runtime)) {
        json_error('Guest exam attempt not found or expired.', 400);
    }

    $examId = (int)($runtime['exam_id'] ?? 0);
    $lockedQuestionIds = array_values(array_filter(array_map('intval', $runtime['question_ids'] ?? []), fn($id) => $id > 0));

    if ($examId <= 0 || empty($lockedQuestionIds)) {
        json_error('Invalid guest exam attempt.', 400);
    }

    try {
        $examStmt = $conn->prepare("SELECT id, title, category, passing_score FROM exams WHERE id = ? LIMIT 1");
        if (!$examStmt) {
            throw new Exception('Failed to prepare exam lookup.');
        }
        $examStmt->bind_param('i', $examId);
        $examStmt->execute();
        $exam = $examStmt->get_result()->fetch_assoc();
        $examStmt->close();

        if (!$exam) {
            throw new Exception('Exam not found.');
        }

        if (!is_post_test_title((string)$exam['title'])) {
            json_error('Guest mode can only submit module Post Tests.', 403);
        }

        $submittedResponses = [];
        foreach ($responses as $response) {
            $questionId = (int)($response['question_id'] ?? 0);
            $answerId = array_key_exists('answer_id', $response) && $response['answer_id'] !== null
                ? (int)$response['answer_id']
                : null;

            if ($questionId > 0) {
                $submittedResponses[$questionId] = $answerId;
            }
        }

        $answerValidationStmt = $conn->prepare("
            SELECT id, question_id, answer_text, is_correct
            FROM exam_answers
            WHERE id = ? AND question_id = ?
            LIMIT 1
        ");
        if (!$answerValidationStmt) {
            throw new Exception('Failed to prepare answer validation query.');
        }

        $correctAnswerStmt = $conn->prepare("
            SELECT id, answer_text
            FROM exam_answers
            WHERE question_id = ? AND is_correct = 1
            LIMIT 1
        ");
        if (!$correctAnswerStmt) {
            throw new Exception('Failed to prepare correct answer query.');
        }

        $questionTextStmt = $conn->prepare("SELECT question_text FROM exam_questions WHERE id = ? AND exam_id = ? LIMIT 1");
        if (!$questionTextStmt) {
            throw new Exception('Failed to prepare question text query.');
        }

        $totalQ = count($lockedQuestionIds);
        $totalCorrect = 0;
        $totalAnswered = 0;
        $details = [];

        foreach ($lockedQuestionIds as $idx => $questionId) {
            $selectedAnswerId = $submittedResponses[$questionId] ?? null;
            $isCorrect = 0;
            $userAnswerText = null;
            $correctAnswerId = null;
            $correctAnswerText = null;

            $questionTextStmt->bind_param('ii', $questionId, $examId);
            $questionTextStmt->execute();
            $questionRow = $questionTextStmt->get_result()->fetch_assoc();
            $questionText = $questionRow['question_text'] ?? '';

            $correctAnswerStmt->bind_param('i', $questionId);
            $correctAnswerStmt->execute();
            $correctAnswerRow = $correctAnswerStmt->get_result()->fetch_assoc();
            if ($correctAnswerRow) {
                $correctAnswerId = (int)$correctAnswerRow['id'];
                $correctAnswerText = $correctAnswerRow['answer_text'];
            }

            if ($selectedAnswerId !== null) {
                $totalAnswered++;
                $answerValidationStmt->bind_param('ii', $selectedAnswerId, $questionId);
                $answerValidationStmt->execute();
                $selectedAnswerRow = $answerValidationStmt->get_result()->fetch_assoc();

                if ($selectedAnswerRow) {
                    $userAnswerText = $selectedAnswerRow['answer_text'];
                    $isCorrect = (int)$selectedAnswerRow['is_correct'];
                    if ($isCorrect === 1) {
                        $totalCorrect++;
                    }
                } else {
                    $selectedAnswerId = null;
                    $userAnswerText = null;
                    $isCorrect = 0;
                    $totalAnswered--;
                }
            }

            $details[] = [
                'question_id' => $questionId,
                'order_index' => $idx + 1,
                'question_text' => $questionText,
                'user_answer_id' => $selectedAnswerId,
                'user_answer_text' => $userAnswerText,
                'correct_answer_id' => $correctAnswerId,
                'correct_answer_text' => $correctAnswerText,
                'is_correct' => (bool)$isCorrect,
                'is_answered' => $selectedAnswerId !== null,
            ];
        }

        $answerValidationStmt->close();
        $correctAnswerStmt->close();
        $questionTextStmt->close();

        $rawPercent = $totalQ > 0 ? ($totalCorrect / $totalQ) * 100 : 0;
        $transmutedGrade = $totalQ > 0 ? 60 + (40 * ($totalCorrect / $totalQ)) : 60;
        $transmutedGrade = max(60, min(100, $transmutedGrade));

        $rawPercent = round($rawPercent, 2);
        $transmutedGrade = round($transmutedGrade, 2);
        $passingScore = (float)($exam['passing_score'] ?? 0);
        $passedValue = ($passingScore > 0) ? ($transmutedGrade >= $passingScore ? 1 : 0) : 0;

        if (!isset($_SESSION['guest_exam_progress']) || !is_array($_SESSION['guest_exam_progress'])) {
            $_SESSION['guest_exam_progress'] = [];
        }
        if (!isset($_SESSION['guest_exam_progress']['attempts']) || !is_array($_SESSION['guest_exam_progress']['attempts'])) {
            $_SESSION['guest_exam_progress']['attempts'] = [];
        }
        if (!isset($_SESSION['guest_exam_progress']['history']) || !is_array($_SESSION['guest_exam_progress']['history'])) {
            $_SESSION['guest_exam_progress']['history'] = [];
        }

        $existing = $_SESSION['guest_exam_progress']['attempts'][(string)$examId] ?? [];
        $existingBest = isset($existing['best_score']) ? (float)$existing['best_score'] : null;
        $bestScore = $existingBest === null ? $transmutedGrade : max($existingBest, $transmutedGrade);

        $startedAt = (string)($runtime['started_at'] ?? date('Y-m-d H:i:s'));
        $finishedAt = date('Y-m-d H:i:s');
        $timeTaken = '—';
        $startTs = strtotime($startedAt);
        if ($startTs) {
            $diff = max(0, time() - $startTs);
            $timeTaken = sprintf('%02d:%02d', floor($diff / 60), $diff % 60);
        }

        $entry = [
            'exam_id' => $examId,
            'title' => substr((string)$exam['title'], 0, 180),
            'category' => substr((string)$exam['category'], 0, 120),
            'module_code' => post_test_module_code((string)$exam['title']),
            'best_score' => round($bestScore, 2),
            'last_score' => $transmutedGrade,
            'score' => $transmutedGrade,
            'raw_percent' => $rawPercent,
            'correct' => $totalCorrect,
            'total' => $totalQ,
            'total_answered' => $totalAnswered,
            'passing_score' => round($passingScore, 2),
            'time_taken' => $timeTaken,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'passed' => (bool)$passedValue
        ];

        $_SESSION['guest_exam_progress']['attempts'][(string)$examId] = $entry;
        $_SESSION['guest_exam_progress']['history'][] = $entry;
        if (count($_SESSION['guest_exam_progress']['history']) > 30) {
            $_SESSION['guest_exam_progress']['history'] = array_slice($_SESSION['guest_exam_progress']['history'], -30);
        }

        unset($_SESSION['guest_exam_runtime'][$attemptKey]);

        echo json_encode([
            'success' => true,
            'score' => $totalCorrect,
            'correct' => $totalCorrect,
            'incorrect' => max(0, $totalAnswered - $totalCorrect),
            'total_answered' => $totalAnswered,
            'unanswered' => max(0, $totalQ - $totalAnswered),
            'total' => $totalQ,
            'passing_score' => $passingScore,
            'raw_percent' => $rawPercent,
            'grade' => $transmutedGrade,
            'passed' => ($passingScore > 0 ? (bool)$passedValue : null),
            'details' => $details
        ]);
        exit;
    } catch (Throwable $e) {
        json_error('Server error', 500, $e->getMessage());
    }
}

$attemptId = (int)($attemptIdRaw ?? 0);

if ($userId <= 0 || $attemptId <= 0 || !is_array($responses)) {
    json_error('Invalid request', 400);
}

$conn->begin_transaction();

try {
    $attemptStmt = $conn->prepare("
        SELECT 
            a.id,
            a.user_id,
            a.exam_id,
            a.finished_at,
            e.passing_score
        FROM user_exam_attempts a
        INNER JOIN exams e ON e.id = a.exam_id
        WHERE a.id = ? AND a.user_id = ?
        LIMIT 1
    ");

    if (!$attemptStmt) {
        throw new Exception('Failed to prepare attempt validation query.');
    }

    $attemptStmt->bind_param("ii", $attemptId, $userId);
    $attemptStmt->execute();
    $attemptResult = $attemptStmt->get_result();
    $attempt = $attemptResult->fetch_assoc();
    $attemptStmt->close();

    if (!$attempt) {
        throw new Exception('Attempt not found or does not belong to this user.');
    }

    if (!empty($attempt['finished_at'])) {
        throw new Exception('This exam attempt has already been submitted.');
    }

    $passingScore = (float)($attempt['passing_score'] ?? 0);

    $lockedQuestionsStmt = $conn->prepare("
        SELECT question_id, order_index
        FROM attempt_questions
        WHERE attempt_id = ?
        ORDER BY order_index ASC
    ");

    if (!$lockedQuestionsStmt) {
        throw new Exception('Failed to prepare locked questions query.');
    }

    $lockedQuestionsStmt->bind_param("i", $attemptId);
    $lockedQuestionsStmt->execute();
    $lockedQuestionsResult = $lockedQuestionsStmt->get_result();

    $lockedQuestionIds = [];
    while ($row = $lockedQuestionsResult->fetch_assoc()) {
        $lockedQuestionIds[(int)$row['question_id']] = (int)$row['order_index'];
    }

    $lockedQuestionsStmt->close();

    if (empty($lockedQuestionIds)) {
        throw new Exception('No locked questions found for this attempt.');
    }

    $totalQ = count($lockedQuestionIds);

    $submittedResponses = [];
    foreach ($responses as $response) {
        $questionId = (int)($response['question_id'] ?? 0);
        $answerId = array_key_exists('answer_id', $response) && $response['answer_id'] !== null
            ? (int)$response['answer_id']
            : null;

        if ($questionId > 0) {
            $submittedResponses[$questionId] = $answerId;
        }
    }

    $answerValidationStmt = $conn->prepare("
        SELECT 
            id,
            question_id,
            answer_text,
            is_correct
        FROM exam_answers
        WHERE id = ? AND question_id = ?
        LIMIT 1
    ");

    if (!$answerValidationStmt) {
        throw new Exception('Failed to prepare answer validation query.');
    }

    $correctAnswerStmt = $conn->prepare("
        SELECT id, answer_text
        FROM exam_answers
        WHERE question_id = ? AND is_correct = 1
        LIMIT 1
    ");

    if (!$correctAnswerStmt) {
        throw new Exception('Failed to prepare correct answer query.');
    }

    $questionTextStmt = $conn->prepare("SELECT question_text FROM exam_questions WHERE id = ? LIMIT 1");

    if (!$questionTextStmt) {
        throw new Exception('Failed to prepare question text query.');
    }

    $saveResponseStmt = $conn->prepare("
        INSERT INTO user_exam_responses (
            attempt_id,
            question_id,
            selected_answer_id,
            is_correct
        )
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            selected_answer_id = VALUES(selected_answer_id),
            is_correct = VALUES(is_correct)
    ");

    if (!$saveResponseStmt) {
        throw new Exception('Failed to prepare response save query.');
    }

    $totalCorrect = 0;
    $totalAnswered = 0;
    $details = [];

    foreach ($lockedQuestionIds as $questionId => $orderIndex) {
        $selectedAnswerId = $submittedResponses[$questionId] ?? null;
        $isCorrect = 0;
        $userAnswerText = null;
        $correctAnswerId = null;
        $correctAnswerText = null;

        $questionTextStmt->bind_param("i", $questionId);
        $questionTextStmt->execute();
        $questionTextResult = $questionTextStmt->get_result();
        $questionRow = $questionTextResult->fetch_assoc();
        $questionText = $questionRow['question_text'] ?? '';

        $correctAnswerStmt->bind_param("i", $questionId);
        $correctAnswerStmt->execute();
        $correctAnswerResult = $correctAnswerStmt->get_result();
        $correctAnswerRow = $correctAnswerResult->fetch_assoc();

        if ($correctAnswerRow) {
            $correctAnswerId = (int)$correctAnswerRow['id'];
            $correctAnswerText = $correctAnswerRow['answer_text'];
        }

        if ($selectedAnswerId !== null) {
            $totalAnswered++;

            $answerValidationStmt->bind_param("ii", $selectedAnswerId, $questionId);
            $answerValidationStmt->execute();
            $answerValidationResult = $answerValidationStmt->get_result();
            $selectedAnswerRow = $answerValidationResult->fetch_assoc();

            if ($selectedAnswerRow) {
                $userAnswerText = $selectedAnswerRow['answer_text'];
                $isCorrect = (int)$selectedAnswerRow['is_correct'];

                if ($isCorrect === 1) {
                    $totalCorrect++;
                }
            } else {
                $selectedAnswerId = null;
                $userAnswerText = null;
                $isCorrect = 0;
                $totalAnswered--;
            }
        }

        $bindAnswerId = $selectedAnswerId;
        $saveResponseStmt->bind_param("iiii", $attemptId, $questionId, $bindAnswerId, $isCorrect);
        $saveResponseStmt->execute();

        $details[] = [
            'question_id' => $questionId,
            'order_index' => $orderIndex,
            'question_text' => $questionText,
            'user_answer_id' => $selectedAnswerId,
            'user_answer_text' => $userAnswerText,
            'correct_answer_id' => $correctAnswerId,
            'correct_answer_text' => $correctAnswerText,
            'is_correct' => (bool)$isCorrect,
            'is_answered' => $selectedAnswerId !== null,
        ];
    }

    $answerValidationStmt->close();
    $correctAnswerStmt->close();
    $questionTextStmt->close();
    $saveResponseStmt->close();

    $rawPercent = $totalQ > 0 ? ($totalCorrect / $totalQ) * 100 : 0;
    $transmutedGrade = $totalQ > 0 ? 60 + (40 * ($totalCorrect / $totalQ)) : 60;

    if ($transmutedGrade < 60) {
        $transmutedGrade = 60;
    }

    if ($transmutedGrade > 100) {
        $transmutedGrade = 100;
    }

    $rawPercent = round($rawPercent, 2);
    $transmutedGrade = round($transmutedGrade, 2);

    $passedValue = ($passingScore > 0)
        ? ($transmutedGrade >= $passingScore ? 1 : 0)
        : 0;

    $setParts = [
        "finished_at = NOW()",
        "score = ?",
        "total_correct = ?",
        "total_answered = ?",
    ];

    $types = "dii";
    $values = [$transmutedGrade, $totalCorrect, $totalAnswered];

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

    if (column_exists($conn, 'user_exam_attempts', 'passed')) {
        $setParts[] = "passed = ?";
        $types .= "i";
        $values[] = $passedValue;
    }

    $sql = "UPDATE user_exam_attempts SET " . implode(", ", $setParts) . " WHERE id = ?";
    $types .= "i";
    $values[] = $attemptId;

    $updateAttemptStmt = $conn->prepare($sql);

    if (!$updateAttemptStmt) {
        throw new Exception('Failed to prepare attempt update query.');
    }

    $updateAttemptStmt->bind_param($types, ...$values);
    $updateAttemptStmt->execute();
    $updateAttemptStmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'score' => $totalCorrect,
        'correct' => $totalCorrect,
        'incorrect' => max(0, $totalAnswered - $totalCorrect),
        'total_answered' => $totalAnswered,
        'unanswered' => max(0, $totalQ - $totalAnswered),
        'total' => $totalQ,
        'passing_score' => $passingScore,
        'raw_percent' => $rawPercent,
        'grade' => $transmutedGrade,
        'passed' => ($passingScore > 0 ? (bool)$passedValue : null),
        'details' => $details
    ]);
} catch (Throwable $e) {
    $conn->rollback();

    json_error('Server error', 500, $e->getMessage());
}
?>
