<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../signin.php');
    exit;
}

require_once '../partial/db_conn.php';

$user_id = $_SESSION['user_id'];

// Always fetch fresh data from DB
$stmt = $conn->prepare("
    SELECT full_name, profile_image 
    FROM users 
    WHERE id = ? AND is_deleted = 0
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: ../signin.php');
    exit;
}

$full_name     = $user['full_name'];
$profile_image = $user['profile_image'] ?? '';

// Optional: keep session fresh for other pages
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

// Page marker for navbar highlighting
$page = 'practical-exams';
?>

<?php
$cats = ['Analytical Chemistry', 'Organic Chemistry', 'Physical Chemistry', 'Inorganic Chemistry', 'BioChemistry'];
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
            height: 520px;
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
            height: 100%;
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

        /* Hover */
        .exam-category-header:hover {
            background: #eef2f7;
        }

        .toggle-icon {
            font-size: 14px;
            transition: transform 0.2s ease;
        }

        /* Category grid */
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
            padding: 1.5rem 2rem 2rem;
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

        /* Responsive Design */
        @media (min-width: 1200px) {
            .exam-card {
                height: 540px;
            }
        }

        /* Tablets */
        @media (max-width: 992px) {
            #examsGrid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Mobile */
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

    
    <!-- Gate / Locked Content Modal -->
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

        // function loadExams() {
        //     fetch('../partial/exam_list.php')
        //         .then(r => r.json())
        //         .then(({
        //             data
        //         }) => {
        //             const grid = document.getElementById('examsGrid');
        //             grid.innerHTML = '';

        //             if (!Array.isArray(data) || !data.length) {
        //                 grid.innerHTML = `
        //   <div class="text-center col-12">
        //     <h4>No exams available yet.</h4>
        //   </div>`;
        //                 return;
        //             }

        //             console.log(data)
        //             let totalScore = 0;
        //             let totalAttempts = 0;

        //             const fragment = document.createDocumentFragment();

        //             for (const e of data) {
        //                 const difficulty = (e.difficulty || 'Beginner');
        //                 const badge =
        //                     difficulty === 'Beginner' ? 'success' :
        //                     difficulty === 'Intermediate' ? 'warning' :
        //                     'danger';

        //                 if (e.user_score !== null && e.user_score !== undefined) {
        //                     totalScore += Number(e.user_score) || 0;
        //                     totalAttempts++;
        //                 }

        //                 let shortDesc = e.description || 'No description available.';
        //                 if (shortDesc.length > 120) {
        //                     shortDesc = shortDesc.slice(0, 117) + '...';
        //                 }

        //                 const safeTitle = escapeAttr(e.title);
        //                 const safeDesc = escapeAttr(e.description || '');
        //                 const safeTopic = escapeAttr(e.topic || 'Not specified');

        //                 const div = document.createElement('div');
        //                 div.className = 'exam-card';
        //                 div.style.cursor = 'pointer';
        //                 div.onclick = () => openDetailsModal(
        //                     e.id,
        //                     safeTitle,
        //                     safeDesc,
        //                     difficulty,
        //                     e.total_questions || e.actual_questions,
        //                     e.duration_minutes,
        //                     e.passing_score,
        //                     safeTopic,
        //                     e.user_score ?? null
        //                 );

        //                 div.innerHTML = `
        //   <div class="exam-card-content">
        //     <div class="exam-header">
        //       <h3 class="exam-title">${e.title}</h3>
        //       <span class="difficulty-badge ${badge}">${difficulty}</span>
        //     </div>
        //     <p class="exam-description">${shortDesc}</p>
        //     <div class="exam-stats">
        //       <div class="stat-item">
        //         <i class="fas fa-question-circle stat-icon"></i>
        //         <span>${e.total_questions || e.actual_questions} Questions</span>
        //       </div>
        //       <div class="stat-item">
        //         <i class="fas fa-clock stat-icon"></i>
        //         <span>${e.duration_minutes} Minutes</span>
        //       </div>
        //     </div>
        //   </div>
        //   <div class="exam-footer">
        //     <div class="start-btn">
        //       ${e.user_score ? 'Retake' : 'Take'} Exam <i class="fas fa-play"></i>
        //     </div>
        //     ${e.user_score !== null && e.user_score !== undefined
        //       ? `<small class="d-block text-center text-success mt-2">
        //            Your best: ${e.user_score}%
        //          </small>`
        //       : ''
        //     }
        //   </div>
        // `;

        //                 div.querySelector('.start-btn').onclick = ev => {
        //                     ev.stopPropagation();
        //                     openDetailsModal(e.id,
        //                         safeTitle,
        //                         safeDesc,
        //                         difficulty,
        //                         e.total_questions || e.actual_questions,
        //                         e.duration_minutes,
        //                         e.passing_score,
        //                         safeTopic,
        //                         e.user_score ?? null);
        //                 };

        //                 fragment.appendChild(div);
        //             }

        //             grid.appendChild(fragment);

        //             if (totalAttempts > 0) {
        //                 document.getElementById('userAvg').textContent =
        //                     Math.round(totalScore / totalAttempts) + '%';
        //             }
        //         })
        //         .catch(err => {
        //             console.error('Load exams error:', err);
        //             document.getElementById('examsGrid').innerHTML = `
        // <div class="text-center col-12 text-danger">
        //   Failed to load exams.
        // </div>`;
        //         });
        // }
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

        let ALL_EXAMS = [];

        function loadExams() {
            fetch('../partial/exam_list.php')
                .then(r => r.json())
                .then(({
                    data
                }) => {
                    ALL_EXAMS = Array.isArray(data) ? data : [];

                    // auto-load first category
                    renderExamsByCategory(CATEGORIES[0]);
                })
                .catch(err => console.error('Failed to load exams:', err));
        }

        function renderExamsByCategory(category) {
            const grid = document.getElementById('examsGrid');
            grid.innerHTML = '';

            const exams = ALL_EXAMS.filter(e => e.category === category);

            if (!exams.length) {
                grid.innerHTML = `
            <div class="text-center col-12">
                <h4>No exams available for this category.</h4>
            </div>`;
                return;
            }

            let totalScore = 0;
            let totalAttempts = 0;
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
                const totalItems = e.total_questions || e.actual_questions;

                // Percent helpers (we now display % instead of raw score like 30/30)
                const toPercent = (score, total) => {
                    const s = Number(score);
                    const t = Number(total) || 0;
                    if (!isFinite(s) || !isFinite(t) || t <= 0) return null;
                    return Math.round((s / t) * 100);
                };

                const passingPercent = (e.passing_score !== null && e.passing_score !== undefined) ?
                    Math.round(Number(e.passing_score)) :
                    null;

                const bestPercent = (e.user_score !== null && e.user_score !== undefined) ?
                    toPercent(e.user_score, totalItems) :
                    null;


                if (bestPercent !== null) {
                    totalScore += Number(bestPercent) || 0;
                    totalAttempts++;
                }

                examMetaMap.set(e.id, { title: e.title, category, moduleCode: getModuleCodeFromPostTestTitle(e.title) });

                const div = document.createElement('div');
                div.className = 'exam-card';
                div.style.cursor = 'pointer';

                div.onclick = () => openDetailsModal(e.id, safeTitle, safeDesc, difficulty, totalItems, e.duration_minutes, passingPercent, safeTopic, bestPercent, category);

                div.innerHTML = `
            <div class="exam-card-content">
                <div class="exam-header">
                    <h3 class="exam-title">${e.title}</h3>
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
                <div class="start-btn">
                    ${e.user_score ? 'Retake' : 'Take'} Exam <i class="fas fa-play"></i>
                </div>
                ${
                    bestPercent !== null
                        ? `<small class="d-block text-center text-success mt-2">
                            Your best: ${bestPercent}%
                          </small>`
                        : ''
                }
            </div>
        `;

                div.querySelector('.start-btn').onclick = ev => {
                    ev.stopPropagation();
                    div.onclick();
                };

                fragment.appendChild(div);
            }

            grid.appendChild(fragment);

            document.getElementById('userAvg').textContent =
                totalAttempts ? Math.round(totalScore / totalAttempts) + '%' : '—';
        }


        /* ---------- Helpers ---------- */

        function escapeAttr(str) {
            return String(str)
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'")
                .replace(/"/g, '&quot;')
                .replace(/\n/g, '\\n')
                .replace(/\r/g, '');
        }



        // ---- Gate / locked content helpers ----
        let currentExamMeta = null; // { id, title, category, moduleCode }
        const examMetaMap = new Map(); // examId -> { title, category, moduleCode }
        let gateTarget = null;      // { category, moduleCode }

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

        async function isModuleProgressComplete(moduleCode, category) {
            // NOTE: get_progress.php payload (per your sample) does NOT include `category`.
            // So we must not require category matching, or post tests will stay locked forever.
            if (!moduleCode) return false;
            try {
                const resp = await fetch('../partial/get_progress.php');
                const json = await resp.json();
                const data = Array.isArray(json.data) ? json.data : [];

                // Match module titles more flexibly:
                // - allow "Module A", "Module A.", "Module A:", "Module A - ...", etc.
                // - do case-insensitive category compare (some DB values differ by casing/spacing)
                // Match module titles flexibly (DB stores module as a single letter/code, e.g. "A").
                // Accept any of these title formats (case-insensitive):
                //   - "Module A ..."
                //   - "A. ..." / "A - ..." / "A: ..." / "A ..."
                // Also normalize category for safer comparison.
                const code = String(moduleCode || '').trim();
                const reStart = new RegExp(`^\\s*(?:Module\\s+)?${escapeRegExp(code)}\\b`, 'i');
                const reAnywhere = new RegExp(`\\bModule\\s+${escapeRegExp(code)}\\b`, 'i');

                // 1) Find all module groups whose TITLE matches the requested module code.
                const matchedByTitle = data.filter(m => {
                    const title = String(m.title || '');
                    return reStart.test(title) || reAnywhere.test(title);
                });
                if (matchedByTitle.length === 0) return false;

                // 2) Consider module COMPLETE if ANY matching module group has ALL its files at 100%.
                // This matters because you can have multiple "Module A" across different subjects/tracks.
                return matchedByTitle.some(mod => {
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

            // Let study-materials page auto-open the module (if it supports it)
            try {
                sessionStorage.setItem('chemEase_open_module', JSON.stringify(gateTarget));
            } catch (e) {}

            // Redirect to study materials (index router)
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
            currentExamMeta = { id, title, category, moduleCode: getModuleCodeFromPostTestTitle(title) };
            showModal('detailsModal');
        }

        document.getElementById('startFromDetailsBtn').addEventListener('click', function() {
            if (!currentExamIdForStart) return;
            bootstrap.Modal.getInstance(document.getElementById('detailsModal'))?.hide();
            redirectToExam(currentExamIdForStart);
        });

        async function redirectToExam(examId) {
            if (!examId) {
                console.log(currentExamIdForStart)
                alert("Invalid exam.");
                return;
            }


            // Content lock check happens HERE (when user clicks Take/Start Exam)
            const meta = (currentExamMeta && currentExamMeta.id === examId)
                ? currentExamMeta
                : (examMetaMap.get(examId) || null);

            if (meta && isPostTestTitle(meta.title)) {
                const category = meta.category;
                const moduleCode = meta.moduleCode || getModuleCodeFromPostTestTitle(meta.title);
                const ok = await isModuleProgressComplete(moduleCode, category);
                if (!ok) {
                    showGate(
                        "Locked content. You may have failed or haven't taken the previous exam, or you haven't finished the previous module/lesson yet.",
                        { category, moduleCode }
                    );
                    return;
                }
            }

            window.location.href = `take-exam.php?exam_id=${examId}`;
        }


        function startExam(examId) {
            fetch(`../partial/exam_start.php?exam_id=${examId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.exam) return showError('Exam not found');

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
                    document.getElementById('qTotal').textContent = examData.questions.length;

                    startTime = Date.now();
                    startTimer(data.exam.duration_minutes * 60);
                    showQuestion();

                    examModalInstance = bootstrap.Modal.getInstance(document.getElementById('examModal')) ||
                        new bootstrap.Modal(document.getElementById('examModal'), {
                            backdrop: 'static',
                            keyboard: false
                        });
                    examModalInstance.show();
                });
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
                        showResults(res, minutes + ':' + seconds);
                    } else {
                        showError('Error submitting exam. Please try again.');
                    }
                });
        }

        function showResults(data, timeTaken) {
            const passed = data.score >= data.passing_score;
            const statusText = passed ? 'Passed' : 'Failed';
            document.getElementById('finalScore').innerHTML = `${data.score}%<div style="font-size: 1rem; margin-top: 0.5rem; font-weight: 600;">${statusText}</div>`;
            document.getElementById('scoreCircle').className = 'score-circle ' + (passed ? 'score-pass' : 'score-fail');
            document.getElementById('resultsTitle').textContent = passed ? 'Congratulations! You Passed!' : 'Exam Completed';

            document.getElementById('statCorrect').textContent = data.correct;
            document.getElementById('statIncorrect').textContent = data.total - data.correct - (responses.filter(r => r === null).length);
            document.getElementById('statUnanswered').textContent = responses.filter(r => r === null).length;
            document.getElementById('statTime').textContent = timeTaken;

            let detailed = '<h4 class="mt-4 mb-3 text-start">Detailed Results</h4>';

            examData.questions.forEach((q, i) => {
                const userAnswerId = responses[i];
                const correctAnswer = q.choices.find(c => c.correct);
                const userAnswer = q.choices.find(c => c.id === userAnswerId);
                const isCorrect = userAnswerId && userAnswer && userAnswer.correct;

                let cleanUserText = userAnswer ? userAnswer.text.replace(/^[A-D]\.\s*/i, '').trim() : null;
                let cleanCorrectText = correctAnswer.text.replace(/^[A-D]\.\s*/i, '').trim();

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
            tab.addEventListener('click', () => {
                document.querySelectorAll('.topic-tab')
                    .forEach(t => t.classList.remove('active'));

                tab.classList.add('active');

                const slug = tab.dataset.topic;
                const category = CATEGORIES.find(c => slugify(c) === slug);

                if (category) {
                    renderExamsByCategory(category);
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