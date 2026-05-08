<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../partial/db_conn.php';

function resolveCurrentUserIdFromSession(): int
{
    if (!isset($_SESSION['user_id'])) {
        return 0;
    }

    $raw = (string)$_SESSION['user_id'];
    if (!ctype_digit($raw)) {
        return 0;
    }

    $id = (int)$raw;
    return $id > 0 ? $id : 0;
}

function buildGuestExamPayload(mysqli $conn, int $examId): ?array
{
    if ($examId <= 0) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    $exam = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$exam) {
        return null;
    }

    $stmtQ = $conn->prepare("
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
        WHERE q.exam_id = ?
        ORDER BY q.id, a.order_index, a.id
    ");
    $stmtQ->bind_param("i", $examId);
    $stmtQ->execute();
    $rows = $stmtQ->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtQ->close();

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
                'text' => $row['answer_text'],
                'correct' => (bool)$row['is_correct']
            ];
        }
    }
    if ($current) {
        $questions[] = $current;
    }

    $totalQuestions = isset($exam['total_questions']) ? (int)$exam['total_questions'] : 0;
    if ($totalQuestions <= 0) {
        $totalQuestions = count($questions);
    }
    $exam['total_questions'] = $totalQuestions;

    return [
        'success' => true,
        'exam' => $exam,
        'attempt_id' => 'guest-' . $examId . '-' . time(),
        'questions' => $questions
    ];
}

$role = (string)($_SESSION['role'] ?? '');
$isGuestUser = ($role === 'guest');
$user_id = resolveCurrentUserIdFromSession();

if (!in_array($role, ['user', 'guest'], true)) {
    header('Location: ../signin.php');
    exit;
}

if (!$isGuestUser && $user_id <= 0) {
    header('Location: ../signin.php');
    exit;
}

if (!isset($_SESSION['guest_exam_progress']) || !is_array($_SESSION['guest_exam_progress'])) {
    $_SESSION['guest_exam_progress'] = [];
}

if (isset($_GET['guest_exam_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = (string)$_GET['guest_exam_action'];

    if ($action === 'get') {
        echo json_encode([
            'ok' => true,
            'data' => $_SESSION['guest_exam_progress']
        ]);
        exit;
    }

    if ($action === 'start') {
        if (!$isGuestUser) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Guest-only action.']);
            exit;
        }

        $examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
        $payload = buildGuestExamPayload($conn, $examId);
        if (!$payload) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Exam not found.']);
            exit;
        }

        $guestExamTitle = (string)($payload['exam']['title'] ?? '');
        if (!preg_match('/POST TEST\s*\(Module\s+/i', $guestExamTitle)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Guest mode can take module Post Tests only.']);
            exit;
        }

        echo json_encode($payload);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    if ($action === 'save') {
        if (!$isGuestUser) {
            echo json_encode(['ok' => true, 'message' => 'Authenticated users store attempts in DB.']);
            exit;
        }

        $examId = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
        $title = trim((string)($_POST['title'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $moduleCode = trim((string)($_POST['module_code'] ?? ''));
        $score = max(0, min(100, (float)($_POST['score'] ?? 0)));
        $correct = max(0, (int)($_POST['correct'] ?? 0));
        $total = max(0, (int)($_POST['total'] ?? 0));
        $passingScore = max(0, min(100, (float)($_POST['passing_score'] ?? 0)));
        $timeTaken = trim((string)($_POST['time_taken'] ?? '00:00'));

        if ($examId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Invalid exam id.']);
            exit;
        }

        $stmtGuestExam = $conn->prepare("SELECT title FROM exams WHERE id = ? LIMIT 1");
        $stmtGuestExam->bind_param('i', $examId);
        $stmtGuestExam->execute();
        $guestExamRow = $stmtGuestExam->get_result()->fetch_assoc();
        $stmtGuestExam->close();
        if (!$guestExamRow || !preg_match('/POST TEST\s*\(Module\s+/i', (string)$guestExamRow['title'])) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Guest mode can save module Post Test attempts only.']);
            exit;
        }

        if (!isset($_SESSION['guest_exam_progress']['attempts']) || !is_array($_SESSION['guest_exam_progress']['attempts'])) {
            $_SESSION['guest_exam_progress']['attempts'] = [];
        }
        if (!isset($_SESSION['guest_exam_progress']['history']) || !is_array($_SESSION['guest_exam_progress']['history'])) {
            $_SESSION['guest_exam_progress']['history'] = [];
        }

        $key = (string)$examId;
        $existing = $_SESSION['guest_exam_progress']['attempts'][$key] ?? [];
        $best = isset($existing['best_score']) ? (float)$existing['best_score'] : null;
        $bestScore = ($best === null) ? $score : max($best, $score);

        $_SESSION['guest_exam_progress']['attempts'][$key] = [
            'exam_id' => $examId,
            'title' => substr($title, 0, 180),
            'category' => substr($category, 0, 120),
            'module_code' => substr($moduleCode, 0, 20),
            'best_score' => round($bestScore, 2),
            'last_score' => round($score, 2),
            'correct' => $correct,
            'total' => $total,
            'passing_score' => round($passingScore, 2),
            'time_taken' => substr($timeTaken, 0, 20),
            'finished_at' => date('Y-m-d H:i:s')
        ];

        $_SESSION['guest_exam_progress']['history'][] = $_SESSION['guest_exam_progress']['attempts'][$key];
        if (count($_SESSION['guest_exam_progress']['history']) > 30) {
            $_SESSION['guest_exam_progress']['history'] = array_slice($_SESSION['guest_exam_progress']['history'], -30);
        }

        echo json_encode([
            'ok' => true,
            'data' => $_SESSION['guest_exam_progress']['attempts'][$key]
        ]);
        exit;
    }

    if ($action === 'sync') {
        if ($isGuestUser || $user_id <= 0) {
            echo json_encode(['ok' => false, 'message' => 'Sign in required.']);
            exit;
        }

        $attempts = $_SESSION['guest_exam_progress']['attempts'] ?? [];
        if (!is_array($attempts) || empty($attempts)) {
            $_SESSION['guest_exam_progress'] = [];
            echo json_encode(['ok' => true, 'synced' => 0]);
            exit;
        }

        $synced = 0;
        $stmtSync = $conn->prepare("
            INSERT INTO user_exam_attempts (
                user_id, exam_id, started_at, finished_at, score, total_correct, total_answered
            ) VALUES (?, ?, NOW(), NOW(), ?, ?, ?)
        ");

        if ($stmtSync) {
            foreach ($attempts as $entry) {
                $examId = isset($entry['exam_id']) ? (int)$entry['exam_id'] : 0;
                $score = isset($entry['best_score']) ? (float)$entry['best_score'] : 0.0;
                $correct = isset($entry['correct']) ? (int)$entry['correct'] : 0;
                $total = isset($entry['total']) ? (int)$entry['total'] : 0;

                if ($examId <= 0) {
                    continue;
                }

                $stmtSync->bind_param("iidii", $user_id, $examId, $score, $correct, $total);
                if ($stmtSync->execute()) {
                    $synced++;
                }
            }
            $stmtSync->close();
        }

        $_SESSION['guest_exam_progress'] = [];
        echo json_encode(['ok' => true, 'synced' => $synced]);
        exit;
    }

    if ($action === 'clear') {
        $_SESSION['guest_exam_progress'] = [];
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Unknown action.']);
    exit;
}

// Always fetch fresh data from DB
$user = null;
if (!$isGuestUser) {
    $stmt = $conn->prepare("
        SELECT full_name, profile_image 
        FROM users 
        WHERE id = ? AND is_deleted = 0
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$isGuestUser && !$user) {
    session_destroy();
    header('Location: ../signin.php');
    exit;
}

$full_name     = $user['full_name'] ?? 'Guest User';
$profile_image = $user['profile_image'] ?? '';

$_SESSION['full_name']     = $full_name;
$_SESSION['profile_image'] = $profile_image;

// Initials
$initials = '';
if ($full_name) {
    $name_parts = explode(' ', trim($full_name));
    foreach ($name_parts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($initials) >= 2) break;
    }
}
if (empty($initials)) $initials = 'U';

$hasGuestExamProgress = !empty($_SESSION['guest_exam_progress']['attempts']);
$guestSelectedStudyModule = isset($_SESSION['guest_selected_study_module']) && is_array($_SESSION['guest_selected_study_module'])
    ? $_SESSION['guest_selected_study_module']
    : null;

$preloadedExams = [];
$sqlExams = "
    SELECT e.*, COUNT(q.id) AS actual_questions
    FROM exams e
    LEFT JOIN exam_questions q ON q.exam_id = e.id
    GROUP BY e.id
    ORDER BY e.created_at DESC, e.id DESC
";
if ($resExams = $conn->query($sqlExams)) {
    while ($row = $resExams->fetch_assoc()) {
        if (!isset($row['total_questions']) || (int)$row['total_questions'] <= 0) {
            $row['total_questions'] = (int)($row['actual_questions'] ?? 0);
        }
        $preloadedExams[] = $row;
    }
    $resExams->free();
}

// Page marker for navbar highlighting
$page = 'practical-exams';
?>

<?php
$cats = ['Analytical Chemistry', 'Organic Chemistry', 'Physical Chemistry', 'Inorganic Chemistry', 'BioChemistry'];

// Guest practical-exam unlock data.
// The study-materials page stores guest file progress in browser sessionStorage under `guest_progress`.
// This metadata lets the browser know which file IDs belong to every study module per category.
$guestModuleRequirements = [];
foreach ($cats as $guestCat) {
    $moduleStmt = $conn->prepare("
        SELECT id, title, category, module
        FROM study_materials
        WHERE category = ?
        ORDER BY
          CASE
            WHEN UPPER(TRIM(module)) REGEXP '^[A-Z]$' THEN ASCII(UPPER(TRIM(module))) - ASCII('A') + 1
            WHEN TRIM(module) REGEXP '^[0-9]+$' THEN CAST(TRIM(module) AS UNSIGNED)
            WHEN UPPER(TRIM(module)) = 'I' THEN 1
            WHEN UPPER(TRIM(module)) = 'II' THEN 2
            WHEN UPPER(TRIM(module)) = 'III' THEN 3
            WHEN UPPER(TRIM(module)) = 'IV' THEN 4
            WHEN UPPER(TRIM(module)) = 'V' THEN 5
            WHEN UPPER(TRIM(module)) = 'VI' THEN 6
            WHEN UPPER(TRIM(module)) = 'VII' THEN 7
            WHEN UPPER(TRIM(module)) = 'VIII' THEN 8
            WHEN UPPER(TRIM(module)) = 'IX' THEN 9
            WHEN UPPER(TRIM(module)) = 'X' THEN 10
            ELSE 999
          END,
          id ASC
    ");

    if (!$moduleStmt) {
        continue;
    }

    $moduleStmt->bind_param('s', $guestCat);
    $moduleStmt->execute();
    $moduleRes = $moduleStmt->get_result();

    while ($moduleRow = $moduleRes->fetch_assoc()) {
        $fileIds = [];
        $filesStmt = $conn->prepare("
            SELECT id
            FROM study_material_files
            WHERE material_id = ?
            ORDER BY id ASC
        ");

        if ($filesStmt) {
            $materialId = (int)$moduleRow['id'];
            $filesStmt->bind_param('i', $materialId);
            $filesStmt->execute();
            $filesRes = $filesStmt->get_result();
            while ($fileRow = $filesRes->fetch_assoc()) {
                $fileIds[] = (int)$fileRow['id'];
            }
            $filesStmt->close();
        }

        $guestModuleRequirements[] = [
            'material_id' => (int)$moduleRow['id'],
            'title' => $moduleRow['title'] ?? '',
            'category' => $moduleRow['category'] ?? $guestCat,
            'module' => $moduleRow['module'] ?? '',
            'file_ids' => $fileIds,
        ];
    }

    $moduleStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practice Exams</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #17a2b8;
            --gradient-end: #20c997;
            --dark-text: #2c3e50;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }

        * {
            box-sizing: border-box;
        }

        body {
            padding-top: 80px;
            overflow-x: hidden;
        }

        .primary-blue-header {
            background-color: var(--primary-blue) !important;
            color: #fff !important;
        }

        .practice-exams-container {
            padding: 1rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .page-header {
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.12) 0%, rgba(255, 255, 255, 0.97) 100%);
            padding: 3rem 2rem;
            border-radius: 24px;
            margin: 1.5rem auto 2.5rem;
            box-shadow: 0 12px 45px rgba(23, 162, 184, 0.14);
            backdrop-filter: blur(14px);
            text-align: center;
            border: 1px solid rgba(23, 162, 184, 0.08);
            max-width: 1200px;
        }

        .page-title {
            font-size: 2.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-blue), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 0.8rem 0;
            line-height: 1.2;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: #5c6b7f;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.8;
            font-weight: 400;
        }

        .exam-stats-bar {
            background: rgba(255, 255, 255, .9);
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(23, 162, 184, .1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .user-score {
            display: flex;
            align-items: center;
            gap: .5rem;
            color: var(--dark-text);
            font-weight: 600;
        }

        .view-history {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: all .3s ease;
            cursor: pointer;
        }

        .view-history:hover {
            color: var(--gradient-end);
            transform: translateX(5px);
        }

        .exams-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }

        .exam-card {
            background: rgba(255, 255, 255, .98);
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 6px 25px rgba(23, 162, 184, .12);
            transition: all .4s cubic-bezier(.4, 0, .2, 1);
            border: 1px solid rgba(23, 162, 184, .08);
            position: relative;
            overflow: hidden;
            min-height: 520px;
            display: flex;
            flex-direction: column;
        }

        .exam-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-blue), var(--gradient-end));
            transition: all .4s ease;
        }

        .exam-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 40px rgba(23, 162, 184, .2);
        }

        .exam-card:hover::before {
            height: 8px;
            background: linear-gradient(90deg, var(--gradient-end), var(--primary-blue));
        }

        .exam-card-content {
            padding: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .exam-header {
            margin-bottom: 1.5rem;
        }

        .exam-title {
            color: var(--dark-text);
            font-weight: 700;
            font-size: 1.35rem;
            margin-bottom: 1rem;
            line-height: 1.4;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .exam-state {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 999px;
            font-size: 1rem;
            flex: 0 0 auto;
        }

        .exam-state.locked {
            background: rgba(0, 0, 0, 0.06);
            color: rgba(0, 0, 0, 0.45);
        }

        .exam-state.passed {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
        }

        .exam-locked {
            opacity: 0.6;
            filter: grayscale(0.6);
            pointer-events: none;
        }

        .exam-locked .start-btn {
            background: rgba(0, 0, 0, 0.08) !important;
            color: rgba(0, 0, 0, 0.55) !important;
        }

        .exam-locked .start-btn i {
            opacity: 0.75;
        }

        .start-btn.disabled {
            cursor: not-allowed;
        }

        .exam-category-header {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
            font-weight: 700;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .exam-category-header:hover {
            background: #eef2f7;
        }

        .toggle-icon {
            font-size: 14px;
            transition: transform 0.2s ease;
        }

        .exam-grid {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(3, minmax(280px, 1fr));
            gap: 1.5rem;
            overflow: hidden;
            transition: all 0.25s ease;
        }

        .exam-grid.collapse {
            max-height: 0;
            opacity: 0;
            pointer-events: none;
            margin-top: 0;
        }

        .exam-grid.expand {
            max-height: 2000px;
            opacity: 1;
            margin-top: 1rem;
        }

        .topic-tabs {
            display: flex;
            gap: 0.6rem;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
            justify-content: center;
            background: rgba(255, 255, 255, 0.92);
            padding: 1rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(23, 162, 184, 0.15);
            backdrop-filter: blur(10px);
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .topic-tab {
            background: transparent;
            border: none;
            padding: 0.9rem 1.8rem;
            border-radius: 16px;
            font-weight: 600;
            color: #2c3e50;
            cursor: pointer;
            transition: all .4s ease;
            font-size: 1rem;
            white-space: nowrap;
            min-width: 140px;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .topic-tab:hover {
            background: rgba(23, 162, 184, 0.15);
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(23, 162, 184, 0.2);
        }

        .topic-tab.active {
            background: linear-gradient(135deg, var(--primary-blue), var(--gradient-end));
            color: white;
            box-shadow: 0 10px 30px rgba(23, 162, 184, 0.45);
            transform: translateY(-3px);
        }

        .difficulty-badge {
            padding: .4rem 1rem;
            border-radius: 25px;
            font-size: .8rem;
            font-weight: 700;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: .8px;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .difficulty-badge.success {
            background: linear-gradient(135deg, rgba(40, 167, 69, .15), rgba(40, 167, 69, .05));
            color: var(--success-color);
            border: 2px solid rgba(40, 167, 69, .3);
            box-shadow: 0 4px 15px rgba(40, 167, 69, .2);
        }

        .difficulty-badge.warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, .15), rgba(255, 193, 7, .05));
            color: #d68910;
            border: 2px solid rgba(255, 193, 7, .4);
            box-shadow: 0 4px 15px rgba(255, 193, 7, .2);
        }

        .difficulty-badge.danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, .15), rgba(220, 53, 69, .05));
            color: var(--danger-color);
            border: 2px solid rgba(220, 53, 69, .3);
            box-shadow: 0 4px 15px rgba(220, 53, 69, .2);
        }

        .exam-description {
            color: #6c757d;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            flex: 1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
        }

        .exam-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(23, 162, 184, .03), rgba(23, 162, 184, .08));
            border-radius: 15px;
            border: 1px solid rgba(23, 162, 184, .1);
            margin-bottom: 0;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: 1rem;
            color: var(--dark-text);
            font-weight: 600;
            padding: .5rem;
        }

        .stat-icon {
            color: var(--primary-blue);
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        .exam-footer {
            margin-top: auto;
            padding: 1.5rem 2rem 1.75rem;
            background: linear-gradient(135deg, rgba(248, 249, 250, .8), rgba(255, 255, 255, .9));
            border-top: 1px solid rgba(23, 162, 184, .08);
        }

        .start-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--gradient-end));
            border: none;
            color: #fff;
            padding: 1rem 2rem;
            border-radius: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all .4s cubic-bezier(.4, 0, .2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .75rem;
            font-size: 1rem;
            box-shadow: 0 6px 20px rgba(23, 162, 184, .3);
            width: 100%;
            text-transform: uppercase;
            letter-spacing: .5px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .start-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .2), transparent);
            transition: left .5s;
        }

        .start-btn:hover::before {
            left: 100%;
        }

        .start-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 30px rgba(23, 162, 184, .4);
            color: #fff;
        }

        .timer {
            font-size: 1.8rem;
            font-weight: bold;
            color: #6c757d;
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 12px;
            border: 2px solid #dee2e6;
        }

        .question {
            margin-bottom: 2rem;
        }

        .question-text {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .question-image {
            max-width: 100%;
            max-height: 280px;
            height: auto;
            width: auto;
            border-radius: 12px;
            margin: 20px auto;
            display: block;
            object-fit: contain;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            background: #f8f9fa;
            padding: 10px;
        }

        .attachment-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #e9ecef;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            color: #495057;
            margin: 10px 0;
            font-weight: 500;
        }

        .attachment-link i {
            color: #17a2b8;
        }

        .choice {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all .3s;
            background: #fff;
            font-size: 1.1rem;
            position: relative;
        }

        .choice:hover {
            background: #f8f9fa;
            border-color: #17a2b8;
        }

        .choice.selected {
            background: #dbeafe;
            border-color: #2563eb;
            color: #1e3a8a;
        }

        .choice-prefix {
            font-weight: bold;
            color: #17a2b8;
            font-size: 1.2rem;
            min-width: 30px;
        }

        .review-item {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 5px solid #17a2b8;
        }

        .review-question {
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .review-answer {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0;
            padding: 10px 15px;
            border-radius: 8px;
        }

        .review-answer.your {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .review-answer.correct {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .review-answer.incorrect {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .results-modal .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }

        .results-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--gradient-end));
            color: #fff;
            padding: 2rem;
            text-align: center;
        }

        .results-body {
            padding: 2rem;
            color: var(--dark-text);
        }

        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .score-pass {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .score-fail {
            background: linear-gradient(135deg, #dc3545, #e74c3c);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin: 2rem 0;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #17a2b8;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .history-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .05);
        }

        .history-table th,
        .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .history-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .history-table tr:hover {
            background: #f8f9fa;
        }

        .history-table thead th:first-child {
            border-top-left-radius: 12px;
        }

        .history-table thead th:last-child {
            border-top-right-radius: 12px;
        }

        @media (min-width: 1200px) {
            .exam-card {
                height: 540px;
            }
        }

        @media (max-width: 992px) {
            #examsGrid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            #examsGrid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1200px) {
            .exams-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .exam-card {
                height: 520px;
            }

            .question-image {
                max-height: 250px;
            }
        }

        @media (max-width: 992px) {
            .exam-card {
                height: 500px;
            }

            .page-title {
                font-size: 2.4rem;
            }

            .page-subtitle {
                font-size: 1.1rem;
            }

            .timer {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 768px) {
            .exams-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .exam-stats-bar {
                flex-direction: column;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .question-image {
                max-height: 220px;
            }

            .exam-card {
                height: auto;
                min-height: 460px;
            }

            .page-title {
                font-size: 2rem;
            }

            .page-subtitle {
                font-size: 1rem;
            }

            .page-header {
                padding: 2rem 1.5rem;
            }

            .exam-title {
                font-size: 1.2rem;
            }

            .question-text {
                font-size: 1.15rem;
            }

            .choice {
                font-size: 1rem;
                padding: 0.9rem 1.2rem;
            }

            .modal-dialog {
                margin: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .exam-card {
                min-height: 420px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .question-image {
                max-height: 180px;
            }

            .timer {
                font-size: 1.5rem;
                padding: 8px 15px;
            }

            .history-table {
                font-size: 0.9rem;
            }

            .history-table th,
            .history-table td {
                padding: 10px 8px;
            }

            .page-title {
                font-size: 1.75rem;
            }

            .page-subtitle {
                font-size: 0.95rem;
            }

            .exam-stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .score-circle {
                width: 120px;
                height: 120px;
                font-size: 2rem;
            }

            .stat-box {
                padding: 0.8rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .review-item {
                padding: 1rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .results-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .practice-exams-container {
                padding: 0.5rem;
            }

            .exam-card-content {
                padding: 1.5rem;
            }

            .exam-footer {
                padding: 1.5rem;
            }

            .start-btn {
                padding: 0.9rem 1.5rem;
                font-size: 0.95rem;
            }

            .page-header {
                margin-top: 0;
                padding: 1.5rem 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .page-subtitle {
                font-size: 0.9rem;
            }

            .exam-title {
                font-size: 1.1rem;
                flex-direction: column;
                align-items: flex-start;
            }

            .difficulty-badge {
                font-size: 0.75rem;
                padding: 0.3rem 0.8rem;
            }

            .question-text {
                font-size: 1rem;
            }

            .choice {
                font-size: 0.95rem;
                padding: 0.8rem 1rem;
            }

            .choice-prefix {
                font-size: 1rem;
                min-width: 25px;
            }
        }

        @media (max-width: 360px) {
            .page-title {
                font-size: 1.35rem;
            }

            .exam-card-content {
                padding: 1.2rem;
            }

            .exam-stats {
                padding: 1rem;
            }

            .stat-item {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    
    <div class="practice-exams-container">
        <div class="page-header">
            <h1 class="page-title">Practice Exams</h1>
            <p class="page-subtitle">Take timed tests with questions mimicking the exam format</p>
        </div>

        <div class="exam-stats-bar">
            <div class="user-score">
                <i class="fas fa-chart-line"></i>
                <span>Your average score: <strong id="userAvg">—</strong></span>
            </div>
            <a href="#" class="view-history" data-bs-toggle="modal" data-bs-target="#historyModal">
                View Exam History <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php if ($isGuestUser): ?>
            <div class="alert alert-info text-center mx-auto" style="max-width: 1200px;">
                Guest mode unlocks each <strong>module Post Test</strong> after you complete its matching study module. Practice Tests, Mock Exams, Full Exams, and other exam types require a full account.
            </div>
        <?php endif; ?>
        <div class="topic-tabs">
            <?php foreach ($cats as $i => $cat):
                $slug = strtolower(str_replace(' ', '-', $cat));
            ?>
                <button class="topic-tab <?= $i === 0 ? 'active' : '' ?>" data-topic="<?= $slug ?>">
                    <?= htmlspecialchars($cat) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="exams-grid" id="examsGrid"></div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header primary-blue-header">
                    <h5 class="modal-title" id="detailsModalLabel"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="exam-description-full" id="detailsDescription"></p>
                    <div class="exam-details-stats mt-4">
                        <div class="stat-item"><i class="fas fa-tag"></i> <strong>Topic:</strong> <span id="detailsTopic"></span></div>
                        <div class="stat-item"><i class="fas fa-brain"></i> <strong>Difficulty:</strong> <span id="detailsDifficulty"></span></div>
                        <div class="stat-item"><i class="fas fa-question-circle"></i> <strong>Questions:</strong> <span id="detailsQuestions"></span></div>
                        <div class="stat-item"><i class="fas fa-clock"></i> <strong>Duration:</strong> <span id="detailsDuration"></span> minutes</div>
                        <div class="stat-item"><i class="fa-solid fa-check"></i> <strong>Passing Score:</strong> <span id="detailsPassingScore"></span></div>
                        <div class="stat-item" id="bestScoreRow" style="display:none;"><i class="fas fa-trophy"></i> <strong>Your Best Score:</strong> <span id="detailsBestScore"></span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="startFromDetailsBtn">
                        <span id="startFromDetailsText">Start Exam</span> <i class="fas fa-play"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Locked Content Modal -->
    <div class="modal fade" id="gateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header primary-blue-header">
                    <h5 class="modal-title">Content locked</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="gateModalMessage">
                    Locked content.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="gateGoBackBtn">Go to module</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Modal -->
    <div class="modal fade" id="examModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header primary-blue-header">
                    <h5 class="modal-title" id="examTitle"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div class="timer" id="timer">00:00</div>
                        <div class="text-muted">Question <span id="qCurrent">1</span> of <span id="qTotal">0</span></div>
                    </div>
                    <div id="questionContainer"></div>
                </div>
                <div class="modal-footer flex-wrap gap-2">
                    <button class="btn btn-secondary" id="prevBtn" onclick="prevQuestion()">Previous</button>
                    <button class="btn btn-primary" id="nextBtn" onclick="nextQuestion()">Next</button>
                    <button class="btn btn-success" id="finishBtn" style="display:none" onclick="showReview()">Finish Exam</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Exit Confirmation Modal -->
    <div class="modal fade" id="exitConfirmModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header primary-blue-header">
                    <h5 class="modal-title">Confirm Exit</h5>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <p>If you close/exit the exam, you may need to start again. Are you sure?</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmExitBtn">Confirm Exit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header primary-blue-header">
                    <h5 class="modal-title">Review Your Answers</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reviewContainer" style="max-height:70vh;overflow-y:auto"></div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal" onclick="backToExam()">Back to Exam</button>
                    <button class="btn btn-success" onclick="finalSubmit()">Submit Exam</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Modal -->
    <div class="modal fade" id="resultsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content results-modal">
                <div class="results-header">
                    <h3 id="resultsTitle">Exam Complete!</h3>
                </div>
                <div class="results-body text-center">
                    <div class="score-circle" id="scoreCircle">
                        <div id="finalScore">0%</div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-value" id="statCorrect">0</div>
                            <div class="stat-label">Correct</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value" id="statIncorrect">0</div>
                            <div class="stat-label">Incorrect</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value" id="statUnanswered">0</div>
                            <div class="stat-label">Unanswered</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value" id="statTime">00:00</div>
                            <div class="stat-label">Time Taken</div>
                        </div>
                    </div>
                    <div id="detailedResults"></div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button class="btn btn-primary btn-lg" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header primary-blue-header">
                    <h5 class="modal-title">Your Exam History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="historyBody">
                    <p class="text-center">Loading your history...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Up Modal -->
    <div class="modal fade" id="timeUpModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header primary-blue-header">
                    <h5 class="modal-title">Time's Up!</h5>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-clock fa-3x text-danger mb-3"></i>
                    <p>Your time has ended. The exam will now be submitted automatically.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button class="btn btn-danger" data-bs-dismiss="modal" onclick="finalSubmit()">Submit Now</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const IS_GUEST = <?= $isGuestUser ? 'true' : 'false' ?>;
        const HAS_GUEST_EXAM_PROGRESS = <?= $hasGuestExamProgress ? 'true' : 'false' ?>;
        const GUEST_SELECTED_STUDY_MODULE = <?= json_encode($guestSelectedStudyModule, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const PRELOADED_EXAMS = <?= json_encode($preloadedExams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const GUEST_MODULE_REQUIREMENTS = <?= json_encode($guestModuleRequirements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        let examData = {};
        let currentQ = 0;
        let timerInterval;
        let responses = [];
        let startTime;
        let examEnded = false;
        let currentExamIdForStart = null;
        let examModalInstance = null;
        let isGoingToReview = false;
        let originalQuestions = [];
        let questionMapping = [];
        let guestAllowedExamIds = new Set();

        function buildGuestExamUrl(action) {
            const u = new URL(window.location.href);
            u.searchParams.set('guest_exam_action', action);
            if (!u.searchParams.has('page')) {
                u.searchParams.set('page', 'practical-exams');
            }
            return u.toString();
        }

        async function syncGuestExamProgressAfterSignup() {
            if (IS_GUEST || !HAS_GUEST_EXAM_PROGRESS) return;
            try {
                await fetch(buildGuestExamUrl('sync'), {
                    method: 'POST'
                });
            } catch (err) {
                console.error('Failed to sync guest exam progress:', err);
            }
        }

        function shuffleArray(array) {
            const newArray = [...array];
            for (let i = newArray.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
            }
            return newArray;
        }

        function showModal(id) {
            new bootstrap.Modal(document.getElementById(id)).show();
        }

        const CATEGORIES = [
            'Analytical Chemistry',
            'Organic Chemistry',
            'Physical Chemistry',
            'Inorganic Chemistry',
            'BioChemistry'
        ];

        function slugify(text) {
            return text.toLowerCase().replace(/\s+/g, '-');
        }

        function getTitleCategoryPrefix(title) {
            const match = String(title || '').match(
                /^(.*?)\s*-\s*(?:Practice\s*Test\s*\d+|(?:FULL\s+)?MOCK\s+EXAM|FULL\s+EXAM|POST TEST.*)$/i
            );
            return match ? match[1].trim() : null;
        }

        let ALL_EXAMS = [];
        let EXAM_HISTORY = [];
        let BEST_SCORE_MAP = new Map();

        function examKey(title, category) {
            return `${String(title || '').trim().toLowerCase()}||${String(category || '').trim().toLowerCase()}`;
        }

        async function loadExamHistory() {
            if (IS_GUEST) {
                try {
                    const res = await fetch('../partial/exam_history.php', {
                        cache: 'no-store'
                    });
                    const attempts = await res.json();

                    EXAM_HISTORY = Array.isArray(attempts) ? attempts : [];
                    BEST_SCORE_MAP.clear();
                    EXAM_HISTORY.forEach(item => {
                        const examId = Number(item.exam_id);
                        const score = Number(item.best_score ?? item.score ?? item.last_score ?? 0);
                        if (!Number.isFinite(examId) || !Number.isFinite(score)) return;
                        BEST_SCORE_MAP.set(examId, Math.round(score));
                    });
                } catch (err) {
                    console.error('Failed to load guest exam history:', err);
                    EXAM_HISTORY = [];
                    BEST_SCORE_MAP.clear();
                }
                return;
            }

            try {
                const res = await fetch('../partial/exam_history.php');
                const data = await res.json();

                EXAM_HISTORY = Array.isArray(data) ? data : [];
                BEST_SCORE_MAP.clear();

                EXAM_HISTORY.forEach(item => {
                    const examId = Number(item.exam_id);
                    const score = Number(item.score);

                    if (!Number.isFinite(examId) || !Number.isFinite(score)) return;

                    const existing = BEST_SCORE_MAP.get(examId);
                    if (existing === undefined || score > existing) {
                        BEST_SCORE_MAP.set(examId, Math.round(score));
                    }
                });
            } catch (err) {
                console.error('Failed to load exam history:', err);
                EXAM_HISTORY = [];
                BEST_SCORE_MAP.clear();
            }
        }

        function getBestScoreFromHistory(title, category) {
            const key = examKey(title, category);
            const score = BEST_SCORE_MAP.get(key);

            if (score === undefined || score === null || Number.isNaN(score)) {
                return null;
            }

            return Math.round(score);
        }

        async function loadExams() {
            await syncGuestExamProgressAfterSignup();
            await loadExamHistory();

            if (IS_GUEST) {
                ALL_EXAMS = Array.isArray(PRELOADED_EXAMS) ? PRELOADED_EXAMS : [];
                await renderExamsByCategory(CATEGORIES[0]);
                return;
            }

            try {
                const r = await fetch('../partial/exam_list.php');
                const payload = await r.json();
                const fromApi = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
                ALL_EXAMS = fromApi.length ? fromApi : (Array.isArray(PRELOADED_EXAMS) ? PRELOADED_EXAMS : []);
                await renderExamsByCategory(CATEGORIES[0]);
            } catch (err) {
                console.error('Failed to load exams:', err);
                ALL_EXAMS = Array.isArray(PRELOADED_EXAMS) ? PRELOADED_EXAMS : [];
                if (!ALL_EXAMS.length) {
                    document.getElementById('examsGrid').innerHTML = `
                        <div class="text-center col-12">
                            <h4>Failed to load exams.</h4>
                        </div>`;
                    return;
                }
                await renderExamsByCategory(CATEGORIES[0]);
            }
        }

        const _progressCache = new Map();

        async function _fetchProgressData(category) {
            const key = norm(category || '');
            if (_progressCache.has(key)) return _progressCache.get(key);

            const url = key
                ? `../partial/get_progress.php?category=${encodeURIComponent(category)}`
                : `../partial/get_progress.php`;

            const resp = await fetch(url);
            const json = await resp.json();
            const data = Array.isArray(json.data) ? json.data : [];
            _progressCache.set(key, data);
            return data;
        }

        async function _isPostTestLocked(title, category) {
            if (!isPostTestTitle(title)) return false;

            const moduleCode = getModuleCodeFromPostTestTitle(title);
            if (!moduleCode) return true;

            const variants = moduleCodeVariants(moduleCode);
            if (!variants.length) return true;

            try {
                const data = await _fetchProgressData(category);

                const regexes = variants.map(v => ([
                    new RegExp(`^\\s*(?:Module\\s+)?${escapeRegExp(v)}\\b`, 'i'),
                    new RegExp(`\\bModule\\s+${escapeRegExp(v)}\\b`, 'i'),
                ])).flat();

                const matched = data.filter(d => {
                    const t = String(d?.title || d?.module || d?.name || '');
                    return regexes.some(re => re.test(t));
                });

                if (matched.length === 0) return true;

                const isComplete = (row) => {
                    const files = Array.isArray(row?.files) ? row.files : [];
                    if (files.length) {
                        return files.every(f => toPercentNumber(f?.progress) >= 100);
                    }

                    const p = toPercentNumber(row?.progress ?? row?.completion ?? row?.percent ?? row?.percentage);
                    return p >= 100;
                };

                return !matched.some(isComplete);
            } catch (e) {
                return true;
            }
        }

        function _computeBestGrade(exam) {
            const score = BEST_SCORE_MAP.get(Number(exam?.id));
            return Number.isFinite(score) ? Math.round(score) : null;
        }

        function _computePassingGrade(exam) {
            const raw = (exam?.passing_score !== undefined && exam?.passing_score !== null && exam?.passing_score !== '') ?
                Number(exam.passing_score) :
                null;
            if (raw === null || Number.isNaN(raw) || raw <= 0) return null;
            return Math.round(raw);
        }

        function isPracticeTestTitle(title) {
            return /Practice\s*Test\s*\d+/i.test(String(title || ''));
        }

        function getPracticeTestNumber(title) {
            const match = String(title || '').match(/Practice\s*Test\s*(\d+)/i);
            return match ? parseInt(match[1], 10) : null;
        }

        function isMockTestTitle(title) {
            return /MOCK\s+EXAM/i.test(String(title || ''));
        }

        function isFullExamTitle(title) {
            return /FULL\s+EXAM/i.test(String(title || ''));
        }

        function isUnlockableExamType(title) {
            return isPostTestTitle(title) || isPracticeTestTitle(title) || isMockTestTitle(title);
        }

        function normalizeGuestModuleCode(code) {
            return String(code || '').trim().toUpperCase();
        }

        function getGuestStudyProgressFromBrowser() {
            try {
                return JSON.parse(sessionStorage.getItem('guest_progress') || '{}') || {};
            } catch (e) {
                return {};
            }
        }

        function getGuestModuleRequirement(category, moduleCode) {
            const wantedCat = norm(category || '');
            const variants = moduleCodeVariants(moduleCode).map(v => normalizeGuestModuleCode(v));
            const wantedDirect = normalizeGuestModuleCode(moduleCode);
            if (wantedDirect && !variants.includes(wantedDirect)) variants.push(wantedDirect);

            if (!wantedCat || !variants.length) return null;

            return (Array.isArray(GUEST_MODULE_REQUIREMENTS) ? GUEST_MODULE_REQUIREMENTS : [])
                .find(item => {
                    if (norm(item?.category || '') !== wantedCat) return false;
                    const itemModule = normalizeGuestModuleCode(item?.module || '');
                    return variants.includes(itemModule);
                }) || null;
        }

        function isGuestModuleStudyComplete(category, moduleCode) {
            if (!IS_GUEST) return true;

            const requirement = getGuestModuleRequirement(category, moduleCode);
            if (!requirement) return false;

            const fileIds = Array.isArray(requirement.file_ids)
                ? requirement.file_ids.map(id => String(id))
                : [];

            if (!fileIds.length) return false;

            const progressMap = getGuestStudyProgressFromBrowser();
            return fileIds.every(fileId => toPercentNumber(progressMap[fileId]) >= 100);
        }

        function getGuestExamLockReason(exam, category) {
            if (!IS_GUEST) return null;

            if (!isPostTestTitle(exam?.title)) {
                return 'full_account_required';
            }

            const moduleCode = getModuleCodeFromPostTestTitle(exam?.title);
            if (!moduleCode) return 'missing_module';

            if (!isGuestModuleStudyComplete(category || exam?.category, moduleCode)) {
                return 'module_incomplete';
            }

            return null;
        }

        function resolveGuestAllowedExamIds(exams, category) {
            const allowed = new Set();
            if (!IS_GUEST) return allowed;

            // Guest unlock rule: a module POST TEST is unlocked only when its corresponding
            // study module files are all completed in guest sessionStorage progress.
            exams.forEach(e => {
                if (getGuestExamLockReason(e, category) === null) {
                    allowed.add(Number(e.id));
                }
            });

            return allowed;
        }

        function isGuestAllowedExam(examId) {
            if (!IS_GUEST) return true;
            return guestAllowedExamIds instanceof Set && guestAllowedExamIds.has(Number(examId));
        }

        function isExamPassed(exam) {
            const best = _computeBestGrade(exam);
            const passing = _computePassingGrade(exam);

            if (best === null || passing === null) return false;
            return best >= passing;
        }

        async function renderExamsByCategory(category) {
            const grid = document.getElementById('examsGrid');
            grid.innerHTML = '';

            const exams = ALL_EXAMS.filter(e => {
                const dbCategory = String(e.category || '').trim();
                const titleCategory = getTitleCategoryPrefix(e.title);

                if (titleCategory) {
                    return dbCategory === category && titleCategory === category;
                }

                return dbCategory === category;
            });

            if (!exams.length) {
                grid.innerHTML = `
                    <div class="text-center col-12">
                        <h4>No exams available for this category.</h4>
                    </div>`;
                document.getElementById('userAvg').textContent = '—';
                return;
            }

            let totalScore = 0;
            let totalAttempts = 0;
            guestAllowedExamIds = resolveGuestAllowedExamIds(exams, category);

            const postTestLockedMap = new Map();
            if (IS_GUEST) {
                exams.forEach(e => postTestLockedMap.set(e.id, false));
            } else {
                await Promise.all(exams.map(async (e) => {
                    const locked = await _isPostTestLocked(e.title, category);
                    postTestLockedMap.set(e.id, locked);
                }));
            }

            const postTests = exams.filter(e => isPostTestTitle(e.title));
            const practiceTests = exams
                .filter(e => isPracticeTestTitle(e.title))
                .sort((a, b) => (getPracticeTestNumber(a.title) || 0) - (getPracticeTestNumber(b.title) || 0));

            const allPostTestsPassed = postTests.length === 0
                ? true
                : postTests.every(e => {
                    const locked = !!postTestLockedMap.get(e.id);
                    if (locked) return false;
                    return isExamPassed(e);
                });

            const practiceLockedMap = new Map();

            for (let i = 0; i < practiceTests.length; i++) {
                const exam = practiceTests[i];
                const number = getPracticeTestNumber(exam.title);

                let locked = true;

                if (number === 1) {
                    locked = !allPostTestsPassed;
                } else {
                    const prevExam = practiceTests.find(p => getPracticeTestNumber(p.title) === number - 1);
                    locked = !prevExam || !isExamPassed(prevExam);
                }

                practiceLockedMap.set(exam.id, locked);
            }

            const prerequisiteExams = exams.filter(e => isPostTestTitle(e.title) || isPracticeTestTitle(e.title));
            const allPrerequisiteExamsPassed = prerequisiteExams.length > 0 && prerequisiteExams.every(e => {
                if (isPostTestTitle(e.title)) {
                    if (postTestLockedMap.get(e.id)) return false;
                    return isExamPassed(e);
                }

                if (isPracticeTestTitle(e.title)) {
                    if (practiceLockedMap.get(e.id)) return false;
                    return isExamPassed(e);
                }

                return false;
            });

            const fragment = document.createDocumentFragment();

            for (const e of exams) {
                const difficulty = (e.difficulty || 'Beginner');
                const badge =
                    difficulty === 'Beginner' ? 'success' :
                    difficulty === 'Intermediate' ? 'warning' :
                    'danger';

                let shortDesc = e.description || 'No description available.';
                if (shortDesc.length > 120) shortDesc = shortDesc.slice(0, 117) + '...';

                const safeTitle = escapeAttr(e.title);
                const safeDesc = escapeAttr(e.description || '');
                const safeTopic = escapeAttr(e.topic || 'Not specified');
                const totalItems = e.total_questions !== undefined && e.total_questions !== null && e.total_questions !== '' ? Number(e.total_questions) : 0;
                const passingGrade = _computePassingGrade(e);
                const bestGrade = _computeBestGrade(e);
                const hasAttempt = (bestGrade !== null);

                if (hasAttempt && bestGrade !== null) {
                    totalScore += Number(bestGrade) || 0;
                    totalAttempts++;
                }

                const isPostTest = isPostTestTitle(e.title);
                const isPractice = isPracticeTestTitle(e.title);
                const isMock = isMockTestTitle(e.title);
                const isFullExam = isFullExamTitle(e.title);

                let isLocked = true;
                if (IS_GUEST) {
                    isLocked = !isGuestAllowedExam(e.id);
                } else {
                    if (isPostTest) {
                        isLocked = !!postTestLockedMap.get(e.id);
                    } else if (isPractice) {
                        isLocked = !!practiceLockedMap.get(e.id);
                    } else if (isMock) {
                        isLocked = !allPrerequisiteExamsPassed;
                    }
                }

                const isPassed = (!isLocked && bestGrade !== null && passingGrade !== null && bestGrade >= passingGrade);

                examMetaMap.set(e.id, {
                    title: e.title,
                    category,
                    moduleCode: getModuleCodeFromPostTestTitle(e.title),
                    practiceNo: getPracticeTestNumber(e.title),
                    isPostTest,
                    isPractice,
                    isMock,
                    isFullExam
                });

                const div = document.createElement('div');
                div.className = 'exam-card' + (isLocked ? ' exam-locked' : '') + (isPassed ? ' exam-passed' : '');
                div.style.cursor = isLocked ? 'not-allowed' : 'pointer';

                div.onclick = () => {
                    if (isLocked) return;
                    openDetailsModal(
                        e.id,
                        safeTitle,
                        safeDesc,
                        difficulty,
                        totalItems,
                        e.duration_minutes,
                        passingGrade,
                        safeTopic,
                        bestGrade,
                        category
                    );
                };

                const stateIconHtml = isLocked
                    ? `<span class="exam-state locked" title="Locked"><i class="fas fa-lock"></i></span>`
                    : (isPassed
                        ? `<span class="exam-state passed" title="Passed"><i class="fas fa-check-circle"></i></span>`
                        : '');

                const guestLockReason = IS_GUEST ? getGuestExamLockReason(e, category) : null;
                const startBtnHtml = isLocked
                    ? `${guestLockReason === 'module_incomplete' ? 'Complete Module First' : 'Locked'} <i class="fas fa-lock"></i>`
                    : `${IS_GUEST ? 'Take Guest Exam' : (bestGrade !== null ? 'Retake' : 'Take')} <i class="fas fa-play"></i>`;

                div.innerHTML = `
                    <div class="exam-card-content">
                        <div class="exam-header">
                            <h3 class="exam-title"><span class="exam-title-text">${e.title}</span>${stateIconHtml}</h3>
                            <span class="difficulty-badge ${badge}">${difficulty}</span>
                        </div>
                        <p class="exam-description">${shortDesc}</p>
                        <div class="exam-stats">
                            <div class="stat-item">
                                <i class="fas fa-question-circle stat-icon"></i>
                                <span>${totalItems} Questions</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-clock stat-icon"></i>
                                <span>${e.duration_minutes} Minutes</span>
                            </div>
                        </div>
                    </div>
                    <div class="exam-footer">
                        <div class="start-btn${isLocked ? ' disabled' : ''}">
                            ${startBtnHtml}
                        </div>
                        ${
                            (!isLocked && bestGrade !== null)
                                ? `<small class="d-block text-center ${isPassed ? 'text-success' : 'text-muted'} mt-2">
                                    Your best: ${bestGrade}%${isPassed ? ' ✓' : ''}
                                  </small>`
                                : ''
                        }
                    </div>
                `;

                div.querySelector('.start-btn').onclick = ev => {
                    ev.stopPropagation();
                    if (isLocked) return;
                    div.onclick();
                };

                fragment.appendChild(div);
            }

            grid.appendChild(fragment);

            document.getElementById('userAvg').textContent =
                totalAttempts ? (totalScore / totalAttempts).toFixed(2) + '%' : '—';
        }
        function escapeAttr(str) {
            return String(str)
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'")
                .replace(/"/g, '&quot;')
                .replace(/\n/g, '\\n')
                .replace(/\r/g, '');
        }

        let currentExamMeta = null; // { id, title, category, moduleCode, practiceNo, isPostTest, isPractice, isMock, isFullExam }
        const examMetaMap = new Map(); // examId -> richer exam metadata
        let gateTarget = null;

        function showGate(message, target) {
            const el = document.getElementById('gateModalMessage');
            if (el) el.textContent = message || 'Locked content.';
            gateTarget = target || null;
            const mEl = document.getElementById('gateModal');
            const m = bootstrap.Modal.getInstance(mEl) || new bootstrap.Modal(mEl);
            m.show();
        }

        function isPostTestTitle(title) {
            return /POST TEST\s*\(Module\s+/i.test(String(title || ''));
        }

        function getModuleCodeFromPostTestTitle(title) {
            const m = String(title || '').match(/POST TEST\s*\(Module\s+([A-Za-z0-9IVXLCDM]+)\)/i);
            return m ? m[1].trim() : null;
        }

        function escapeRegExp(str) {
            return String(str || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function norm(str) {
            return String(str || '').trim().toLowerCase().replace(/\s+/g, ' ');
        }

        function toPercentNumber(val) {
            if (val === null || val === undefined) return Number.NaN;
            if (typeof val === 'string') {
                const cleaned = val.replace('%', '').trim();
                const n = parseFloat(cleaned);
                return Number.isFinite(n) ? n : Number.NaN;
            }
            const n = Number(val);
            return Number.isFinite(n) ? n : Number.NaN;
        }

        function romanToInt(roman) {
            const map = { I: 1, V: 5, X: 10, L: 50, C: 100, D: 500, M: 1000 };
            const s = String(roman || '').toUpperCase().trim();
            if (!s) return 0;
            let total = 0;
            let prev = 0;
            for (let i = s.length - 1; i >= 0; i--) {
                const cur = map[s[i]] || 0;
                if (cur < prev) total -= cur;
                else {
                    total += cur;
                    prev = cur;
                }
            }
            return total;
        }

        function intToRoman(num) {
            let n = Number(num) || 0;
            if (n <= 0) return '';
            const vals = [
                [1000, 'M'], [900, 'CM'], [500, 'D'], [400, 'CD'],
                [100, 'C'], [90, 'XC'], [50, 'L'], [40, 'XL'],
                [10, 'X'], [9, 'IX'], [5, 'V'], [4, 'IV'], [1, 'I'],
            ];
            let out = '';
            for (const [v, sym] of vals) {
                while (n >= v) {
                    out += sym;
                    n -= v;
                }
            }
            return out;
        }

        function moduleCodeVariants(code) {
            const c = String(code || '').trim();
            if (!c) return [];

            const set = new Set([c]);

            if (/^[A-Z]$/i.test(c)) {
                const n = c.toUpperCase().charCodeAt(0) - 64;
                if (n >= 1 && n <= 26) {
                    const roman = intToRoman(n);
                    if (roman) set.add(roman);
                }
            }

            if (/^[IVXLCDM]+$/i.test(c)) {
                const n = romanToInt(c);
                if (n >= 1 && n <= 26) {
                    set.add(String.fromCharCode(64 + n));
                }
            }

            return Array.from(set);
        }

        async function isModuleProgressComplete(moduleCode, category) {
            if (!moduleCode) return false;

            const wantedCode = String(moduleCode || '').trim();
            const wantedCat = norm(category || '');

            const url = wantedCat
                ? `../partial/get_progress.php?category=${encodeURIComponent(category)}`
                : `../partial/get_progress.php`;

            try {
                const resp = await fetch(url);
                const json = await resp.json();
                const data = Array.isArray(json.data) ? json.data : [];

                const reStart = new RegExp(`^\\s*(?:Module\\s+)?${escapeRegExp(wantedCode)}\\b`, 'i');
                const reAnywhere = new RegExp(`\\bModule\\s+${escapeRegExp(wantedCode)}\\b`, 'i');

                const matched = data.filter(mod => {
                    const modCat = norm(mod?.category || '');
                    if (wantedCat && modCat) {
                        if (modCat !== wantedCat) return false;
                    }

                    const title = String(mod?.title || '');
                    return reStart.test(title) || reAnywhere.test(title);
                });

                if (matched.length === 0) return false;

                return matched.some(mod => {
                    const files = Array.isArray(mod.files) ? mod.files : [];
                    if (files.length === 0) return false;
                    return files.every(f => Number(f.progress || 0) >= 100);
                });
            } catch (e) {
                console.error('Failed to check progress', e);
                return false;
            }
        }

        document.getElementById('gateGoBackBtn')?.addEventListener('click', () => {
            const mEl = document.getElementById('gateModal');
            bootstrap.Modal.getInstance(mEl)?.hide();

            if (!gateTarget || !gateTarget.category || !gateTarget.moduleCode) return;

            try {
                sessionStorage.setItem('chemEase_open_module', JSON.stringify(gateTarget));
            } catch (e) { }

            window.location.href = 'index?page=study-materials';
        });

        function openDetailsModal(id, title, description, difficulty, questions, duration, passingScore, topic, bestScore, category) {
            document.getElementById('detailsModalLabel').textContent = title;
            document.getElementById('detailsDescription').textContent = description || 'No description available.';
            document.getElementById('detailsTopic').textContent = topic;
            document.getElementById('detailsDifficulty').textContent = difficulty;
            document.getElementById('detailsQuestions').textContent = questions;
            document.getElementById('detailsDuration').textContent = duration;
            document.getElementById('detailsPassingScore').textContent =
                (passingScore !== null && passingScore !== undefined && passingScore !== 'null') ?
                    `${passingScore}%` :
                    '—';

            if (bestScore !== null && bestScore !== 'null' && bestScore !== 'undefined' && bestScore !== 0 && bestScore !== '0') {
                document.getElementById('detailsBestScore').textContent = `${bestScore}%`;
                document.getElementById('bestScoreRow').style.display = 'block';
                document.getElementById('startFromDetailsText').textContent = 'Retake Exam';
            } else {
                document.getElementById('bestScoreRow').style.display = 'none';
                document.getElementById('startFromDetailsText').textContent = 'Start Exam';
            }

            currentExamIdForStart = id;
            currentExamMeta = {
                id,
                title,
                category,
                moduleCode: getModuleCodeFromPostTestTitle(title),
                practiceNo: getPracticeTestNumber(title),
                isPostTest: isPostTestTitle(title),
                isPractice: isPracticeTestTitle(title),
                isMock: isMockTestTitle(title),
                isFullExam: isFullExamTitle(title)
            };

            showModal('detailsModal');
        }

        document.getElementById('startFromDetailsBtn').addEventListener('click', function() {
            if (!currentExamIdForStart) return;
            bootstrap.Modal.getInstance(document.getElementById('detailsModal'))?.hide();
            redirectToExam(currentExamIdForStart);
        });

        async function redirectToExam(examId) {
            if (!examId) {
                alert("Invalid exam.");
                return;
            }

            const meta = (currentExamMeta && currentExamMeta.id === examId)
                ? currentExamMeta
                : (examMetaMap.get(examId) || null);

            const exam = ALL_EXAMS.find(e => Number(e.id) === Number(examId));
            if (!exam || !meta) {
                alert("Exam metadata not found.");
                return;
            }

            const category = meta.category || exam.category;
            const exams = ALL_EXAMS.filter(e => {
                const dbCategory = String(e.category || '').trim();
                const titleCategory = getTitleCategoryPrefix(e.title);

                if (titleCategory) {
                    return dbCategory === category && titleCategory === category;
                }

                return dbCategory === category;
            });

            if (IS_GUEST) {
                guestAllowedExamIds = resolveGuestAllowedExamIds(exams, category);
                const lockReason = getGuestExamLockReason(exam, category);

                if (lockReason === 'full_account_required') {
                    showGate(
                        "Guest mode can take module Post Tests only. Practice Tests, Mock Exams, Full Exams, and other exam types require a full account.",
                        { category }
                    );
                    return;
                }

                if (lockReason === 'module_incomplete') {
                    showGate(
                        "This Post Test is locked. Complete the matching study module first, then come back to take the exam.",
                        {
                            category,
                            moduleCode: getModuleCodeFromPostTestTitle(exam.title)
                        }
                    );
                    return;
                }

                if (lockReason !== null || !isGuestAllowedExam(exam.id)) {
                    showGate(
                        "This exam is still locked in guest mode.",
                        { category }
                    );
                    return;
                }

                startExam(examId);
                return;
            }

            const postTestLockedMap = new Map();
            await Promise.all(exams.map(async (e) => {
                const locked = await _isPostTestLocked(e.title, category);
                postTestLockedMap.set(e.id, locked);
            }));

            const postTests = exams.filter(e => isPostTestTitle(e.title));
            const practiceTests = exams
                .filter(e => isPracticeTestTitle(e.title))
                .sort((a, b) => (getPracticeTestNumber(a.title) || 0) - (getPracticeTestNumber(b.title) || 0));

            const allPostTestsPassed = postTests.length === 0
                ? true
                : postTests.every(e => {
                    const locked = !!postTestLockedMap.get(e.id);
                    if (locked) return false;
                    return isExamPassed(e);
                });

            const practiceLockedMap = new Map();

            for (let i = 0; i < practiceTests.length; i++) {
                const item = practiceTests[i];
                const number = getPracticeTestNumber(item.title);

                let locked = true;

                if (number === 1) {
                    locked = !allPostTestsPassed;
                } else {
                    const prevExam = practiceTests.find(p => getPracticeTestNumber(p.title) === number - 1);
                    locked = !prevExam || !isExamPassed(prevExam);
                }

                practiceLockedMap.set(item.id, locked);
            }

            const prerequisiteExams = exams.filter(e => isPostTestTitle(e.title) || isPracticeTestTitle(e.title));
            const allPrerequisiteExamsPassed = prerequisiteExams.length > 0 && prerequisiteExams.every(e => {
                if (isPostTestTitle(e.title)) {
                    if (postTestLockedMap.get(e.id)) return false;
                    return isExamPassed(e);
                }

                if (isPracticeTestTitle(e.title)) {
                    if (practiceLockedMap.get(e.id)) return false;
                    return isExamPassed(e);
                }

                return false;
            });

            if (isPostTestTitle(exam.title)) {
                if (postTestLockedMap.get(exam.id)) {
                    const moduleCode = getModuleCodeFromPostTestTitle(exam.title);
                    showGate(
                        "Locked content. You must finish the required module or lesson first before taking this post test.",
                        { category, moduleCode }
                    );
                    return;
                }

                if (IS_GUEST) {
                    startExam(examId);
                } else {
                    window.location.href = `take-exam.php?exam_id=${examId}`;
                }
                return;
            }

            if (isPracticeTestTitle(exam.title)) {
                if (practiceLockedMap.get(exam.id)) {
                    showGate(
                        "This practice test is still locked. Pass all post tests first, then pass the previous practice test in order.",
                        { category }
                    );
                    return;
                }

                if (IS_GUEST) {
                    startExam(examId);
                } else {
                    window.location.href = `take-exam.php?exam_id=${examId}`;
                }
                return;
            }

            if (isMockTestTitle(exam.title)) {
                if (!allPrerequisiteExamsPassed) {
                    showGate(
                        "This mock exam is locked. Pass all required post tests and practice tests in this category first before taking the mock exam.",
                        { category }
                    );
                    return;
                }

                if (IS_GUEST) {
                    startExam(examId);
                } else {
                    window.location.href = `take-exam.php?exam_id=${examId}`;
                }
                return;
            }

            showGate(
                "This exam is locked. Only POST TEST, Practice Test, and Mock Exam types can be unlocked here.",
                { category }
            );
        }
        function initExamSessionFromPayload(data) {
            if (!data || !data.exam || !Array.isArray(data.questions)) {
                showError('Exam not found');
                return;
            }

            originalQuestions = data.questions;

            const shuffledQuestions = shuffleArray(data.questions);
            questionMapping = shuffledQuestions.map(q => {
                const originalIndex = originalQuestions.findIndex(oq => oq.id === q.id);
                return {
                    shuffledQuestion: {
                        ...q,
                        choices: shuffleArray(q.choices)
                    },
                    originalIndex: originalIndex
                };
            });

            examData = {
                ...data,
                questions: questionMapping.map(qm => qm.shuffledQuestion)
            };

            currentQ = 0;
            examEnded = false;
            isGoingToReview = false;
            responses = Array(examData.questions.length).fill(null);

            document.getElementById('examTitle').textContent = data.exam.title;
            document.getElementById('qTotal').textContent = examData.exam.total_questions;

            startTime = Date.now();
            startTimer((Number(data.exam.duration_minutes) || 0) * 60);
            showQuestion();

            examModalInstance = bootstrap.Modal.getInstance(document.getElementById('examModal')) ||
                new bootstrap.Modal(document.getElementById('examModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
            examModalInstance.show();
        }

        function startExam(examId) {
            const url = `../partial/exam_start.php?exam_id=${encodeURIComponent(examId)}`;

            fetch(url, { cache: 'no-store' })
                .then(async r => {
                    const data = await r.json().catch(() => null);
                    if (!r.ok || !data || data.success === false) {
                        throw new Error(data?.error || data?.message || 'Unable to start exam.');
                    }
                    return data;
                })
                .then(data => {
                    initExamSessionFromPayload(data);
                })
                .catch(err => showError(err.message || 'Unable to start exam. Please try again.'));
        }

        function startTimer(seconds) {
            clearInterval(timerInterval);
            let time = seconds;
            document.getElementById('timer').textContent = formatTime(time);

            timerInterval = setInterval(() => {
                if (examEnded) {
                    clearInterval(timerInterval);
                    return;
                }
                time--;
                document.getElementById('timer').textContent = formatTime(time);
                if (time <= 0) {
                    clearInterval(timerInterval);
                    examEnded = true;
                    showModal('timeUpModal');
                }
            }, 1000);
        }

        function formatTime(seconds) {
            const m = String(Math.floor(seconds / 60)).padStart(2, '0');
            const s = String(seconds % 60).padStart(2, '0');
            return `${m}:${s}`;
        }

        function showQuestion() {
            if (examEnded) return;

            const q = examData.questions[currentQ];
            document.getElementById('qCurrent').textContent = currentQ + 1;

            let html = `<div class="question"><div class="question-text">${q.text}</div>`;

            if (q.image_path) {
                html += `<img src="../${q.image_path}" class="question-image" alt="Question image" onerror="this.style.display='none'">`;
            }

            if (q.attachment_path) {
                const filename = q.attachment_path.split('/').pop();
                html += `<a href="../${q.attachment_path}" target="_blank" class="attachment-link">
                    <i class="fas fa-paperclip"></i> ${filename}
                </a>`;
            }

            q.choices.forEach((c, i) => {
                const letter = String.fromCharCode(65 + i);
                const selected = responses[currentQ] === c.id;
                let cleanText = c.text;
                const prefixPattern = new RegExp(`^${letter}\\.\\s*`, 'i');
                cleanText = cleanText.replace(prefixPattern, '').trim();

                html += `
                <div class="choice ${selected ? 'selected' : ''}" onclick="${examEnded ? '' : 'selectChoice(' + i + ')'}">
                    <span class="choice-prefix">${letter}.</span> ${cleanText}
                </div>`;
            });

            html += `</div>`;
            document.getElementById('questionContainer').innerHTML = html;

            document.getElementById('prevBtn').style.display = currentQ === 0 ? 'none' : 'inline-block';
            const isLast = currentQ === examData.questions.length - 1;
            document.getElementById('nextBtn').style.display = isLast ? 'none' : 'inline-block';
            document.getElementById('finishBtn').style.display = isLast ? 'inline-block' : 'none';
        }

        function selectChoice(choiceIndex) {
            if (examEnded) return;

            const q = examData.questions[currentQ];
            const selectedId = q.choices[choiceIndex].id;
            responses[currentQ] = responses[currentQ] === selectedId ? null : selectedId;
            showQuestion();
        }

        function nextQuestion() {
            if (currentQ < examData.questions.length - 1) {
                currentQ++;
                showQuestion();
            }
        }

        function prevQuestion() {
            if (currentQ > 0) {
                currentQ--;
                showQuestion();
            }
        }

        function backToExam() {
            showModal('examModal');
        }

        function showReview() {
            if (examEnded) return finalSubmit();
            finalSubmit();
        }

        function finalSubmit() {
            clearInterval(timerInterval);
            examEnded = true;

            const timeTaken = Math.floor((Date.now() - startTime) / 1000);
            const minutes = String(Math.floor(timeTaken / 60)).padStart(2, '0');
            const seconds = String(timeTaken % 60).padStart(2, '0');
            const timeLabel = minutes + ':' + seconds;

            if (IS_GUEST) {
                const payload = {
                    attempt_id: examData.attempt_id,
                    responses: examData.questions.map((q, i) => ({
                        question_id: q.id,
                        answer_id: responses[i] || null
                    }))
                };

                fetch('../partial/exam_submit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                    .then(async r => {
                        const res = await r.json().catch(() => null);
                        if (!r.ok || !res || res.success === false) {
                            throw new Error(res?.error || res?.message || 'Error submitting guest exam.');
                        }
                        return res;
                    })
                    .then(res => {
                        showResults(res, timeLabel);
                    })
                    .catch(err => showError(err.message || 'Error submitting guest exam. Please try again.'));
                return;
            }

            const payload = {
                attempt_id: examData.attempt_id,
                responses: examData.questions.map((q, i) => ({
                    question_id: q.id,
                    answer_id: responses[i] || null
                }))
            };

            fetch('../partial/exam_submit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showResults(res, timeLabel);
                    } else {
                        showError('Error submitting exam. Please try again.');
                    }
                });
        }

        function showResults(data, timeTaken) {
            const displayScore = Number.isFinite(Number(data.grade))
                ? Math.round(Number(data.grade))
                : Math.round(Number(data.score || 0));
            const passingScore = Number(data.passing_score || 0);
            const passed = passingScore > 0 ? displayScore >= passingScore : !!data.passed;
            const statusText = passed ? 'Passed' : 'Failed';
            document.getElementById('finalScore').innerHTML = `${displayScore}%<div style="font-size: 1rem; margin-top: 0.5rem; font-weight: 600;">${statusText}</div>`;
            document.getElementById('scoreCircle').className = 'score-circle ' + (passed ? 'score-pass' : 'score-fail');
            document.getElementById('resultsTitle').textContent = passed ? 'Congratulations! You Passed!' : 'Exam Completed';

            const correctCount = Number(data.correct || 0);
            const totalCount = Number(data.total || (Array.isArray(examData.questions) ? examData.questions.length : 0));
            const unansweredCount = Number.isFinite(Number(data.unanswered))
                ? Number(data.unanswered)
                : responses.filter(r => r === null).length;
            const incorrectCount = Number.isFinite(Number(data.incorrect))
                ? Number(data.incorrect)
                : Math.max(0, totalCount - correctCount - unansweredCount);

            document.getElementById('statCorrect').textContent = correctCount;
            document.getElementById('statIncorrect').textContent = incorrectCount;
            document.getElementById('statUnanswered').textContent = unansweredCount;
            document.getElementById('statTime').textContent = timeTaken;

            let detailed = '<h4 class="mt-4 mb-3 text-start">Detailed Results</h4>';

            if (Array.isArray(data.details) && data.details.length) {
                detailed += data.details.map((item, i) => {
                    const isCorrect = !!item.is_correct;
                    const userText = item.user_answer_text ? String(item.user_answer_text).replace(/^[A-D]\.\s*/i, '').trim() : null;
                    const correctText = item.correct_answer_text ? String(item.correct_answer_text).replace(/^[A-D]\.\s*/i, '').trim() : 'N/A';
                    const qText = item.question_text || '';

                    return `<div class="mb-4 p-3 border rounded text-start">
                        <div class="fw-bold mb-2">Question ${i + 1}</div>
                        <div class="mb-2">${qText}</div>
                        <div class="review-answer ${isCorrect ? 'correct' : 'incorrect'} your">
                            <strong>Your Answer:</strong> ${userText || 'Not answered'}
                            ${userText ? (isCorrect ? '<i class="fas fa-check-circle ms-2"></i>' : '<i class="fas fa-times-circle ms-2"></i>') : ''}
                        </div>
                        <div class="review-answer correct">
                            <strong>Correct Answer:</strong> ${correctText}
                            <i class="fas fa-check-circle ms-2"></i>
                        </div>
                    </div>`;
                }).join('');
            } else {
                examData.questions.forEach((q, i) => {
                    const userAnswerId = responses[i];
                    const correctAnswer = Array.isArray(q.choices) ? q.choices.find(c => c.correct) : null;
                    const userAnswer = Array.isArray(q.choices) ? q.choices.find(c => c.id === userAnswerId) : null;
                    const isCorrect = userAnswerId && userAnswer && userAnswer.correct;

                    let cleanUserText = userAnswer ? userAnswer.text.replace(/^[A-D]\.\s*/i, '').trim() : null;
                    let cleanCorrectText = correctAnswer ? correctAnswer.text.replace(/^[A-D]\.\s*/i, '').trim() : 'N/A';

                    detailed += `<div class="mb-4 p-3 border rounded text-start">
                        <div class="fw-bold mb-2">Question ${i + 1}</div>
                        <div class="mb-2">${q.text}</div>`;

                    if (userAnswer) {
                        detailed += `<div class="review-answer ${isCorrect ? 'correct' : 'incorrect'} your">
                            <strong>Your Answer:</strong> ${cleanUserText}
                            ${isCorrect ? '<i class="fas fa-check-circle ms-2"></i>' : '<i class="fas fa-times-circle ms-2"></i>'}
                        </div>`;
                    } else {
                        detailed += `<div class="review-answer your">
                            <strong>Your Answer:</strong> Not answered
                        </div>`;
                    }

                    detailed += `<div class="review-answer correct">
                        <strong>Correct Answer:</strong> ${cleanCorrectText}
                        <i class="fas fa-check-circle ms-2"></i>
                    </div></div>`;
                });
            }

            document.getElementById('detailedResults').innerHTML = detailed;

            ['examModal', 'reviewModal', 'timeUpModal'].forEach(id => {
                const modal = bootstrap.Modal.getInstance(document.getElementById(id));
                if (modal) modal.hide();
            });

            showModal('resultsModal');
            loadExams();
        }

        function showError(msg) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header primary-blue-header">
                            <h5 class="modal-title">Error</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">${msg}</div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>`;
            document.body.appendChild(modal);
            new bootstrap.Modal(modal).show();
            modal.addEventListener('hidden.bs.modal', () => modal.remove());
        }

        document.getElementById('examModal').addEventListener('hide.bs.modal', function(e) {
            if (!examEnded && !isGoingToReview) {
                e.preventDefault();
                const confirmModal = new bootstrap.Modal(document.getElementById('exitConfirmModal'));
                confirmModal.show();
            }
        });

        document.getElementById('confirmExitBtn').addEventListener('click', function() {
            clearInterval(timerInterval);
            examEnded = true;
            const confirmModal = bootstrap.Modal.getInstance(document.getElementById('exitConfirmModal'));
            if (confirmModal) confirmModal.hide();
            if (examModalInstance) {
                examModalInstance.hide();
            }
        });

        document.getElementById('historyModal').addEventListener('show.bs.modal', function() {
            if (IS_GUEST) {
                let html = '<div class="table-responsive"><table class="history-table table table-striped"><thead><tr><th>Exam</th><th>Date</th><th>Score</th><th>Status</th></tr></thead><tbody>';
                if (!EXAM_HISTORY.length) {
                    html += '<tr><td colspan="4" class="text-center py-4">No exam history yet</td></tr>';
                } else {
                    EXAM_HISTORY.forEach(a => {
                        const date = a.finished_at ? new Date(a.finished_at).toLocaleDateString() : new Date().toLocaleDateString();
                        const score = Number(a.last_score ?? a.best_score ?? 0);
                        const passing = Number(a.passing_score ?? 0);
                        const status = score >= passing
                            ? '<span class="text-success fw-bold">Passed</span>'
                            : '<span class="text-danger fw-bold">Failed</span>';
                        html += `<tr>
                            <td>${a.title || 'Guest Exam Attempt'}</td>
                            <td>${date}</td>
                            <td><strong>${Math.round(score)}%</strong></td>
                            <td>${status}</td>
                        </tr>`;
                    });
                }
                html += '</tbody></table></div>';
                document.getElementById('historyBody').innerHTML = html;
                return;
            }

            fetch('../partial/exam_history.php')
                .then(r => r.json())
                .then(data => {
                    let html = '<div class="table-responsive"><table class="history-table table table-striped"><thead><tr><th>Exam</th><th>Date</th><th>Score</th><th>Status</th></tr></thead><tbody>';

                    if (data.length === 0) {
                        html += '<tr><td colspan="4" class="text-center py-4">No exam history yet</td></tr>';
                    } else {
                        data.forEach(a => {
                            const date = new Date(a.finished_at || a.started_at).toLocaleDateString();
                            const status = a.score >= a.passing_score ? '<span class="text-success fw-bold">Passed</span>' : '<span class="text-danger fw-bold">Failed</span>';
                            html += `<tr>
                                <td>${a.title}</td>
                                <td>${date}</td>
                                <td><strong>${a.score}%</strong></td>
                                <td>${status}</td>
                            </tr>`;
                        });
                    }

                    html += '</tbody></table></div>';
                    document.getElementById('historyBody').innerHTML = html;
                });
        });

        document.querySelectorAll('.topic-tab').forEach(tab => {
            tab.addEventListener('click', async () => {
                document.querySelectorAll('.topic-tab')
                    .forEach(t => t.classList.remove('active'));

                tab.classList.add('active');

                const slug = tab.dataset.topic;
                const category = CATEGORIES.find(c => slugify(c) === slug);

                if (category) {
                    await renderExamsByCategory(category);
                }
            });
        });

        window.addEventListener('DOMContentLoaded', loadExams);
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".modal-backdrop").forEach(b => b.remove());
            document.body.classList.remove("modal-open");
            document.body.style.removeProperty("padding-right");
        });
    </script>
</body>

</html>
