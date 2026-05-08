<?php
require_once '../partial/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function resolveCurrentUserId(): ?int
{
    $candidateKeys = ['user_id', 'student_id', 'uid', 'id'];
    foreach ($candidateKeys as $key) {
        if (!isset($_SESSION[$key])) {
            continue;
        }

        $value = $_SESSION[$key];
        if (is_numeric($value) && (int)$value > 0) {
            return (int)$value;
        }
    }

    return null;
}

if (!isset($_SESSION['guest_study_progress']) || !is_array($_SESSION['guest_study_progress'])) {
    $_SESSION['guest_study_progress'] = [];
}
if (!isset($_SESSION['guest_selected_study_module']) || !is_array($_SESSION['guest_selected_study_module'])) {
    $_SESSION['guest_selected_study_module'] = [];
}

$currentUserId = resolveCurrentUserId();
$sessionRole = (string)($_SESSION['role'] ?? '');
$isGuestUser = ($sessionRole === 'guest') || $currentUserId === null;
$hasGuestProgress = !empty($_SESSION['guest_study_progress']);
$guestSelectedStudyModule = !empty($_SESSION['guest_selected_study_module']) ? $_SESSION['guest_selected_study_module'] : null;

if (isset($_GET['guest_progress_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = (string)$_GET['guest_progress_action'];

    if ($action === 'get') {
        echo json_encode([
            'ok' => true,
            'data' => $_SESSION['guest_study_progress']
        ]);
        exit;
    }

    if ($action === 'get_selected_module') {
        echo json_encode([
            'ok' => true,
            'data' => $guestSelectedStudyModule
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'ok' => false,
            'message' => 'Method not allowed.'
        ]);
        exit;
    }

    if ($action === 'save') {
        if (!$isGuestUser) {
            echo json_encode([
                'ok' => true,
                'message' => 'Authenticated users save directly to DB.'
            ]);
            exit;
        }

        $fileId = isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0;
        $progress = isset($_POST['progress']) ? (int)round((float)$_POST['progress']) : 0;
        $progress = max(0, min(100, $progress));

        if ($fileId <= 0) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'message' => 'Invalid file id.'
            ]);
            exit;
        }

        $key = (string)$fileId;
        $existing = isset($_SESSION['guest_study_progress'][$key]) ? (int)$_SESSION['guest_study_progress'][$key] : 0;
        $_SESSION['guest_study_progress'][$key] = max($existing, $progress);

        echo json_encode([
            'ok' => true,
            'file_id' => $fileId,
            'progress' => $_SESSION['guest_study_progress'][$key]
        ]);
        exit;
    }

    if ($action === 'clear') {
        $_SESSION['guest_study_progress'] = [];
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'set_selected_module') {
        if (!$isGuestUser) {
            echo json_encode([
                'ok' => true,
                'message' => 'Authenticated users are not restricted to one guest module.'
            ]);
            exit;
        }

        $materialId = isset($_POST['material_id']) ? (int)$_POST['material_id'] : 0;
        $category = trim((string)($_POST['category'] ?? ''));
        $moduleCode = trim((string)($_POST['module'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));

        if ($materialId <= 0 || $category === '' || $moduleCode === '') {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'message' => 'Invalid module selection.'
            ]);
            exit;
        }

        $_SESSION['guest_selected_study_module'] = [
            'material_id' => $materialId,
            'category' => substr($category, 0, 120),
            'module' => substr($moduleCode, 0, 30),
            'title' => substr($title, 0, 180)
        ];

        echo json_encode([
            'ok' => true,
            'data' => $_SESSION['guest_selected_study_module']
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'Unknown action.'
    ]);
    exit;
}

$cats = ['Analytical Chemistry', 'Organic Chemistry', 'Physical Chemistry', 'Inorganic Chemistry', 'BioChemistry'];
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChemEase - Study Materials</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #17a2b8;
            --gradient-end: #20c5d4;
            --dark-text: #2c3e50;
            --text-muted: #6c757d;
            --bg-light: #f8f9fa;
            --card-bg: rgba(255, 255, 255, 0.98);
        }

        body {
            background: linear-gradient(135deg, #e8f4f8 0%, #f8f9fa 50%, #ffffff 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            color: var(--dark-text);
            margin: 0;
            padding: 0;
        }

        .study-materials-container {
            padding: 1rem;
            max-width: 1400px;
            margin: 0 auto;
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

        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2.2rem;
            padding: 0 0.5rem;
        }

        .topic-section {
            display: none;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2.2rem;
        }

        .topic-section.active {
            display: grid;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 5rem 2rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 24px;
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 40px rgba(23, 162, 184, 0.1);
            border: 2px dashed rgba(23, 162, 184, 0.2);
        }

        .empty-state i {
            font-size: 6.5rem;
            color: #ced4da;
            margin-bottom: 1.8rem;
            opacity: 0.8;
        }

        .empty-state h3 {
            font-size: 2.1rem;
            font-weight: 700;
            color: #495057;
            margin-bottom: 1.2rem;
        }

        .empty-state p {
            font-size: 1.15rem;
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .material-card {
            background: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 14px 45px rgba(23, 162, 184, 0.16);
            border: 1px solid rgba(23, 162, 184, 0.1);
            transition: all .45s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .material-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 7px;
            background: linear-gradient(90deg, var(--primary-blue), var(--gradient-end));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .45s ease;
        }

        .material-card:hover {
            transform: translateY(-14px);
            box-shadow: 0 28px 70px rgba(23, 162, 184, 0.28);
        }

        .material-card:hover::before {
            transform: scaleX(1);
        }

        .card-content {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card-title {
            font-size: 1.65rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .card-description {
            font-size: 1.02rem;
            color: #5c6b7f;
            line-height: 1.7;
            margin-bottom: 1.5rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
        }

        .material-options-wrapper {
            flex: 1;
            overflow-y: auto;
            padding-right: 8px;
            margin-bottom: 1.5rem;
            max-height: 260px;
        }

        .material-options-wrapper::-webkit-scrollbar {
            width: 6px;
        }

        .material-options-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }

        .material-options-wrapper::-webkit-scrollbar-thumb {
            background: rgba(23, 162, 184, 0.3);
            border-radius: 3px;
        }

        .material-options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .material-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.2rem;
            background: rgba(23, 162, 184, 0.08);
            border-radius: 14px;
            transition: all .35s;
            gap: 0.8rem;
        }

        .material-item:hover {
            background: rgba(23, 162, 184, 0.16);
            transform: translateX(6px);
        }

        .material-item-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 0;
        }

        .material-item i.fa-xl {
            color: var(--primary-blue);
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .material-item-info {
            flex: 1;
            min-width: 0;
        }

        .material-item .file-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #2c3e50;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .material-item .file-subtitle {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.2rem;
        }

        .btn-group-custom {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .view-btn,
        .download-btn {
            width: 44px;
            height: 44px;
            padding: 0;
            border-radius: 12px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .3s;
            border: 2px solid var(--primary-blue);
            background: transparent;
            color: var(--primary-blue);
        }

        .view-btn:hover,
        .download-btn:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-2px);
        }

        .download-btn {
            border-color: #28a745;
            color: #28a745;
        }

        .download-btn:hover {
            background: #28a745;
            color: white;
        }

        .download-btn[aria-disabled="true"],
        .view-btn:disabled,
        .start-learning-btn:disabled {
            opacity: 0.65;
            cursor: not-allowed !important;
            pointer-events: none;
        }

        .guest-download-disabled {
            opacity: 0.8;
            cursor: help !important;
            pointer-events: auto !important;
            border-color: #94a3b8 !important;
            color: #64748b !important;
            background: #f8fafc !important;
            position: relative;
        }

        .guest-download-disabled:hover {
            background: #e8f4f8 !important;
            color: var(--primary-blue) !important;
            border-color: var(--primary-blue) !important;
            transform: translateY(-2px);
        }

        .material-card.module-locked {
            border-color: rgba(245, 158, 11, 0.35);
            box-shadow: 0 14px 45px rgba(245, 158, 11, 0.12);
        }

        .module-lock-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            width: fit-content;
            margin-bottom: 0.85rem;
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            border: 1px solid rgba(245, 158, 11, 0.32);
            background: rgba(245, 158, 11, 0.12);
            color: #92400e;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .material-card.guest-locked {
            border-color: rgba(220, 53, 69, 0.22);
        }

        .guest-lock-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            width: fit-content;
            margin-bottom: 0.85rem;
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            border: 1px solid rgba(220, 53, 69, 0.28);
            background: rgba(220, 53, 69, 0.12);
            color: #a71d2a;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .progress-container {
            margin: 1.5rem 0;
            padding: 1.2rem;
            background: rgba(23, 162, 184, 0.04);
            border-radius: 14px;
            border: 1px solid rgba(23, 162, 184, 0.1);
            max-height: 240px;
            overflow-y: auto;
        }

        .progress-container:empty {
            display: none;
        }

        .progress-container::-webkit-scrollbar {
            width: 6px;
        }

        .progress-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .progress-container::-webkit-scrollbar-thumb {
            background: rgba(23, 162, 184, 0.3);
            border-radius: 3px;
        }

        .progress-header {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .progress-header i {
            font-size: 1rem;
        }

        .progress-item {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: center;
            font-size: 0.9rem;
            margin-bottom: 0.9rem;
            padding-bottom: 0.9rem;
            border-bottom: 1px solid rgba(23, 162, 184, 0.1);
        }

        .progress-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .progress-item-left {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            min-width: 0;
        }

        .progress-item-name {
            font-weight: 600;
            color: #2c3e50;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .progress-bar-wrapper {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-blue), var(--gradient-end));
            border-radius: 4px;
            transition: width .8s ease;
        }

        .progress-item-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .progress-percentage {
            font-weight: 700;
            color: var(--primary-blue);
            min-width: 45px;
            text-align: right;
        }

        .progress-check {
            color: #28a745;
            font-size: 1.1rem;
        }

        .card-footer-actions {
            margin-top: auto;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(23, 162, 184, 0.1);
        }

        .start-learning-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--gradient-end));
            color: white;
            border: none;
            padding: 1.2rem 2.2rem;
            border-radius: 16px;
            font-size: 1.15rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .45s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            box-shadow: 0 10px 30px rgba(23, 162, 184, 0.45);
            width: 100%;
            min-height: 56px;
        }

        .start-learning-btn:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 40px rgba(23, 162, 184, 0.55);
        }

        .prereq-loader {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(6px);
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity .25s ease, visibility .25s ease;
        }

        .prereq-loader.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .prereq-loader-box {
            background: #fff;
            border: 1px solid rgba(23, 162, 184, 0.12);
            border-radius: 18px;
            box-shadow: 0 14px 40px rgba(23, 162, 184, 0.16);
            padding: 1.25rem 1.5rem;
            min-width: 280px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .85rem;
        }

        .prereq-loader-text {
            font-size: .95rem;
            font-weight: 600;
            color: #2c3e50;
            text-align: center;
        }

        /* ──────────────────────────────────────────────
           Modal improvements – especially for mobile
        ────────────────────────────────────────────── */
        .custom-modal .modal-dialog {
            margin: 0.5rem;
            max-width: 95vw;
        }

        .custom-modal .modal-content {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: none;
            min-height: 70vh;
        }

        .custom-modal .modal-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--gradient-end));
            color: white;
            border: none;
            padding: 1rem 1.25rem;
            flex-wrap: wrap;
        }

        .custom-modal .modal-title {
            font-size: 1.35rem;
            font-weight: 700;
            text-align: center;
            flex: 1;
            line-height: 1.4;
        }

        .custom-modal .btn-close {
            filter: invert(1);
            opacity: 0.9;
            min-width: 44px;
            min-height: 44px;
            padding: 0.75rem;
        }

        .custom-modal .modal-body {
            padding: 0;
            height: calc(100vh - 140px);
            max-height: 85vh;
        }

        .custom-modal .nav-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            white-space: nowrap;
            padding: 0 1rem;
            scrollbar-width: thin;
        }

        .custom-modal .nav-tabs::-webkit-scrollbar {
            height: 6px;
        }

        .custom-modal .nav-tabs::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.4);
            border-radius: 3px;
        }

        .custom-modal .nav-link {
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .custom-modal .tab-content {
            padding: 1rem;
        }

        .file-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.2rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin: 0.6rem 0;
            cursor: pointer;
            transition: all .25s;
            border: 2px solid transparent;
            min-height: 70px;
            gap: 0.8rem;
        }

        .file-list-item:hover {
            background: white;
            border-color: var(--primary-blue);
            box-shadow: 0 4px 14px rgba(23, 162, 184, 0.15);
        }

        .file-list-item i.file-icon {
            font-size: 2rem;
            width: 50px;
            text-align: center;
            flex-shrink: 0;
        }

        .file-list-item .file-info {
            flex: 1;
            min-width: 0;
        }

        .file-list-item .file-title {
            font-weight: 600;
            font-size: 1.05rem;
            line-height: 1.3;
        }

        .file-list-item .file-subtitle {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .file-list-item .action-icon {
            font-size: 1.4rem;
            flex-shrink: 0;
            color: #6c757d;
            padding: 0.4rem;
        }

        .file-list-item.pdf .action-icon {
            color: var(--primary-blue);
        }

        .file-list-item.video .action-icon {
            color: #dc3545;
        }

        @media (max-width: 576px) {
            .custom-modal .modal-dialog {
                margin: 0.4rem;
                max-width: 100%;
            }

            .custom-modal .modal-content {
                border-radius: 14px;
                min-height: 60vh;
            }

            .custom-modal .modal-header {
                padding: 0.9rem 1rem;
            }

            .custom-modal .modal-title {
                font-size: 1.2rem;
            }

            .custom-modal .modal-body {
                height: calc(100vh - 120px);
                max-height: 80vh;
            }

            .custom-modal .nav-link {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .custom-modal .tab-content {
                padding: 0.8rem;
            }

            .file-list-item {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
                padding: 1.1rem;
                min-height: auto;
                gap: 0.9rem;
            }

            .file-list-item i.file-icon {
                width: auto;
                margin-bottom: 0.4rem;
            }

            .file-list-item .file-info {
                width: 100%;
            }

            .file-list-item .file-title {
                font-size: 1.05rem;
            }

            .file-list-item .action-icon {
                align-self: center;
                margin-top: 0.3rem;
            }

            .material-desc {
                font-size: 0.92rem;
                padding: 0.9rem 1.1rem;
            }
        }

        @media (max-width: 400px) {
            .custom-modal .modal-title {
                font-size: 1.1rem;
            }

            .custom-modal .nav-link {
                font-size: 0.85rem;
                padding: 0.5rem 0.9rem;
            }

            .file-list-item .file-title {
                font-size: 1rem;
            }
        }

        @media (max-width: 1200px) {

            .materials-grid,
            .topic-section {
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                gap: 1.8rem;
            }
        }

        @media (max-width: 992px) {
            .page-title {
                font-size: 2.4rem;
            }

            .page-subtitle {
                font-size: 1.1rem;
            }

            .card-content {
                padding: 1.8rem;
            }

            .topic-tab {
                padding: 0.8rem 1.4rem;
                font-size: 0.95rem;
                min-width: 130px;
            }
        }

        @media (max-width: 768px) {
            .study-materials-container {
                padding: 0.75rem;
            }

            .page-header {
                padding: 2rem 1.5rem;
                margin: 1rem auto 1.5rem;
                border-radius: 20px;
            }

            .page-title {
                font-size: 2rem;
            }

            .page-subtitle {
                font-size: 1rem;
                line-height: 1.6;
            }

            .topic-tabs {
                padding: 0.8rem;
                gap: 0.5rem;
                margin-bottom: 1.5rem;
                border-radius: 16px;
            }

            .materials-grid,
            .topic-section {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                padding: 0;
            }

            .material-card {
                border-radius: 20px;
            }

            .card-content {
                padding: 1.5rem;
            }

            .card-title {
                font-size: 1.45rem;
            }

            .card-description {
                font-size: 0.95rem;
            }

            .material-item {
                flex-direction: column;
                align-items: stretch;
            }

            .material-item-left {
                width: 100%;
            }

            .btn-group-custom {
                width: 100%;
                justify-content: space-between;
            }

            .view-btn,
            .download-btn {
                width: 48px;
                height: 48px;
            }

            .material-desc {
                padding: 1rem 1.3rem;
                margin: 0 1rem 0.5rem;
                font-size: 0.95rem;
            }

            .empty-state {
                padding: 3.5rem 1.5rem;
                border-radius: 20px;
            }

            .empty-state i {
                font-size: 5rem;
            }

            .empty-state h3 {
                font-size: 1.8rem;
            }

            .empty-state p {
                font-size: 1rem;
            }
        }
    </style>
    <div class="study-materials-container">
        <div class="page-header">
            <h1 class="page-title">Study Materials</h1>
            <p class="page-subtitle">Access comprehensive learning resources for your chemistry courseware exam preparation.</p>
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

        <div class="materials-grid">
            <?php foreach ($cats as $cat):
                $slug = strtolower(str_replace(' ', '-', $cat));
            ?>
                <div class="topic-section <?= $slug === 'analytical-chemistry' ? 'active' : '' ?>" data-topic="<?= $slug ?>">
                    <?php
                    $stmt = $conn->prepare("SELECT id, title, description, category, module FROM study_materials WHERE category = ? ORDER BY module ASC, id ASC");
                    $stmt->bind_param('s', $cat);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
                            <h3>No materials available in <?= htmlspecialchars($cat) ?> yet.</h3>
                            <p>Check back later for new content! We're constantly adding high-quality resources.</p>
                        </div>
                        <?php else:
                        while ($material = $result->fetch_assoc()):
                            $mid = $material['id'];
                        ?>
                            <div class="material-card" data-id="<?= $mid ?>" data-category="<?= htmlspecialchars($material['category']) ?>" data-module="<?= htmlspecialchars($material['module']) ?>">
                                <div class="card-content">
                                    <h3 class="card-title"><?= htmlspecialchars($material['title']) ?></h3>
                                    <p class="card-description">
                                        <?= htmlspecialchars($material['description'] ?: 'Explore detailed study materials including PDFs and video lectures to master this topic.') ?>
                                    </p>

                                    <div class="material-options-wrapper">
                                        <div class="material-options">
                                            <?php
                                            $filesStmt = $conn->prepare("
                                            SELECT id, type, path, title
                                            FROM study_material_files
                                            WHERE material_id = ?
                                            ORDER BY id ASC
                                        ");
                                            $filesStmt->bind_param('i', $mid);
                                            $filesStmt->execute();
                                            $filesRes = $filesStmt->get_result();
                                            while ($f = $filesRes->fetch_assoc()):
                                                $icon = $f['type'] === 'pdf' ? 'fa-file-pdf text-danger' : 'fa-play-circle text-danger';
                                                $displayName = $f['title'];
                                                if (!$displayName) {
                                                    if ($f['type'] === 'pdf') {
                                                        $filename = basename($f['path']);
                                                        $displayName = preg_replace('/^pdf_[a-f0-9]{11}\./i', '', $filename);
                                                        $displayName = str_replace('.pdf', '', $displayName);
                                                        $displayName = ucwords(str_replace(['-', '_'], ' ', $displayName));
                                                        if ($displayName === '') $displayName = 'Document ' . $f['id'];
                                                    } else {
                                                        $videoId = '';
                                                        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $f['path'], $m)) {
                                                            $videoId = $m[1];
                                                        }
                                                        // Do not call YouTube/oEmbed while PHP renders the page.
                                                        // A blocked external request here prevents index.php from reaching its loader-hide script.
                                                        $displayName = $videoId ? 'YouTube Video' : 'YouTube Video';
                                                    }
                                                }
                                            ?>
                                                <div class="material-item" data-file-id="<?= $f['id'] ?>" data-file-name="<?= htmlspecialchars($displayName) ?>" data-file-type="<?= $f['type'] ?>" data-file-path="<?= htmlspecialchars($f['path']) ?>">
                                                    <div class="material-item-left">
                                                        <i class="fas <?= $icon ?> fa-xl"></i>
                                                        <div class="material-item-info">
                                                            <div class="file-title"><?= htmlspecialchars($displayName) ?></div>

                                                        </div>
                                                    </div>
                                                    <div class="btn-group-custom">
                                                        <button class="view-btn" data-fid="<?= $f['id'] ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($f['type'] === 'pdf'): ?>
                                                            <?php if ($isGuestUser): ?>
                                                                <button
                                                                    type="button"
                                                                    class="download-btn guest-download-disabled"
                                                                    data-fid="<?= $f['id'] ?>"
                                                                    title="Downloadable content can be unlocked when signed in"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-title="Downloadable content can be unlocked when signed in"
                                                                    aria-disabled="true">
                                                                    <i class="fas fa-download"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <a
                                                                    href="../<?= htmlspecialchars($f['path']) ?>"
                                                                    download
                                                                    class="download-btn"
                                                                    data-fid="<?= $f['id'] ?>"
                                                                    data-path="../<?= htmlspecialchars($f['path']) ?>">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endwhile;
                                            $filesStmt->close(); ?>
                                        </div>
                                    </div>

                                    <div class="progress-container" id="prog-<?= $mid ?>"></div>

                                    <div class="card-footer-actions">
                                        <button class="start-learning-btn" data-id="<?= $mid ?>">
                                            <i class="fas fa-play-circle"></i> Start Learning
                                        </button>
                                    </div>
                                </div>
                            </div>
                    <?php
                        endwhile;
                    endif;
                    $stmt->close();
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="prereqLoader" class="prereq-loader hidden">
        <div class="prereq-loader-box">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="prereq-loader-text">Loading study progress and module access...</div>
        </div>
    </div>

    <!-- Prerequisite Modal: block next module if previous post-test not passed -->
    <div class="modal fade" id="prereqModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Module locked</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="prereqModalMessage"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
                    <button type="button" class="btn btn-primary" id="goBackToPrevBtn">Go back</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Guest Download Modal -->
    <div class="modal fade" id="guestDownloadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sign in to download</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 text-info flex-shrink-0" style="width:46px;height:46px;">
                            <i class="fas fa-download"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-2">Downloadable content unlocks when signed in.</h6>
                            <p class="mb-0 text-muted">You can still view all study materials as a guest. Sign in or create an account to download PDFs and keep your progress.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep browsing</button>
                    <a href="../signin.php" class="btn btn-primary">Sign in / Create account</a>
                </div>
            </div>
        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', () => {
    let currentModalInstance = null;
    let currentModalElement = null;
    let bypassPrereqOnce = false;
    let prereqPrevMaterialId = null;
    let __ytApiPromise = null;

    const IS_GUEST = <?= $isGuestUser ? 'true' : 'false' ?>;

    const POSTTEST_STATUS = new Map();
    const MATERIAL_PROGRESS = new Map();
    const MATERIAL_META = new Map();
    const CATEGORY_MODULE_INDEX = new Map();
    const YOUTUBE_TITLE_CACHE = new Map();

    const __progressState = {
        lastSavedPct: new Map(),
        lastSentAt: new Map(),
        lastUiPct: new Map(),
        cleanups: [],
    };

    let postTestReady = false;
    let progressReady = false;
    let prereqBootstrapDone = false;
    let guestSelectedModule = null;

    const prereqLoaderEl = document.getElementById('prereqLoader');

    function showPrereqLoader() {
        prereqLoaderEl?.classList.remove('hidden');
    }

    function hidePrereqLoader() {
        prereqLoaderEl?.classList.add('hidden');
    }

    function setLearningButtonsDisabled(disabled = true) {
        document.querySelectorAll('.view-btn, .start-learning-btn, .download-btn').forEach(btn => {
            // Never leave buttons physically unclickable. Prereq/guest rules are enforced inside click handlers.
            if ('disabled' in btn) btn.disabled = false;
            btn.style.pointerEvents = '';
            btn.style.opacity = '';
            btn.style.cursor = '';

            if (btn.classList.contains('download-btn')) {
                btn.setAttribute('aria-disabled', 'false');
                btn.removeAttribute('tabindex');
            }
        });
    }

    function updatePrereqBootstrapState() {
        if (postTestReady && progressReady) {
            prereqBootstrapDone = true;
            setLearningButtonsDisabled(false);
            refreshModuleLockBadges();
            initGuestDownloadTooltips();
            hidePrereqLoader();
        }
    }

    function getGuestProgressFromBrowser() {
        try {
            return JSON.parse(sessionStorage.getItem('guest_progress') || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    function saveGuestProgressToBrowser(fileId, progress) {
        const safeFileId = parseInt(fileId, 10);
        const safeProgress = Math.max(0, Math.min(100, Math.round(Number(progress) || 0)));
        if (!Number.isFinite(safeFileId) || safeFileId <= 0) return;

        const map = getGuestProgressFromBrowser();
        const current = Number(map[safeFileId] || 0);
        map[safeFileId] = Math.max(current, safeProgress);
        sessionStorage.setItem('guest_progress', JSON.stringify(map));
    }

    function getGuestSelectedModuleFromBrowser() {
        try {
            return JSON.parse(sessionStorage.getItem('guest_selected_module') || 'null');
        } catch (e) {
            return null;
        }
    }

    function setGuestSelectedModuleToBrowser(payload) {
        if (!payload || !payload.material_id) return false;
        const safePayload = {
            material_id: String(payload.material_id),
            category: String(payload.category || ''),
            module: String(payload.module || ''),
            title: String(payload.title || '')
        };
        sessionStorage.setItem('guest_selected_module', JSON.stringify(safePayload));
        guestSelectedModule = safePayload;
        return true;
    }

    function normalizeCategory(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/&/g, 'and')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function normalizeModule(value) {
        return String(value || '').trim().toUpperCase();
    }

    function parseModuleCodeFromPostTestTitle(title) {
        const m = String(title || '').match(/POST\s*TEST\s*\(\s*Module\s+([A-Za-z0-9IVXLCDM]+)\s*\)/i);
        return m ? normalizeModule(m[1]) : null;
    }

    function moduleToSortableValue(moduleCode) {
        const code = normalizeModule(moduleCode);
        if (/^\d+$/.test(code)) return Number(code);
        if (/^[A-Z]$/.test(code)) return code.charCodeAt(0) - 64;
        const roman = { I: 1, II: 2, III: 3, IV: 4, V: 5, VI: 6, VII: 7, VIII: 8, IX: 9, X: 10 };
        return roman[code] ?? Number.MAX_SAFE_INTEGER;
    }

    function buildMaterialMeta() {
        MATERIAL_META.clear();
        CATEGORY_MODULE_INDEX.clear();

        document.querySelectorAll('.material-card').forEach(card => {
            const materialId = String(card.dataset.id || '');
            const categoryKey = normalizeCategory(card.dataset.category);
            const moduleKey = normalizeModule(card.dataset.module);
            const meta = { materialId, categoryKey, moduleKey, sortValue: moduleToSortableValue(moduleKey), card };

            MATERIAL_META.set(materialId, meta);
            if (!CATEGORY_MODULE_INDEX.has(categoryKey)) CATEGORY_MODULE_INDEX.set(categoryKey, []);
            CATEGORY_MODULE_INDEX.get(categoryKey).push(meta);
        });

        CATEGORY_MODULE_INDEX.forEach(items => {
            items.sort((a, b) => a.sortValue - b.sortValue || Number(a.materialId) - Number(b.materialId));
        });
    }

    function isFirstUnlockedModuleForGuest(materialId) {
        const current = MATERIAL_META.get(String(materialId));
        if (!current) return true;
        const items = CATEGORY_MODULE_INDEX.get(current.categoryKey) || [];
        if (!items.length) return true;
        return String(items[0].materialId) === String(materialId);
    }

    function getMaterialLockReason(materialId) {
        const previousMeta = getPreviousMaterialMeta(materialId);
        if (!previousMeta) return null;

        if (!isMaterialComplete(previousMeta.materialId)) {
            return { previousMeta, reason: 'not_finished' };
        }

        const status = getPostTestStatusForMaterial(previousMeta);
        if (!status) {
            return { previousMeta, reason: 'not_taken' };
        }

        if (status.passed !== true) {
            return { previousMeta, reason: 'failed' };
        }

        return null;
    }

    function isGuestModuleAllowed(materialId) {
        // Guests now follow the same module-gating rule as real users.
        return getMaterialLockReason(materialId) === null;
    }

    async function ensureGuestModuleSelectionFromTrigger(triggerEl) {
        if (!IS_GUEST) return true;

        const card = triggerEl?.closest('.material-card');
        if (card) {
            setGuestSelectedModuleToBrowser({
                material_id: String(card.dataset.id || ''),
                category: card.dataset.category || '',
                module: card.dataset.module || '',
                title: card.querySelector('.card-title')?.textContent?.trim() || ''
            });
        }

        return true;
    }

    function refreshModuleLockBadges() {
        document.querySelectorAll('.material-card.module-locked').forEach(card => card.classList.remove('module-locked'));
        document.querySelectorAll('.module-lock-badge').forEach(badge => badge.remove());

        document.querySelectorAll('.material-card').forEach(card => {
            const materialId = String(card.dataset.id || '');
            const lock = getMaterialLockReason(materialId);
            if (!lock) return;

            card.classList.add('module-locked');
            const title = card.querySelector('.card-title');
            if (!title) return;

            const label = lock.reason === 'not_finished'
                ? 'Complete previous module first'
                : lock.reason === 'not_taken'
                    ? 'Take previous post-test first'
                    : 'Pass previous post-test first';

            title.insertAdjacentHTML('beforebegin', `
                <span class="module-lock-badge">
                    <i class="fas fa-lock"></i>${label}
                </span>
            `);
        });
    }

    function applyGuestModuleLockBadges() {
        refreshModuleLockBadges();
    }

    function refreshMaterialProgressState(materialId) {
        const cont = document.getElementById('prog-' + materialId);
        if (!cont) {
            MATERIAL_PROGRESS.set(String(materialId), { avg: 0, complete: false, count: 0 });
            return;
        }

        const values = Array.from(cont.querySelectorAll('.progress-item .progress-percentage'))
            .map(el => parseFloat(String(el.textContent || '').replace('%', '').trim()))
            .filter(v => Number.isFinite(v));

        const avg = values.length ? (values.reduce((a, b) => a + b, 0) / values.length) : 0;
        const complete = values.length > 0 && values.every(v => v >= 100);
        MATERIAL_PROGRESS.set(String(materialId), { avg, complete, count: values.length });
    }

    function isMaterialComplete(materialId) {
        return MATERIAL_PROGRESS.get(String(materialId))?.complete === true;
    }

    function getPostTestStatusForMaterial(materialMeta) {
        if (!materialMeta) return null;
        return POSTTEST_STATUS.get(`${materialMeta.categoryKey}::${materialMeta.moduleKey}`) || null;
    }

    function getPreviousMaterialMeta(materialId) {
        const current = MATERIAL_META.get(String(materialId));
        if (!current) return null;
        const items = CATEGORY_MODULE_INDEX.get(current.categoryKey) || [];
        const idx = items.findIndex(item => item.materialId === String(materialId));
        if (idx <= 0) return null;
        return items[idx - 1] || null;
    }

    async function fetchWithTimeout(url, opts = {}, timeoutMs = 12000) {
        const ctrl = new AbortController();
        const timer = setTimeout(() => ctrl.abort(), timeoutMs);
        try {
            return await fetch(url, { ...opts, signal: ctrl.signal });
        } finally {
            clearTimeout(timer);
        }
    }

    function addPostTestStatusFromExamRow(e) {
        if (!e || !e.title) return;
        const moduleCode = parseModuleCodeFromPostTestTitle(e.title);
        if (!moduleCode) return;

        const categoryKey = normalizeCategory(e.category);
        const bestRaw = e.user_score ?? e.score ?? e.grade ?? e.raw_percent ?? null;
        const passingRaw = e.passing_score ?? e.passing ?? null;
        const bestPct = (bestRaw !== null && bestRaw !== undefined && bestRaw !== '') ? Math.round(Number(bestRaw)) : null;
        const passingPct = (passingRaw !== null && passingRaw !== undefined && passingRaw !== '') ? Math.round(Number(passingRaw)) : 75;
        const statusText = String(e.status || '').toLowerCase();
        const passed = statusText === 'passed' || (bestPct !== null && passingPct !== null && bestPct >= passingPct);

        POSTTEST_STATUS.set(`${categoryKey}::${moduleCode}`, {
            passed,
            bestPct,
            passingPct,
            rawTitle: e.title
        });
    }

    async function loadGuestPostTestStatus() {
        try {
            const r = await fetchWithTimeout('../partial/exam_history.php', { cache: 'no-store' }, 10000);
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            const history = await r.json();
            if (Array.isArray(history)) {
                history.forEach(addPostTestStatusFromExamRow);
            }
        } catch (err) {
            console.warn('Guest exam history unavailable for study-material locks:', err);
        }
    }

    async function loadPostTestStatus() {
        try {
            POSTTEST_STATUS.clear();

            if (IS_GUEST) {
                await loadGuestPostTestStatus();
                return;
            }

            const r = await fetchWithTimeout('../partial/exam_list.php', { cache: 'no-store' }, 10000);
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            const j = await r.json();
            const exams = Array.isArray(j.data) ? j.data : [];
            exams.forEach(addPostTestStatusFromExamRow);
        } catch (err) {
            console.error('Failed to load post-test status:', err);
        } finally {
            postTestReady = true;
            updatePrereqBootstrapState();
        }
    }

    async function loadProgressData() {
        try {
            const materialMap = new Map();

            if (IS_GUEST) {
                const guestProgress = getGuestProgressFromBrowser();
                document.querySelectorAll('.material-card').forEach(card => {
                    const mid = String(card.dataset.id || '');
                    const files = [];
                    card.querySelectorAll('.material-item[data-file-id]').forEach(item => {
                        const fid = String(item.dataset.fileId || '');
                        if (!Object.prototype.hasOwnProperty.call(guestProgress, fid)) return;
                        const safeProgress = Math.max(0, Math.min(100, Number(guestProgress[fid] || 0)));
                        files.push({ id: fid, progress: safeProgress });
                    });
                    if (files.length) materialMap.set(mid, files);
                });
            } else {
                const r = await fetchWithTimeout('../partial/get_progress.php', { cache: 'no-store' }, 10000);
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                const { data } = await r.json();
                if (Array.isArray(data)) {
                    data.forEach(mat => materialMap.set(String(mat.id), Array.isArray(mat.files) ? mat.files : []));
                }
            }

            document.querySelectorAll('.material-card').forEach(card => {
                const mid = String(card.dataset.id || '');
                const cont = document.getElementById('prog-' + mid);
                const files = materialMap.get(mid) || [];
                if (!cont) return;

                if (!files.length) {
                    MATERIAL_PROGRESS.set(mid, { avg: 0, complete: false, count: 0 });
                    return;
                }

                cont.innerHTML = '<div class="progress-header"><i class="fas fa-chart-line"></i> Your Progress</div>';
                files.forEach(p => {
                    const name = getFileDisplayName(p.id);
                    const safeProgress = Math.max(0, Math.min(100, Number(p.progress || 0)));
                    cont.insertAdjacentHTML('beforeend', `
                        <div class="progress-item" data-fid="${p.id}">
                            <div class="progress-item-left">
                                <div class="progress-item-name" title="${escapeHtml(name)}">${escapeHtml(name)}</div>
                                <div class="progress-bar-wrapper"><div class="progress-bar-fill" style="width:${safeProgress}%"></div></div>
                            </div>
                            <div class="progress-item-right">
                                <span class="progress-percentage">${safeProgress}%</span>
                                ${safeProgress >= 100 ? '<i class="fas fa-check-circle progress-check"></i>' : ''}
                            </div>
                        </div>
                    `);
                    __progressState.lastUiPct.set(String(p.id), safeProgress);
                    __progressState.lastSavedPct.set(String(p.id), safeProgress);
                });

                refreshMaterialProgressState(mid);
            });

            document.querySelectorAll('.material-card').forEach(card => {
                const mid = String(card.dataset.id || '');
                if (!MATERIAL_PROGRESS.has(mid)) MATERIAL_PROGRESS.set(mid, { avg: 0, complete: false, count: 0 });
            });
        } catch (err) {
            console.error('Progress fetch error:', err);
        } finally {
            progressReady = true;
            updatePrereqBootstrapState();
        }
    }

    async function syncGuestProgressToDbOnce() {
        if (IS_GUEST) return;
        const guestProgress = getGuestProgressFromBrowser();
        const entries = Object.entries(guestProgress);
        if (!entries.length) return;

        try {
            const results = await Promise.all(entries.map(async ([fileId, progress]) => {
                const safeFileId = parseInt(fileId, 10);
                const safeProgress = Math.max(0, Math.min(100, Math.round(Number(progress) || 0)));
                if (!Number.isFinite(safeFileId) || safeFileId <= 0) return true;

                const r = await fetch('../partial/save_progress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ file_id: String(safeFileId), progress: String(safeProgress) })
                });
                return r.ok;
            }));

            if (results.every(Boolean)) {
                sessionStorage.removeItem('guest_progress');
                sessionStorage.removeItem('guest_selected_module');
            }
        } catch (err) {
            console.error('Failed to sync guest progress after login:', err);
        }
    }

    async function bootstrapStudyMaterials() {
        buildMaterialMeta();
        prereqBootstrapDone = false;
        postTestReady = false;
        progressReady = false;

        showPrereqLoader();
        setLearningButtonsDisabled(false);

        const failSafe = setTimeout(() => {
            if (!prereqBootstrapDone) {
                console.warn('Failsafe triggered: releasing study-materials loader.');
                postTestReady = true;
                progressReady = true;
                updatePrereqBootstrapState();
            }
        }, 8000);

        try {
            if (IS_GUEST) {
                guestSelectedModule = getGuestSelectedModuleFromBrowser();
                await loadPostTestStatus();
                await loadProgressData();
            } else {
                await loadPostTestStatus();
                await syncGuestProgressToDbOnce();
                await loadProgressData();
            }
        } catch (err) {
            console.error('bootstrapStudyMaterials error:', err);
            postTestReady = true;
            progressReady = true;
            updatePrereqBootstrapState();
        } finally {
            clearTimeout(failSafe);
            postTestReady = true;
            progressReady = true;
            updatePrereqBootstrapState();
        }
    }

    function getLockedMessage(reason) {
        const def = "Locked content: You failed to pass the exam, haven't taken the exam yet, or haven't finished the previous module.";
        if (reason === 'not_finished') return "Locked content: You haven't finished the previous module yet. Complete it (100%) before proceeding.";
        if (reason === 'not_taken') return "Locked content: You haven't taken the previous module's post test yet. Take and pass it to unlock the next module.";
        if (reason === 'failed') return 'Locked content: You failed to pass the exam. Please review the previous module/lesson and try again.';
        if (reason === 'guest_signup_required') return 'This module is locked. Complete the previous module and pass its post-test first. Sign in to save progress permanently and unlock downloads.';
        return def;
    }

    function showPrereqModal(prevMaterialId, reason = null) {
        prereqPrevMaterialId = prevMaterialId || null;
        const el = document.getElementById('prereqModalMessage');
        if (el) el.textContent = getLockedMessage(reason);
        const backBtn = document.getElementById('goBackToPrevBtn');
        if (backBtn) backBtn.style.display = prereqPrevMaterialId ? '' : 'none';
        const modalEl = document.getElementById('prereqModal');
        if (!modalEl || !window.bootstrap?.Modal) {
            alert(getLockedMessage(reason));
            return;
        }
        new bootstrap.Modal(modalEl).show();
    }

    function showGuestDownloadModal() {
        const modalEl = document.getElementById('guestDownloadModal');
        if (!modalEl || !window.bootstrap?.Modal) {
            window.location.href = '../signin.php';
            return;
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function initGuestDownloadTooltips() {
        if (!window.bootstrap?.Tooltip) return;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            bootstrap.Tooltip.getOrCreateInstance(el);
        });
    }

    function enforcePrerequisitesFromButton(btnEl) {
        if (!prereqBootstrapDone) {
            // Fail open after showing/hiding loader quickly so clicks do not feel broken.
            showPrereqLoader();
            setTimeout(hidePrereqLoader, 600);
            return true;
        }

        try {
            const card = btnEl.closest('.material-card');
            if (!card) return true;
            const currentId = String(card.dataset.id || '');

            const lock = getMaterialLockReason(currentId);
            if (!lock) return true;

            showPrereqModal(lock.previousMeta.materialId, lock.reason);
            return false;
        } catch (err) {
            console.error('Prereq check failed:', err);
            return true;
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getFileDisplayName(fileId) {
        const item = document.querySelector(`[data-file-id="${fileId}"]`);
        return item ? (item.dataset.fileName || 'File') : 'File';
    }

    function getYouTubeEmbed(url) {
        if (!url) return '';
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
        const match = String(url).match(regExp);
        const videoId = match && match[2].length === 11 ? match[2] : null;
        return videoId ? `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1` : url;
    }

    async function getYouTubeTitle(videoUrl) {
        const url = String(videoUrl || '').trim();
        if (!url) return 'YouTube Video';

        const videoIdMatch = url.match(/(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/watch\?.+&v=))([^"&?\/\s]{11})/i);
        if (!videoIdMatch) return 'YouTube Video';

        const videoId = videoIdMatch[1];
        if (YOUTUBE_TITLE_CACHE.has(videoId)) {
            return YOUTUBE_TITLE_CACHE.get(videoId);
        }

        try {
            const response = await fetch(`https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=${encodeURIComponent(videoId)}&format=json`, {
                cache: 'force-cache'
            });

            if (!response.ok) throw new Error(`YouTube oEmbed HTTP ${response.status}`);

            const data = await response.json();
            const title = String(data.title || '').trim() || 'YouTube Video';
            YOUTUBE_TITLE_CACHE.set(videoId, title);
            return title;
        } catch (err) {
            console.warn('Unable to fetch YouTube title:', err);
            YOUTUBE_TITLE_CACHE.set(videoId, 'YouTube Video');
            return 'YouTube Video';
        }
    }

    function shouldFetchVideoTitle(title) {
        const value = String(title || '').trim();
        return value === '' || value === 'Untitled Video' || value === 'YouTube Video' || value === 'Video Lesson';
    }

    async function hydrateYouTubeTitles() {
        const videoItems = Array.from(document.querySelectorAll('.material-item[data-file-type="youtube"], .material-item[data-file-type="video"]'));

        await Promise.all(videoItems.map(async item => {
            const currentTitle = item.dataset.fileName || item.querySelector('.file-title')?.textContent || '';
            if (!shouldFetchVideoTitle(currentTitle)) return;

            const path = item.dataset.filePath || '';
            const title = await getYouTubeTitle(path);
            if (!title || title === 'YouTube Video') return;

            item.dataset.fileName = title;
            const titleEl = item.querySelector('.file-title');
            if (titleEl) {
                titleEl.textContent = title;
                titleEl.title = title;
            }
        }));
    }

    function createModal(title = 'Viewer', extraClass = '') {
        const modal = document.createElement('div');
        modal.className = `modal fade ${extraClass}`;
        modal.innerHTML = `
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" style="min-height: 70vh;">
                    <div class="modal-header">
                        <h5 class="modal-title w-100 text-center">${escapeHtml(title)}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0" id="viewerBody">
                        <div class="d-flex justify-content-center align-items-center h-100 bg-light">
                            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        </div>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);
        return modal;
    }

    function registerCleanup(fn) {
        if (typeof fn === 'function') __progressState.cleanups.push(fn);
    }

    function runAndClearCleanups() {
        try {
            __progressState.cleanups.forEach(fn => {
                try { fn(); } catch (e) {}
            });
        } finally {
            __progressState.cleanups = [];
        }
    }

    function destroyModal() {
        runAndClearCleanups();

        if (currentModalInstance) {
            try {
                currentModalInstance.hide();
                currentModalInstance.dispose();
            } catch (e) {}
            currentModalInstance = null;
        }

        if (currentModalElement) {
            currentModalElement.querySelectorAll('iframe').forEach(iframe => iframe.src = 'about:blank');
            currentModalElement.remove();
            currentModalElement = null;
        }

        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    function saveProgress(fid, pct) {
        if (IS_GUEST) {
            saveGuestProgressToBrowser(fid, pct);
            return;
        }

        fetch('../partial/save_progress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ file_id: String(fid), progress: String(Math.round(clampPct(pct))) })
        }).catch(() => {});
    }

    function clampPct(pct) {
        pct = Number(pct);
        if (!Number.isFinite(pct)) return 0;
        return Math.max(0, Math.min(100, pct));
    }

    function saveProgressSmart(fileId, pct, opts = {}) {
        const { force = false, minDelta = 2 } = opts;
        pct = clampPct(pct);
        const prev = __progressState.lastSavedPct.get(String(fileId)) ?? 0;
        if (pct < prev) pct = prev;
        const now = Date.now();
        const lastAt = __progressState.lastSentAt.get(String(fileId)) ?? 0;
        if (!force && now - lastAt < 3000) return;
        if (!force && pct !== 100 && (pct - prev) < minDelta) return;
        __progressState.lastSavedPct.set(String(fileId), pct);
        __progressState.lastSentAt.set(String(fileId), now);
        saveProgress(fileId, Math.round(pct));
    }

    function updateProgressUiSmart(fileId, pct) {
        pct = Math.round(clampPct(pct));
        const prevUi = __progressState.lastUiPct.get(String(fileId)) ?? 0;
        if (pct < prevUi) pct = prevUi;
        if (pct === prevUi) return;
        __progressState.lastUiPct.set(String(fileId), pct);
        updateProgressBar(fileId, pct);
    }

    function getResumableProgressPct(fileId) {
        const uiPct = __progressState.lastUiPct.get(String(fileId));
        const savedPct = __progressState.lastSavedPct.get(String(fileId));
        const bestKnown = Math.max(
            Number.isFinite(Number(uiPct)) ? Number(uiPct) : 0,
            Number.isFinite(Number(savedPct)) ? Number(savedPct) : 0
        );
        return clampPct(bestKnown);
    }

    function updateProgressBar(fid, pct) {
        fetch(`../partial/get_material_from_file.php?fid=${encodeURIComponent(fid)}`)
            .then(r => r.json())
            .then(d => {
                if (!d?.material_id) return;
                const cont = document.getElementById('prog-' + d.material_id);
                if (!cont) return;

                let item = cont.querySelector(`[data-fid="${fid}"]`);
                const name = getFileDisplayName(fid);
                const safePct = Math.round(clampPct(pct));

                if (!item) {
                    if (!cont.querySelector('.progress-header')) {
                        cont.innerHTML = '<div class="progress-header"><i class="fas fa-chart-line"></i> Your Progress</div>';
                    }
                    cont.insertAdjacentHTML('beforeend', `
                        <div class="progress-item" data-fid="${fid}">
                            <div class="progress-item-left">
                                <div class="progress-item-name" title="${escapeHtml(name)}">${escapeHtml(name)}</div>
                                <div class="progress-bar-wrapper"><div class="progress-bar-fill" style="width:${safePct}%"></div></div>
                            </div>
                            <div class="progress-item-right">
                                <span class="progress-percentage">${safePct}%</span>
                                ${safePct >= 100 ? '<i class="fas fa-check-circle progress-check"></i>' : ''}
                            </div>
                        </div>`);
                } else {
                    item.querySelector('.progress-bar-fill').style.width = safePct + '%';
                    item.querySelector('.progress-percentage').textContent = safePct + '%';
                    if (safePct >= 100 && !item.querySelector('.progress-check')) {
                        item.querySelector('.progress-item-right').insertAdjacentHTML('beforeend', '<i class="fas fa-check-circle progress-check"></i>');
                    } else if (safePct < 100) {
                        const check = item.querySelector('.progress-check');
                        if (check) check.remove();
                    }
                }

                refreshMaterialProgressState(d.material_id);
            })
            .catch(err => console.error('Failed to update progress UI:', err));
    }

    function setupPdfTimeTracking(fileId, url) {
        const MIN_SECONDS = 60;
        const MAX_SECONDS = 1800;
        const SECONDS_PER_MB = 60;

        async function getPdfSizeBytes(u) {
            try {
                const head = await fetch(u, { method: 'HEAD', cache: 'no-store' });
                if (head?.ok) {
                    const len = head.headers.get('content-length');
                    if (len && !isNaN(Number(len))) return Number(len);
                }
            } catch (e) {}
            return null;
        }

        function estimateSeconds(bytes) {
            if (!bytes || bytes <= 0) return 600;
            const mb = bytes / (1024 * 1024);
            const est = Math.ceil(mb * SECONDS_PER_MB);
            return Math.max(MIN_SECONDS, Math.min(MAX_SECONDS, est));
        }

        getPdfSizeBytes(url).then(bytes => {
            startTimeBasedTracking(fileId, estimateSeconds(bytes), { minDelta: 1 });
        });
    }

    function startTimeBasedTracking(fileId, secondsToComplete = 600, opts = {}) {
        const { tickSeconds = 5, tickMs = 5000, minDelta = 2 } = opts;
        const resumedPct = getResumableProgressPct(fileId);

        if (resumedPct >= 100) {
            updateProgressUiSmart(fileId, 100);
            saveProgressSmart(fileId, 100, { force: true, minDelta });
            return;
        }

        let seconds = (resumedPct / 100) * Math.max(1, secondsToComplete);
        if (resumedPct > 0) updateProgressUiSmart(fileId, resumedPct);

        const intervalId = setInterval(() => {
            seconds += tickSeconds;
            const pct = (seconds / Math.max(1, secondsToComplete)) * 100;
            updateProgressUiSmart(fileId, pct);
            saveProgressSmart(fileId, pct, { minDelta });
        }, tickMs);

        const warmup = resumedPct <= 0 ? setTimeout(() => {
            updateProgressUiSmart(fileId, 1);
            saveProgressSmart(fileId, 1, { force: true, minDelta });
        }, 1200) : null;

        registerCleanup(() => {
            clearInterval(intervalId);
            if (warmup) clearTimeout(warmup);
            const pct = (seconds / Math.max(1, secondsToComplete)) * 100;
            saveProgressSmart(fileId, pct, { force: true, minDelta });
        });
    }

    function ensureYouTubeApi() {
        if (window.YT && window.YT.Player) return Promise.resolve();
        if (__ytApiPromise) return __ytApiPromise;

        __ytApiPromise = new Promise(resolve => {
            if (!document.querySelector('script[src*="youtube.com/iframe_api"]')) {
                const tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                document.head.appendChild(tag);
            }

            const prev = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = function() {
                try { if (typeof prev === 'function') prev(); } catch (e) {}
                resolve();
            };

            const poll = setInterval(() => {
                if (window.YT && window.YT.Player) {
                    clearInterval(poll);
                    resolve();
                }
            }, 200);
            setTimeout(() => {
                clearInterval(poll);
                resolve();
            }, 8000);
        });

        return __ytApiPromise;
    }

    function extractYouTubeId(url) {
        if (!url) return null;
        const match = String(url).match(/(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/watch\?.+&v=))([^"&?\/\s]{11})/i);
        return match ? match[1] : null;
    }

    function renderYouTubePlayerHtml(fileId, url) {
        const vid = extractYouTubeId(url);
        const iframeId = `yt-player-${fileId}-${Date.now()}`;
        const origin = encodeURIComponent(window.location.origin);
        const src = vid ? `https://www.youtube.com/embed/${vid}?enablejsapi=1&origin=${origin}&rel=0&modestbranding=1` : getYouTubeEmbed(url);
        return `
            <div class="ratio ratio-16x9 h-100">
                <iframe id="${iframeId}" data-yt-file-id="${fileId}" data-yt-video-id="${vid || ''}" src="${src}" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture" style="border:none;"></iframe>
            </div>`;
    }

    function setupYouTubeProgressTracking(fileId, url) {
        const vid = extractYouTubeId(url);
        if (!vid) {
            startTimeBasedTracking(fileId, 420);
            return;
        }

        ensureYouTubeApi().then(() => {
            const iframe = document.querySelector(`iframe[data-yt-file-id="${fileId}"]`);
            if (!iframe || !window.YT?.Player) {
                startTimeBasedTracking(fileId, 420);
                return;
            }

            let player = null;
            let tick = null;

            try {
                player = new YT.Player(iframe.id, {
                    events: {
                        onReady: () => {
                            tick = setInterval(() => {
                                try {
                                    const dur = player.getDuration?.() || 0;
                                    const cur = player.getCurrentTime?.() || 0;
                                    if (!dur) return;
                                    const pct = (cur / dur) * 100;
                                    updateProgressUiSmart(fileId, pct);
                                    saveProgressSmart(fileId, pct);
                                } catch (e) {}
                            }, 1000);
                        },
                        onStateChange: (e) => {
                            try {
                                const dur = player.getDuration?.() || 0;
                                const cur = player.getCurrentTime?.() || 0;
                                if (dur) {
                                    const pct = (cur / dur) * 100;
                                    updateProgressUiSmart(fileId, pct);
                                    saveProgressSmart(fileId, pct, { force: true });
                                }
                                if (e.data === YT.PlayerState.ENDED) {
                                    updateProgressUiSmart(fileId, 100);
                                    saveProgressSmart(fileId, 100, { force: true });
                                }
                            } catch (err) {}
                        }
                    }
                });
            } catch (e) {
                startTimeBasedTracking(fileId, 420);
                return;
            }

            registerCleanup(() => {
                if (tick) clearInterval(tick);
                try {
                    const dur = player?.getDuration?.() || 0;
                    const cur = player?.getCurrentTime?.() || 0;
                    if (dur) saveProgressSmart(fileId, (cur / dur) * 100, { force: true });
                } catch (e) {}
                try { player?.destroy?.(); } catch (e) {}
            });
        });
    }

    async function openSingleFile(fid) {
        try {
            const r = await fetch(`../partial/get_one_file.php?fid=${encodeURIComponent(fid)}`, { cache: 'no-store' });
            const d = await r.json();

            if (!d?.type) {
                alert('File not found');
                return;
            }

            let title = d.title || (d.type === 'pdf' ? 'PDF Document' : 'Video Lesson');
            if ((d.type === 'youtube' || d.type === 'video') && shouldFetchVideoTitle(title)) {
                title = await getYouTubeTitle(d.path);
            }
            const modal = createModal(title, 'custom-modal');
            const body = modal.querySelector('#viewerBody');

            if (d.type === 'pdf') {
                body.innerHTML = `<iframe src="../${d.path}" style="width:100%;height:100%;border:none;"></iframe>`;
                setupPdfTimeTracking(fid, `../${d.path}`);
            } else if (d.type === 'youtube' || d.type === 'video') {
                body.innerHTML = renderYouTubePlayerHtml(fid, d.path);
                setupYouTubeProgressTracking(fid, d.path);
            } else {
                body.innerHTML = '<div class="p-4 text-center text-muted">Unsupported file type.</div>';
            }

            currentModalInstance = new bootstrap.Modal(modal, { backdrop: 'static', keyboard: false });
            currentModalElement = modal;
            modal.addEventListener('hidden.bs.modal', destroyModal, { once: true });
            currentModalInstance.show();
        } catch (err) {
            console.error('Error loading content:', err);
            alert('Error loading content');
        }
    }

    window.openFile = function(fid) {
        destroyModal();
        openSingleFile(fid);
    };

    function bindEvents() {
        document.querySelectorAll('.topic-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.topic;
                document.querySelectorAll('.topic-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                document.querySelectorAll('.topic-section').forEach(sec => sec.classList.toggle('active', sec.dataset.topic === target));
            });
        });

        const goBackBtn = document.getElementById('goBackToPrevBtn');
        if (goBackBtn) {
            goBackBtn.addEventListener('click', () => {
                const mid = prereqPrevMaterialId;
                const modalEl = document.getElementById('prereqModal');
                const inst = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
                inst?.hide();

                if (!mid) return;
                const btn = document.querySelector(`.start-learning-btn[data-id="${mid}"]`);
                if (!btn) return;
                bypassPrereqOnce = true;
                btn.click();
            });
        }

        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', async e => {
                e.preventDefault();
                e.stopPropagation();

                if (!(await ensureGuestModuleSelectionFromTrigger(btn))) {
                    showPrereqModal(null, 'guest_signup_required');
                    return;
                }

                if (!bypassPrereqOnce) {
                    if (!enforcePrerequisitesFromButton(btn)) return;
                } else {
                    bypassPrereqOnce = false;
                }

                destroyModal();
                openSingleFile(btn.dataset.fid);
            });
        });

        document.querySelectorAll('.download-btn').forEach(link => {
            link.addEventListener('click', async e => {
                e.stopPropagation();

                if (!(await ensureGuestModuleSelectionFromTrigger(link))) {
                    e.preventDefault();
                    showPrereqModal(null, 'guest_signup_required');
                    return;
                }

                if (!bypassPrereqOnce) {
                    if (!enforcePrerequisitesFromButton(link)) {
                        e.preventDefault();
                        return;
                    }
                } else {
                    bypassPrereqOnce = false;
                }

                if (IS_GUEST) {
                    e.preventDefault();
                    showGuestDownloadModal();
                    return;
                }
            });
        });

        document.querySelectorAll('.start-learning-btn').forEach(btn => {
            btn.addEventListener('click', async e => {
                e.preventDefault();
                e.stopPropagation();

                if (!(await ensureGuestModuleSelectionFromTrigger(btn))) {
                    showPrereqModal(null, 'guest_signup_required');
                    return;
                }

                if (!bypassPrereqOnce) {
                    if (!enforcePrerequisitesFromButton(btn)) return;
                } else {
                    bypassPrereqOnce = false;
                }

                const mid = btn.dataset.id;
                const card = btn.closest('.material-card');
                if (!mid || !card) return;
                destroyModal();

                try {
                    const resp = await fetch(`../partial/get_material_files.php?id=${encodeURIComponent(mid)}`, { cache: 'no-store' });
                    const data = await resp.json();
                    const materialTitle = card.querySelector('.card-title')?.textContent?.trim() || 'Study Material';
                    const materialDesc = card.querySelector('.card-description')?.textContent?.trim() || '';
                    const pdfs = Array.isArray(data.pdfs) ? data.pdfs : [];
                    const videos = Array.isArray(data.videos) ? data.videos : [];

                    if (!pdfs.length && !videos.length) {
                        alert('No content available.');
                        return;
                    }

                    const modal = createModal(materialTitle, 'custom-modal');
                    const body = modal.querySelector('#viewerBody');
                    let tabsHtml = '';
                    let panesHtml = '';
                    const hasPdfs = pdfs.length > 0;
                    const hasVideos = videos.length > 0;

                    if (hasPdfs) {
                        tabsHtml += `<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-pdfs">PDFs (${pdfs.length})</a></li>`;
                        let list = '';
                        pdfs.forEach(f => {
                            const fname = f.title || String(f.path || '').split('/').pop().replace(/^pdf_[a-f0-9]{11}\./i, '').replace(/\.pdf$/i, '').replace(/_/g, ' ');
                            list += `
                                <div class="file-list-item pdf" data-open-file-id="${f.id}">
                                    <i class="fas fa-file-pdf file-icon"></i>
                                    <div class="file-info"><div class="file-title">${escapeHtml(fname)}</div></div>
                                    <i class="fas fa-arrow-right action-icon"></i>
                                </div>`;
                        });
                        panesHtml += `<div class="tab-pane fade show active" id="tab-pdfs">${list}</div>`;
                    }

                    if (hasVideos) {
                        const active = !hasPdfs ? ' active' : '';
                        tabsHtml += `<li class="nav-item"><a class="nav-link${active}" data-bs-toggle="tab" href="#tab-videos">Videos (${videos.length})</a></li>`;
                        let list = '';
                        for (const v of videos) {
                            let displayTitle = v.title;
                            if (!displayTitle || displayTitle.trim() === '' || displayTitle === 'Untitled Video' || displayTitle === 'YouTube Video') {
                                displayTitle = await getYouTubeTitle(v.path);
                            }
                            list += `
                                <div class="file-list-item video" data-open-file-id="${v.id}">
                                    <i class="fas fa-play-circle file-icon"></i>
                                    <div class="file-info"><div class="file-title">${escapeHtml(displayTitle)}</div></div>
                                    <i class="fas fa-arrow-right action-icon"></i>
                                </div>`;
                        }
                        const showActive = !hasPdfs ? ' show active' : '';
                        panesHtml += `<div class="tab-pane fade${showActive}" id="tab-videos">${list}</div>`;
                    }

                    body.innerHTML = `
                        <div class="h-100 d-flex flex-column">
                            <div class="border-bottom bg-white px-3 py-3 px-md-4">
                                <h4 class="mb-3 fw-bold text-center text-dark">${escapeHtml(materialTitle)}</h4>
                                <div class="material-desc">${escapeHtml(materialDesc || 'Select a resource to begin studying.')}</div>
                            </div>
                            <ul class="nav nav-tabs px-2 pt-2 border-bottom-0">${tabsHtml}</ul>
                            <div class="tab-content flex-grow-1 overflow-auto p-2 p-md-3">${panesHtml}</div>
                        </div>`;

                    body.querySelectorAll('[data-open-file-id]').forEach(item => {
                        item.addEventListener('click', () => {
                            window.openFile(item.dataset.openFileId);
                        });
                    });

                    currentModalInstance = new bootstrap.Modal(modal, { backdrop: 'static', keyboard: false });
                    currentModalElement = modal;
                    currentModalInstance.show();
                } catch (err) {
                    console.error('Error loading materials:', err);
                    alert('Error loading materials');
                }
            });
        });
    }

    bindEvents();
    bootstrapStudyMaterials().finally(() => {
        prereqBootstrapDone = true;
        postTestReady = true;
        progressReady = true;
        setLearningButtonsDisabled(false);
        hydrateYouTubeTitles();
        hidePrereqLoader();
    });
});
</script>
