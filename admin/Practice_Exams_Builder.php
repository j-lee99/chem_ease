<?php
session_start();
require_once '../partial/db_conn.php';

$role = $_SESSION['role'] ?? '';
$isAdmin = ($role === 'admin');
$isSuperAdmin = ($role === 'super_admin');

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT full_name, profile_image
    FROM users
    WHERE id = ? AND is_deleted = 0
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$full_name = $user['full_name'] ?? 'Admin';
$profile_image = $user['profile_image'] ?? '';

$initials = '';
$name_parts = explode(' ', trim($full_name));

foreach ($name_parts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    if (strlen($initials) >= 2) {
        break;
    }
}

if (empty($initials)) {
    $initials = 'A';
}

if (!isset($_SESSION['user_id']) || !in_array(($_SESSION['role'] ?? ''), ['admin', 'super_admin'], true)) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php
        if ($isSuperAdmin) {
            echo "ChemEase Super Admin Panel - Exam Builder";
        } elseif ($isAdmin) {
            echo "ChemEase Admin Panel - Exam Builder";
        } else {
            echo "ChemEase - Exam Builder";
        }
        ?>
    </title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="pe.css">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <meta name="msapplication-TileColor" content="#0d6efd">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <style>
        :root {
            --primary: #17a2b8;
            --builder-bg: #ffffff;
            --builder-surface: #f8fafc;
            --builder-surface-2: #f1f5f9;
            --builder-border: #dbe3ea;
            --builder-border-strong: #c7d2de;
            --builder-text: #1f2937;
            --builder-muted: #6b7280;
            --builder-heading: #111827;
            --builder-primary: #1aa3b8;
            --builder-primary-dark: #14889a;
            --builder-primary-soft: rgba(26, 163, 184, 0.12);
            --builder-success: #1f9d55;
            --builder-success-soft: rgba(31, 157, 85, 0.12);
            --builder-danger: #dc3545;
            --builder-danger-soft: rgba(220, 53, 69, 0.1);
            --builder-warning: #f0b429;
            --builder-warning-soft: rgba(240, 180, 41, 0.18);
            --builder-shadow-sm: 0 4px 12px rgba(15, 23, 42, 0.06);
            --builder-shadow-md: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .question-block {
            position: relative;
            margin-bottom: 16px;
            padding: 20px;
            border: 1px solid var(--builder-border);
            border-radius: 16px;
            background: var(--builder-bg);
            box-shadow: var(--builder-shadow-sm);
        }

        .question-block:hover {
            border-color: rgba(26, 163, 184, 0.45);
            box-shadow: 0 0 0 0.18rem rgba(26, 163, 184, 0.08), 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .choice-row {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
            position: relative;
        }

        .choice-row input[type=text] {
            flex: 1;
        }

        .remove-choice {
            cursor: pointer;
            color: var(--builder-danger);
            padding: 6px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .remove-choice:hover {
            background-color: var(--builder-danger-soft);
        }

        .step {
            cursor: pointer;
            padding: 9px 16px;
            border-radius: 999px;
            transition: all 0.25s ease;
            font-weight: 600;
            color: var(--builder-muted);
            background: var(--builder-bg);
            border: 1px solid var(--builder-border);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }

        .step:hover {
            background-color: #f8fcfd;
            border-color: rgba(26, 163, 184, 0.28);
            transform: translateY(-1px);
        }

        .step.completed {
            color: var(--builder-success);
            background-color: var(--builder-success-soft);
            border-color: rgba(31, 157, 85, 0.22);
        }

        .step.active {
            font-weight: 700;
            color: var(--builder-primary-dark);
            background-color: var(--builder-primary-soft);
            border-color: rgba(26, 163, 184, 0.35);
        }

        .media-upload {
            border: 1px dashed var(--builder-border-strong);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            margin: 10px 0;
            background: var(--builder-surface);
            color: var(--builder-muted);
        }

        .media-upload:hover,
        .media-upload.dragover {
            border-color: var(--builder-primary);
            background-color: #f3fbfc;
        }

        .choice-preview {
            padding: 10px 12px;
            margin: 4px 0;
            border-radius: 10px;
            font-size: 14px;
            color: var(--builder-text);
        }

        .choice-preview.correct {
            background-color: var(--builder-success-soft);
            border-left: 4px solid var(--builder-success);
        }

        .choice-preview.incorrect {
            background-color: rgba(220, 53, 69, 0.08);
            border-left: 4px solid #ef4444;
        }

        .question-image-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 10px;
            margin: 10px 0;
            border: 1px solid var(--builder-border);
            background: #ffffff;
            object-fit: cover;
        }

        .actions-cell {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 12px;
        }
        
        .profile-dropdown {
    position: relative;
}

.profile-trigger {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255,255,255,0.35);
    background: rgba(255,255,255,0.1);
    transition: all 0.2s ease;
}

.profile-trigger:hover {
    background: rgba(255,255,255,0.15);
    border-color: rgba(255,255,255,0.6);
}

.profile-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-initials {
    width: 100%;
    height: 100%;
    background: #ffffff;
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
}

.dropdown-menu {
    min-width: 230px;
    border: none;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    overflow: hidden;
}

.dropdown-item {
    padding: 10px 16px;
    font-size: 14px;
}

.dropdown-item i {
    width: 18px;
}

        .action-btn.view { background-color: #0d6efd; color: white; }
        .action-btn.view:hover { background-color: #0b59d0; }
        .action-btn.edit { background-color: #1ea95f; color: white; }
        .action-btn.edit:hover { background-color: #17854a; }
        .action-btn.delete { background-color: #dc3545; color: white; }
        .action-btn.delete:hover { background-color: #bf2c3b; }

        .modal-xl {
            max-width: 95%;
        }

        .review-question {
            border: 1px solid var(--builder-border);
            padding: 16px;
            margin: 10px 0;
            border-radius: 14px;
            background: var(--builder-bg);
            color: var(--builder-text);
        }

        .error-message {
            color: var(--builder-danger);
            font-size: 12px;
            margin-top: 5px;
        }

        .exam-builder-page-shell .modal-content {
            background: #ffffff;
            color: var(--builder-text);
            border: 1px solid var(--builder-border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        }

        .exam-builder-page-shell .modal-header,
        .exam-builder-page-shell .modal-footer {
            border-color: var(--builder-border);
            background: #f8fbfc;
        }

        .exam-builder-page-shell .modal-header {
            padding: 18px 24px;
        }

        .exam-builder-page-shell .modal-title {
            font-weight: 700;
            letter-spacing: 0.02em;
            color: var(--builder-heading);
        }

        .exam-builder-page-shell .btn-close {
            opacity: .65;
        }

        .exam-builder-page-shell .btn-close:hover {
            opacity: 1;
        }

        .exam-builder-page-shell .modal-body {
            background: linear-gradient(180deg, #fbfeff 0%, #f5f8fb 100%);
            padding: 20px;
        }

        .builder-shell {
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr) 360px;
            gap: 20px;
            min-height: 72vh;
            align-items: start;
        }

        .builder-sidebar,
        .builder-main,
        .builder-rightbar {
            background: var(--builder-bg);
            border: 1px solid var(--builder-border);
            border-radius: 18px;
            box-shadow: var(--builder-shadow-sm);
        }

        .builder-sidebar,
        .builder-rightbar {
            padding: 18px;
        }

        .builder-sidebar {
            position: sticky;
            top: 20px;
            align-self: start;
            height: fit-content;
            max-height: calc(100vh - 120px);
            overflow: hidden;
        }

        .builder-main {
            padding: 22px;
            overflow: visible;
        }

        .builder-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--builder-muted);
            font-weight: 700;
            margin-bottom: 12px;
        }

        .builder-question-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: calc(100vh - 250px);
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 6px;
            scroll-behavior: smooth;
        }

        .question-nav-card {
            border: 1px solid var(--builder-border);
            background: var(--builder-surface);
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all .2s ease;
        }

        .question-nav-card:hover {
            border-color: rgba(26, 163, 184, 0.25);
            background: #f4fcfd;
        }

        .question-nav-card.active {
            border-color: rgba(26, 163, 184, 0.38);
            background: #eefbfd;
            box-shadow: inset 0 0 0 1px rgba(26, 163, 184, 0.08);
        }

        .question-nav-index {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #ffffff;
            border: 1px solid var(--builder-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--builder-text);
            flex-shrink: 0;
        }

        .question-nav-card.active .question-nav-index {
            background: var(--builder-primary);
            color: #ffffff;
            border-color: var(--builder-primary);
        }

        .question-nav-meta {
            min-width: 0;
            flex: 1;
        }

        .question-nav-type {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--builder-primary-dark);
            font-weight: 700;
            margin-bottom: 4px;
        }

        .question-nav-preview {
            font-size: 12px;
            color: var(--builder-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .question-nav-actions {
            display: flex;
            gap: 6px;
        }

        .builder-side-btn,
        .builder-mini-btn {
            border-radius: 12px;
            border: 1px solid var(--builder-border);
            background: #ffffff;
            color: var(--builder-text);
            transition: .2s ease;
        }

        .builder-side-btn:hover,
        .builder-mini-btn:hover {
            background: #f8fbfc;
            border-color: rgba(26, 163, 184, 0.3);
            color: var(--builder-primary-dark);
        }

        .builder-side-btn {
            width: 100%;
            padding: 11px 14px;
            font-weight: 600;
            margin-top: 14px;
        }

        .builder-mini-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .builder-stat-card {
            border: 1px solid var(--builder-border);
            background: var(--builder-surface);
            border-radius: 14px;
            padding: 14px;
            margin-top: 16px;
        }

        .builder-stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--builder-text);
            font-size: 13px;
            margin-bottom: 10px;
        }

        .builder-stat-row strong {
            color: var(--builder-heading);
        }

        .builder-stat-row:last-child {
            margin-bottom: 0;
        }

        .builder-main-top {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .builder-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--builder-primary-soft);
            color: var(--builder-primary-dark);
            border: 1px solid rgba(26, 163, 184, 0.18);
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 10px;
        }

        .builder-heading {
            font-size: 28px;
            line-height: 1.1;
            font-weight: 700;
            color: var(--builder-heading);
            margin-bottom: 4px;
        }

        .builder-subheading {
            color: var(--builder-muted);
            font-size: 14px;
        }

        .builder-top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .builder-progress-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--builder-surface);
            border: 1px solid var(--builder-border);
            color: var(--builder-text);
            font-size: 12px;
            font-weight: 600;
        }

        .builder-primary-btn,
        .builder-outline-btn {
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .builder-primary-btn {
            background: var(--builder-primary);
            color: #ffffff;
            border-color: var(--builder-primary);
        }

        .builder-primary-btn:hover {
            background: var(--builder-primary-dark);
            border-color: var(--builder-primary-dark);
        }

        .builder-outline-btn {
            background: #ffffff;
            color: var(--builder-text);
            border-color: var(--builder-border);
        }

        .builder-outline-btn:hover {
            background: #f8fbfc;
            border-color: rgba(26, 163, 184, 0.3);
            color: var(--builder-primary-dark);
        }

        .builder-panel-card {
            background: #ffffff;
            border: 1px solid var(--builder-border);
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        }

        .builder-panel-card .form-control,
        .builder-panel-card .form-select,
        .question-block .form-control,
        .question-block .form-select {
            background-color: #ffffff;
            border-color: var(--builder-border-strong);
            color: var(--builder-text);
            border-radius: 12px;
        }

        .builder-panel-card .form-control::placeholder,
        .question-block .form-control::placeholder,
        .question-block textarea::placeholder {
            color: #9aa4b2;
        }

        .builder-panel-card .form-control:focus,
        .builder-panel-card .form-select:focus,
        .question-block .form-control:focus,
        .question-block .form-select:focus,
        .question-block textarea:focus {
            border-color: rgba(26, 163, 184, 0.5);
            box-shadow: 0 0 0 .18rem rgba(26, 163, 184, .12);
        }

        .builder-panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .builder-panel-heading h6,
        .builder-panel-heading h5 {
            margin: 0;
            font-weight: 700;
            color: var(--builder-heading);
        }

        .builder-muted {
            color: var(--builder-muted);
            font-size: 12px;
        }

        .builder-settings-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .builder-right-group {
            margin-bottom: 20px;
        }

        .builder-right-card {
            border: 1px solid var(--builder-border);
            background: var(--builder-surface);
            border-radius: 14px;
            padding: 14px;
        }

        .builder-right-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .builder-right-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--builder-text);
            font-size: 13px;
            gap: 12px;
        }

        .builder-right-line strong {
            color: var(--builder-heading);
            text-align: right;
        }

        .builder-right-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid var(--builder-border);
            background: #ffffff;
            color: var(--builder-text);
            font-size: 12px;
            margin: 0 6px 6px 0;
        }

        .builder-right-tip {
            background: #eefbfd;
            border: 1px solid rgba(26, 163, 184, 0.2);
            border-radius: 14px;
            padding: 14px;
            color: var(--builder-text);
            font-size: 13px;
        }

        .builder-step-content {
            display: none;
        }

        .builder-step-content.active {
            display: block;
        }

        .builder-footer-nav {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-top: 18px;
        }

        .builder-footer-center {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .builder-footer-btn {
            padding: 10px 15px;
            border-radius: 12px;
            font-weight: 600;
            border: 1px solid var(--builder-border);
            background: #ffffff;
            color: var(--builder-text);
        }

        .builder-footer-btn:hover {
            background: #f8fbfc;
            border-color: rgba(26, 163, 184, 0.28);
            color: var(--builder-primary-dark);
        }

        .builder-footer-btn.primary {
            background: var(--builder-primary);
            border-color: var(--builder-primary);
            color: #ffffff;
            font-weight: 700;
        }

        .builder-footer-btn.primary:hover {
            background: var(--builder-primary-dark);
            border-color: var(--builder-primary-dark);
            color: #ffffff;
        }

        .builder-summary-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .builder-summary-item {
            border: 1px solid var(--builder-border);
            border-radius: 14px;
            padding: 14px;
            background: #ffffff;
        }

        .builder-summary-item strong {
            color: var(--builder-heading);
        }

        .exam-builder-page-shell .alert-info {
            background: #eefbfd !important;
            color: var(--builder-text) !important;
            border: 1px solid rgba(26, 163, 184, 0.18) !important;
        }

        .exam-builder-page-shell .text-muted {
            color: var(--builder-muted) !important;
        }

        .exam-builder-page-shell .form-check-input:checked {
            background-color: var(--builder-primary);
            border-color: var(--builder-primary);
        }

        .exam-builder-page-shell .btn-success {
            background-color: var(--builder-success);
            border-color: var(--builder-success);
        }

        .exam-builder-page-shell .btn-success:hover {
            background-color: #188347;
            border-color: #188347;
        }

        .exam-builder-page-shell .btn-outline-primary {
            color: var(--builder-primary-dark);
            border-color: rgba(26, 163, 184, 0.35);
        }

        .exam-builder-page-shell .btn-outline-primary:hover {
            background: var(--builder-primary);
            border-color: var(--builder-primary);
            color: #ffffff;
        }

        .exam-builder-page-shell .btn-outline-danger {
            color: var(--builder-danger);
            border-color: rgba(220, 53, 69, 0.25);
        }

        .exam-builder-page-shell .btn-outline-danger:hover {
            background: var(--builder-danger);
            color: #ffffff;
        }
        
        .builder-right-tip {
            background: #fff8e8;
            border: 1px solid #f5d48a;
            border-left: 5px solid #f0ad4e;
            border-radius: 14px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .builder-tip-header {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: #8a5a00;
        }
        
        .builder-tip-header i {
            font-size: 18px;
            color: #f0ad4e;
        }
        
        .builder-tip-text {
            font-size: 13px;
            color: #5f5f5f;
            line-height: 1.6;
            margin: 0;
        }
        
        .builder-tip-item {
            background: #ffffff;
            border: 1px solid #f1e2b8;
            border-radius: 10px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .builder-tip-label {
            font-size: 13px;
            font-weight: 700;
            color: #8a5a00;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .builder-tip-item small {
            color: #444;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .builder-tip-example {
            color: #7a7a7a;
            font-style: italic;
        }
        
        .builder-tip-warning {
            background: #fff0f0;
            border: 1px solid #f3c2c2;
            color: #b54747;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .quick-info-card {
            background: linear-gradient(135deg, #eefbfd 0%, #f8fdff 100%);
            border: 1px solid rgba(26, 163, 184, 0.2);
            border-left: 5px solid var(--builder-primary);
        }
        
        .quick-info-card .builder-tip-header {
            color: var(--builder-primary-dark);
        }
        
        .quick-info-card .builder-tip-header i {
            color: var(--builder-primary);
        }
        
        .quick-info-card .builder-tip-item {
            border-color: rgba(26, 163, 184, 0.15);
            background: rgba(255, 255, 255, 0.9);
        }
        
        .quick-info-card .builder-tip-label {
            color: var(--builder-primary-dark);
        }

        @media (max-width: 1400px) {
            .builder-shell {
                grid-template-columns: 220px minmax(0, 1fr) 320px;
            }
        }

        @media (max-width: 1199px) {
            .builder-shell {
                grid-template-columns: 1fr;
            }

            .builder-sidebar,
            .builder-rightbar {
                position: static;
                top: auto;
                max-height: none;
                overflow: visible;
                order: 2;
            }

            .builder-question-list {
                max-height: 320px;
            }

            .builder-main {
                order: 1;
            }
        }

        @media (max-width: 767px) {
            .builder-main-top,
            .builder-footer-nav,
            .progress-step {
                flex-direction: column;
                align-items: stretch !important;
            }

            .builder-settings-grid {
                grid-template-columns: 1fr;
            }

            .builder-top-actions,
            .builder-footer-center {
                width: 100%;
            }

            .builder-top-actions > *,
            .builder-footer-center > * {
                width: 100%;
            }
        }

        .exam-builder-page {
            padding: 24px;
        }

        .builder-page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .builder-page-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #eefbfd;
            color: var(--builder-primary-dark);
            border: 1px solid rgba(26, 163, 184, 0.16);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .builder-page-title {
            margin: 0 0 6px;
            color: var(--builder-heading);
            font-size: 32px;
            font-weight: 800;
        }

        .builder-page-subtitle {
            margin: 0;
            color: var(--builder-muted);
            max-width: 720px;
        }

        .builder-page-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .builder-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 16px;
            border-radius: 12px;
            border: 1px solid var(--builder-border);
            background: #ffffff;
            color: var(--builder-text);
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .builder-page-link:hover {
            color: var(--builder-primary-dark);
            border-color: rgba(26, 163, 184, 0.28);
            background: #f8fbfc;
        }

        .builder-page-card {
            background: transparent;
        }

        .exam-builder-page-shell {
            overflow: visible;
        }

    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand">
            <img src="../images/logo.png" alt="ChemEase Logo">
            <span>ChemEase</span>
            <button class="collapse-btn ms-auto" onclick="toggleSidebar()">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-item"><a href="index.php" class="nav-link" data-section="dashboard"><i class="fas fa-home"></i><span>Dashboard</span></a></div>
            <div class="nav-item"><a href="Users.php" class="nav-link" data-section="users"><i class="fas fa-users"></i><span>Users</span></a></div>
            <?php if ($isAdmin): ?>
                <div class="nav-item"><a href="Learning_Material.php" class="nav-link" data-section="learning"><i class="fas fa-book"></i><span>Learning Materials</span></a></div>
                <div class="nav-item"><a href="Practice_Exams.php" class="nav-link active" data-section="exams"><i class="fas fa-clipboard-list"></i><span>Practice Exams</span></a></div>
            <?php endif ?>
            <?php if ($isSuperAdmin || $isAdmin): ?>
                <div class="nav-item"><a href="Discussion_Forums.php" class="nav-link"><i class="fas fa-comments"></i><span>Discussion Forums</span></a></div>
            <?php endif; ?>
            <?php if ($isSuperAdmin): ?>
                <div class="nav-item"><a href="Generate_Reports.php" class="nav-link" data-section="reports"><i class="fas fa-file-alt"></i><span>Reports & Analytics</span></a></div>
            <?php endif ?>
        </nav>
    </div>

    <div class="top-navbar">
        <h4>ADMIN PANEL</h4>
        <div class="navbar-actions">
    <div class="dropdown profile-dropdown">
        <div
            class="profile-trigger"
            id="adminProfileDropdown"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            role="button"
        >
            <?php if ($profile_image && file_exists('../' . $profile_image)): ?>
                <img
                    src="../<?php echo htmlspecialchars($profile_image); ?>?t=<?php echo time(); ?>"
                    alt="Profile"
                    class="profile-img"
                >
            <?php else: ?>
                <div class="profile-initials">
                    <?php echo htmlspecialchars($initials); ?>
                </div>
            <?php endif; ?>
        </div>

        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminProfileDropdown">
            <li class="dropdown-header px-3 py-2">
                <strong><?php echo htmlspecialchars($full_name); ?></strong><br>
                <small class="text-muted">
                    <?php echo $isSuperAdmin ? 'Super Admin' : 'Admin'; ?>
                </small>
            </li>

            <li><hr class="dropdown-divider"></li>

            <li>
                <a class="dropdown-item" href="Profile_Settings.php">
                    <i class="fas fa-user-cog me-2"></i> Profile Settings
                </a>
            </li>

            <?php if ($isSuperAdmin): ?>
                <li>
                    <a class="dropdown-item" href="Settings.php">
                        <i class="fas fa-cog me-2"></i> System Settings
                    </a>
                </li>
            <?php endif; ?>

            <!--<li>-->
            <!--    <a class="dropdown-item" href="index.php">-->
            <!--        <i class="fas fa-home me-2"></i> Dashboard-->
            <!--    </a>-->
            <!--</li>-->

            <li><hr class="dropdown-divider"></li>

            <li>
                <a class="dropdown-item text-danger" href="../partial/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>
    </div>

    <div class="main-content">
        <div class="exam-builder-page">
            <div class="builder-page-header">
                <div>
                    <div class="builder-page-badge"><i class="fas fa-clipboard-list"></i> Practice Exams</div>
                    <h1 class="builder-page-title">Create Practice Exam</h1>
                </div>
                <div class="builder-page-actions">
                    <a href="Practice_Exams.php" class="builder-page-link"><i class="fas fa-arrow-left me-2"></i>Back to Exams</a>
                </div>
            </div>

            <section class="exam-builder-page-shell" id="examBuilderPage">
                <div class="builder-page-card">
<div class="progress-step d-flex justify-content-between mb-4">
                        <div class="step active" onclick="showStep(1)">Basic Information</div>
                        <div class="step" onclick="showStep(2)">Add Questions</div>
                        <div class="step" onclick="showStep(3)">Review &amp; Create</div>
                    </div>

                    <div class="builder-shell">
                        <aside class="builder-sidebar">
                            <div class="builder-section-title">Questions</div>
                            <div class="builder-question-list" id="builderQuestionList"></div>
                            <button class="builder-side-btn" type="button" onclick="addQuestion()"><i class="fas fa-plus me-2"></i>Add Question</button>
                            <div class="builder-stat-card">
                                <div class="builder-stat-row"><span>Current Questions</span><strong id="builderQuestionCount">0</strong></div>
                                <div class="builder-stat-row"><span>Declared Total</span><strong id="builderDeclaredCount">0</strong></div>
                                <div class="builder-stat-row"><span>Mode</span><strong id="builderModeText">Create</strong></div>
                            </div>
                        </aside>

                        <main class="builder-main">
                            <div class="builder-main-top">
                                <div>
                                    <div class="builder-badge"><i class="fas fa-pen"></i> Exam Builder</div>
                                    <div class="builder-heading" id="builderMainHeading">Create Exam</div>
                                    <div class="builder-subheading" id="builderMainSubheading">Fill in your exam details, add questions, and review before saving.</div>
                                </div>
                                <div class="builder-top-actions">
                                    <div class="builder-progress-pill"><span id="builderProgressQuestions">0</span> question(s) prepared</div>
                                    <button type="button" class="builder-outline-btn" onclick="showStep(3)">Preview Review</button>
                                    <button type="button" class="builder-primary-btn" id="builderSaveTopBtn" onclick="createExam()">Save Exam</button>
                                </div>
                            </div>

                            <div id="step1" class="builder-step-content active">
                                <div class="builder-panel-card">
                                    <div class="builder-panel-heading">
                                        <h5>Basic Information</h5>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Exam Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="examTitle" placeholder="e.g. Organic Chemistry - Quiz (Module A)">
                                            <div class="error-message" id="titleError"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Topic <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="examTopic" placeholder="Enter exam topic">
                                            <div class="error-message" id="topicError"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Category <span class="text-danger">*</span></label>
                                            <select class="form-select" id="examCategory">
                                                <option value="">Select category</option>
                                                <option value="Analytical Chemistry">Analytical Chemistry</option>
                                                <option value="Organic Chemistry">Organic Chemistry</option>
                                                <option value="Physical Chemistry">Physical Chemistry</option>
                                                <option value="Inorganic Chemistry">Inorganic Chemistry</option>
                                                <option value="BioChemistry">BioChemistry</option>
                                            </select>
                                            <div class="error-message" id="categoryError"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Difficulty <span class="text-danger">*</span></label>
                                            <select class="form-select" id="examDifficulty">
                                                <option value="">Select difficulty</option>
                                                <option value="Beginner">Beginner</option>
                                                <option value="Intermediate">Intermediate</option>
                                                <option value="Advanced">Advanced</option>
                                            </select>
                                            <div class="error-message" id="difficultyError"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="builder-panel-card">
                                    <div class="builder-panel-heading">
                                        <h6>Settings</h6>
                                    </div>
                                    <div class="builder-settings-grid">
                                        <div>
                                            <label class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="examDuration" min="1" placeholder="30">
                                            <div class="error-message" id="durationError"></div>
                                        </div>
                                        <div>
                                            <label class="form-label">Displayed Total Questions <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="totalItems" min="1" placeholder="10">
                                            <small class="builder-muted d-block mt-1">Informational only. You can add more questions.</small>
                                            <div class="error-message" id="itemsError"></div>
                                        </div>
                                        <div>
                                            <label class="form-label">Passing Score (%) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="passingScore" min="0" max="100" placeholder="70">
                                            <div class="error-message" id="passingError"></div>
                                        </div>
                                        <div>
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" id="examDescription" rows="4" placeholder="Enter exam description..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="builder-footer-nav">
                                    <div></div>
                                    <div class="builder-footer-center">
                                        <button class="builder-footer-btn primary" onclick="nextStep(2)"><i class="fas fa-arrow-right me-2"></i>Go to Questions</button>
                                    </div>
                                </div>
                            </div>

                            <div id="step2" class="builder-step-content">
                                <div class="builder-panel-card">
                                    <div class="builder-panel-heading">
                                        <h5>Add Questions</h5>
                                    </div>
                                    <div class="alert alert-info border-0" >
                                        <i class="fas fa-info-circle me-2"></i>
                                        Add as many questions as needed for this exam.
                                    </div>
                                    <div id="questionsContainer"></div>
                                    <div class="text-center mt-3">
                                        <button class="btn btn-success btn-lg add-question" onclick="addQuestion()"><i class="fas fa-plus"></i> Add Question</button>
                                    </div>
                                </div>

                                <div class="builder-footer-nav">
                                    <button class="builder-footer-btn" onclick="prevStep(1)"><i class="fas fa-arrow-left me-2"></i>Back</button>
                                    <div class="builder-footer-center">
                                        <!--<button class="builder-footer-btn" onclick="renderQuestionNavigator()"><i class="fas fa-rotate me-2"></i>Refresh List</button>-->
                                        <button class="builder-footer-btn primary" onclick="nextStep(3)">Review Exam <i class="fas fa-arrow-right ms-2"></i></button>
                                    </div>
                                </div>
                            </div>

                            <div id="step3" class="builder-step-content">
                                <div class="builder-panel-card">
                                    <div class="builder-panel-heading">
                                        <h5>Review Exam</h5>
                                        <span class="builder-muted">Final check before create or update.</span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <p><strong>Title:</strong> <span id="reviewTitle"></span></p>
                                            <p><strong>Topic:</strong> <span id="reviewTopic"></span></p>
                                            <p><strong>Category:</strong> <span class="badge bg-primary" id="reviewCategory"></span></p>
                                            <p><strong>Difficulty:</strong> <span class="badge bg-warning text-dark" id="reviewDifficulty"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Duration:</strong> <span id="reviewDuration"></span> minutes</p>
                                            <p><strong>Total Questions:</strong> <span id="reviewTotalItems"></span></p>
                                            <p><strong>Passing Score:</strong> <span id="reviewPassingScore"></span>%</p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <strong>Description:</strong>
                                        <p class="mt-2 mb-0" id="reviewDescription"></p>
                                    </div>
                                </div>
                                <div class="builder-panel-card">
                                    <div class="builder-panel-heading"><h6>Questions Preview</h6><span class="builder-muted">Actual list of added questions.</span></div>
                                    <div id="reviewQuestions" class="builder-summary-list"></div>
                                </div>
                                <div class="builder-footer-nav">
                                    <button class="builder-footer-btn" onclick="prevStep(2)"><i class="fas fa-arrow-left me-2"></i>Back</button>
                                    <div class="builder-footer-center">
                                        <button class="builder-footer-btn primary" id="createExamBtn" onclick="createExam()"><i class="fas fa-check me-2"></i>Create Exam</button>
                                    </div>
                                </div>
                            </div>
                        </main>

                        <aside class="builder-rightbar">
                            <div class="builder-right-group">
                                <div class="builder-section-title">Builder Summary</div>
                                <div class="builder-right-card">
                                    <div class="builder-right-list">
                                        <div class="builder-right-line"><span>Title</span><strong id="summaryTitle">Untitled</strong></div>
                                        <div class="builder-right-line"><span>Topic</span><strong id="summaryTopic">—</strong></div>
                                        <div class="builder-right-line"><span>Category</span><strong id="summaryCategory">—</strong></div>
                                        <div class="builder-right-line"><span>Difficulty</span><strong id="summaryDifficulty">—</strong></div>
                                    </div>
                                </div>
                            </div>
                            <div class="builder-right-group">
                                <div class="builder-section-title">Question Types</div>
                                <div id="builderQuestionTypeChips"></div>
                            </div>
                            <div class="builder-right-group">
                                <div class="builder-section-title">Quick Info</div>
                            
                                <div class="builder-right-tip quick-info-card">
                                    <div class="builder-tip-header">
                                        <i class="fas fa-lightbulb"></i>
                                        <strong>Builder Tips</strong>
                                    </div>
                            
                                    <p class="builder-tip-text">
                                        Use the left sidebar to quickly jump between questions while building your exam.
                                    </p>
                            
                                    <div class="builder-tip-item">
                                        <span class="builder-tip-label">Keep Questions Clear</span>
                                        <small>Write short, direct, and easy-to-understand questions.</small>
                                    </div>
                            
                                    <div class="builder-tip-item">
                                        <span class="builder-tip-label">Review Correct Answers</span>
                                        <small>Double-check that the correct answer is selected before saving.</small>
                                    </div>
                            
                                    <div class="builder-tip-item">
                                        <span class="builder-tip-label">Use Preview Step</span>
                                        <small>Go to the review section before creating the exam to catch missing questions or incorrect choices.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="builder-right-group">
                                <div class="builder-right-tip">
                                    <div class="builder-tip-header">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <strong>Exam Naming Convention</strong>
                                    </div>
                            
                                    <p class="builder-tip-text">
                                        Please follow the required naming format to keep exams properly categorized and to avoid progression or reporting issues.
                                    </p>
                            
                                    <div class="builder-tip-item">
                                        <span class="builder-tip-label">POST-TEST</span>
                                        <small>[Branch/Category] - POST-TEST (Module [Letter])</small>
                                        <small class="builder-tip-example">Example: Organic Chemistry - POST-TEST (Module A)</small>
                                    </div>
                            
                                    <div class="builder-tip-item">
                                        <span class="builder-tip-label">Practice Test</span>
                                        <small>[Branch/Category] - Practice Test [Number]</small>
                                        <small class="builder-tip-example">Example: Organic Chemistry - Practice Test 1</small>
                                    </div>
                            
                                    <div class="builder-tip-item">
                                        <span class="builder-tip-label">Mock Exam</span>
                                        <small>[Branch/Category] - FULL MOCK EXAM</small>
                                        <small class="builder-tip-example">Example: Organic Chemistry - FULL MOCK EXAM</small>
                                    </div>
                            
                                    <div class="builder-tip-warning">
                                        <i class="fas fa-exclamation-circle"> </i>
                                        Incorrect naming may cause categorization issues, progression errors, and inconsistent dashboard data.
                                    </div>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        
                </div>
            </section>
        </div>
    </div>

    <div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 1100;"><div id="toastContainer"></div></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.collapse-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.top-navbar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('collapsed');
            this.querySelector('i').classList.toggle('fa-chevron-left');
            this.querySelector('i').classList.toggle('fa-chevron-right');
        });

        let currentStep = 1;
        let questions = [];
        let currentExamId = 0;
        let isEditMode = false;

        function showError(id, message) {
            const errorEl = document.getElementById(id);
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.style.display = message ? 'block' : 'none';
            }
        }

        function clearErrors() {
            ['titleError', 'topicError', 'categoryError', 'difficultyError', 'durationError', 'itemsError', 'passingError'].forEach(id => showError(id, ''));
        }

        function validateStep1() {
            clearErrors();
            let isValid = true;
            const title = document.getElementById('examTitle').value.trim();
            const topic = document.getElementById('examTopic').value.trim();
            const category = document.getElementById('examCategory').value;
            const difficulty = document.getElementById('examDifficulty').value;
            const duration = parseInt(document.getElementById('examDuration').value);
            const items = parseInt(document.getElementById('totalItems').value);
            const passing = parseInt(document.getElementById('passingScore').value);

            if (!title) { showError('titleError', 'Exam title is required'); isValid = false; }
            if (!topic) { showError('topicError', 'Topic is required'); isValid = false; }
            if (!category) { showError('categoryError', 'Category is required'); isValid = false; }
            if (!difficulty) { showError('difficultyError', 'Difficulty is required'); isValid = false; }
            if (!duration || duration < 1) { showError('durationError', 'Duration must be at least 1 minute'); isValid = false; }
            if (!items || items < 1) { showError('itemsError', 'Displayed total questions must be at least 1'); isValid = false; }
            if (Number.isNaN(passing) || passing < 0 || passing > 100) { showError('passingError', 'Passing score must be between 0-100'); isValid = false; }

            updateBuilderMeta();
            return isValid;
        }

        function toggleSidebar() {
            document.querySelectorAll('.sidebar, .top-navbar, .main-content').forEach(el => el.classList.toggle('collapsed'));
            const i = document.querySelector('.collapse-btn i');
            i.classList.toggle('fa-chevron-left');
            i.classList.toggle('fa-chevron-right');
        }

        function loadExams() {
            fetch('../partial/exam_list.php')
                .then(r => r.json())
                .then(({ data }) => {
                    data = Array.isArray(data) ? data : [];
                    const totalExams = data.length;
                    let totalAttempts = 0;
                    let weightedScoreSum = 0;
                    for (const e of data) {
                        const completions = Number(e.completions) || 0;
                        const avgScore = Number(e.avg_score) || 0;
                        totalAttempts += completions;
                        weightedScoreSum += avgScore * completions;
                    }
                    const avgScore = totalAttempts ? Math.round(weightedScoreSum / totalAttempts) : 0;
                    document.querySelector('.total-exams .stat-number').textContent = totalExams;
                    document.querySelector('.total-attempts .stat-number').textContent = totalAttempts > 999 ? (totalAttempts / 1000).toFixed(1) + 'k' : totalAttempts;
                    document.querySelector('.average-score .stat-number').textContent = avgScore + '%';
                    document.querySelector('.total-exams .stat-subtitle').textContent = 'Across 5 categories';
                    document.querySelector('.total-attempts .stat-subtitle').textContent = '';
                    document.querySelector('.average-score .stat-subtitle').textContent = `Based on ${totalAttempts} attempts`;
                    animateStats();

                    const tbody = document.getElementById('examsTbody');
                    tbody.innerHTML = '';
                    if (!data.length) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No exams found. Create your first exam!</td></tr>';
                        return;
                    }
                    const fragment = document.createDocumentFragment();
                    for (const e of data) {
                        const categoryClass = (e.category || 'general').toLowerCase().replace(/\s+/g, '-');
                        const difficulty = (e.difficulty || 'beginner').toLowerCase();
                        const difficultyBadgeClass = difficulty === 'beginner' ? 'bg-success' : difficulty === 'intermediate' ? 'bg-warning' : 'bg-danger';
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><div class="exam-title"><i class="fas fa-clipboard-list me-2"></i>${e.title}</div></td>
                            <td><div class="exam-topic">${e.topic || 'General'}</div></td>
                            <td><span class="category-badge ${categoryClass}">${e.category}</span></td>
                            <td><span class="badge ${difficultyBadgeClass}">${e.difficulty}</span></td>
                            <td><div class="attempts-count"><i class="fas fa-question-circle me-1"></i>${e.actual_questions}</div></td>
                            <td><div class="attempts-count"><i class="fas fa-users me-1"></i>${e.completions}</div></td>
                            <td><div class="score-progress"><div class="progress-bar-container"><div class="progress-bar progress-${e.avg_score}"></div></div><span class="score-text">${e.avg_score}%</span></div></td>
                            <td>
                                <div class="actions-cell">
                                    <button class="action-btn view" title="View Details" onclick="viewExam(${e.id})"><i class="fas fa-eye"></i></button>
                                    <button class="action-btn edit" title="Edit Exam" onclick="editExam(${e.id})"><i class="fas fa-edit"></i></button>
                                    <button class="action-btn delete" title="Delete Exam" onclick="deleteExam(${e.id})"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>`;
                        fragment.appendChild(tr);
                    }
                    tbody.appendChild(fragment);
                    animateProgressBars();
                    attachRowHover();
                })
                .catch(err => {
                    console.error('Error loading exams:', err);
                    document.getElementById('examsTbody').innerHTML = '<tr><td colspan="8" class="text-center py-4 text-danger">Error loading exams. Please try again.</td></tr>';
                });
        }

        function animateStats() {
            document.querySelectorAll('.stat-number').forEach(el => {
                const text = el.textContent.trim();
                let target = 0;
                let suffix = '';
                if (text.includes('%')) { target = parseInt(text.replace('%', '')); suffix = '%'; }
                else if (text.includes('k')) { target = parseFloat(text.replace('k', '')) * 1000; suffix = 'k'; }
                else { target = parseInt(text.replace(/[^\d]/g, '')) || 0; }
                let current = 0;
                const increment = target / 50 || 1;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) { current = target; clearInterval(timer); }
                    el.textContent = suffix === '%' ? Math.floor(current) + '%' : suffix === 'k' ? (current / 1000).toFixed(1) + 'k' : Math.floor(current);
                }, 30);
            });
        }

        function animateProgressBars() {
            document.querySelectorAll('.progress-bar').forEach(bar => {
                const match = bar.className.match(/progress-(\d+)/);
                if (match) {
                    const width = match[1] + '%';
                    bar.style.width = '0%';
                    setTimeout(() => { bar.style.width = width; }, 500);
                }
            });
        }

        function attachRowHover() {
            document.querySelectorAll('.exams-table tbody tr').forEach(r => {
                r.addEventListener('mouseenter', () => { r.style.transform = 'translateX(2px)'; r.style.boxShadow = '2px 0 8px rgba(0,0,0,0.1)'; });
                r.addEventListener('mouseleave', () => { r.style.transform = ''; r.style.boxShadow = ''; });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', e => {
                    const term = e.target.value.toLowerCase();
                    document.querySelectorAll('.exams-table tbody tr').forEach(tr => {
                        tr.style.display = tr.textContent.toLowerCase().includes(term) ? '' : 'none';
                    });
                });
            }
        });

        function setActiveStepUI(step) {
            document.querySelectorAll('.builder-step-content').forEach(content => content.classList.remove('active'));
            const stepEl = document.getElementById('step' + step);
            if (stepEl) stepEl.classList.add('active');
            document.querySelectorAll('.step').forEach((st, i) => {
                st.classList.toggle('completed', i < step - 1);
                st.classList.toggle('active', i === step - 1);
            });
        }

        function showStep(s) {
            if (s === 2 && currentStep === 1 && !validateStep1()) return;
            currentStep = s;
            setActiveStepUI(s);
            if (s === 3) populateReview();
            updateBuilderMeta();
        }

        function nextStep(s) {
            if (s === 2 && currentStep === 1 && !validateStep1()) return;
            if (s === 3 && currentStep === 2 && questions.length < 1) {
                alert('Please add at least 1 question');
                return;
            }
            showStep(s);
        }

        function prevStep(s) { showStep(s); }

        function addQuestion() {
            const idx = questions.length;
            const div = document.createElement('div');
            div.className = 'question-block mb-4';
            div.id = `question-block-${idx}`;
            div.dataset.qindex = idx;
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="fas fa-question-circle text-primary me-2"></i>Question ${idx + 1}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuestion(${idx})"><i class="fas fa-trash"></i> Remove</button>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Question Text <span class="text-danger">*</span></label>
                    <textarea class="form-control question-text" rows="2" placeholder="Enter the question text here..." required></textarea>
                    <div class="error-message" id="question-error-${idx}"></div>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Question Type <span class="text-danger">*</span></label>
                    <select class="form-select question-type" onchange="typeChanged(this, ${idx})">
                        <option value="multiple">Multiple Choice</option>
                        <option value="truefalse">True/False</option>
                    </select>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="media-upload" onclick="document.getElementById('imageUpload-${idx}').click()">
                            <i class="fas fa-image fa-2x text-muted mb-2"></i>
                            <p class="mb-1"><strong>Add Image</strong></p>
                            <small class="text-muted">JPG, PNG, GIF (Max 5MB)</small>
                            <input type="file" id="imageUpload-${idx}" accept="image/*" style="display: none;" onchange="handleImageUpload(event, ${idx})">
                            <div id="image-preview-${idx}"></div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Answer Choices <span class="text-danger">*</span></label>
                    <div class="choices-container" id="choices-${idx}"></div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addChoice(${idx})"><i class="fas fa-plus"></i> Add Choice</button>
                </div>
                <hr class="my-3">`;
            document.getElementById('questionsContainer').appendChild(div);
            questions.push({ id: null, text: '', type: 'multiple', image_path: '', choices: [] });
            addChoice(idx, 4);
            attachQuestionListeners(div, idx);
            renderQuestionNavigator();
            updateBuilderMeta();
        }

        function removeQuestion(idx) {
            if (confirm('Are you sure you want to remove this question?')) {
                const block = document.getElementById(`question-block-${idx}`);
                if (block) block.remove();
                questions.splice(idx, 1);
                rebuildAllQuestionBlocks();
                renderQuestionNavigator();
                updateBuilderMeta();
            }
        }

        function rebuildAllQuestionBlocks() {
            const container = document.getElementById('questionsContainer');
            const blocks = Array.from(container.querySelectorAll('.question-block'));
            blocks.forEach((block, index) => {
                block.id = `question-block-${index}`;
                block.dataset.qindex = index;
                const title = block.querySelector('h6');
                if (title) title.innerHTML = `<i class="fas fa-question-circle text-primary me-2"></i>Question ${index + 1}`;
                const removeBtn = block.querySelector('.btn-outline-danger');
                if (removeBtn) removeBtn.setAttribute('onclick', `removeQuestion(${index})`);
                const error = block.querySelector('.error-message');
                if (error) error.id = `question-error-${index}`;
                const typeSelect = block.querySelector('.question-type');
                if (typeSelect) typeSelect.setAttribute('onchange', `typeChanged(this, ${index})`);
                const uploadArea = block.querySelector('.media-upload');
                const uploadInput = block.querySelector('input[type="file"]');
                const preview = block.querySelector('[id^="image-preview-"]');
                const choicesContainer = block.querySelector('.choices-container');
                const addChoiceBtn = block.querySelector('.btn-outline-primary');
                if (uploadInput) { uploadInput.id = `imageUpload-${index}`; uploadInput.setAttribute('onchange', `handleImageUpload(event, ${index})`); }
                if (uploadArea) uploadArea.setAttribute('onclick', `document.getElementById('imageUpload-${index}').click()`);
                if (preview) preview.id = `image-preview-${index}`;
                if (choicesContainer) choicesContainer.id = `choices-${index}`;
                if (addChoiceBtn) addChoiceBtn.setAttribute('onclick', `addChoice(${index})`);

                const rows = Array.from(block.querySelectorAll('.choice-row'));
                rows.forEach((row, cidx) => {
                    const input = row.querySelector('.choice-input');
                    const check = row.querySelector('.correct-choice');
                    const label = row.querySelector('.form-check-label');
                    const del = row.querySelector('.remove-choice');
                    if (input) input.placeholder = `Enter choice ${String.fromCharCode(65 + cidx)}...`;
                    if (check) check.id = `correct-${index}-${cidx}`;
                    if (label) label.setAttribute('for', `correct-${index}-${cidx}`);
                    if (del) del.setAttribute('onclick', `removeChoice(this, ${index}, ${cidx})`);
                });
            });
        }

        function addChoice(qidx, count = 1) {
            const container = document.getElementById(`choices-${qidx}`);
            if (!container) return;
            for (let i = 0; i < count; i++) {
                const cidx = questions[qidx].choices.length;
                const row = document.createElement('div');
                row.className = 'choice-row';
                row.innerHTML = `
                    <input type="text" class="form-control choice-input" placeholder="Enter choice ${String.fromCharCode(65 + cidx)}..." required>
                    <div class="form-check form-switch">
                        <input class="form-check-input correct-choice" type="checkbox" id="correct-${qidx}-${cidx}">
                        <label class="form-check-label" for="correct-${qidx}-${cidx}">Correct</label>
                    </div>
                    ${cidx > 1 ? `<span class="remove-choice" onclick="removeChoice(this, ${qidx}, ${cidx})" title="Remove choice"><i class="fas fa-trash"></i></span>` : ''}`;
                container.appendChild(row);
                questions[qidx].choices.push({ text: '', correct: false });
                const choiceInput = row.querySelector('.choice-input');
                const correctCheck = row.querySelector('.correct-choice');
                choiceInput.addEventListener('input', function() {
                    const choiceIndex = Array.from(container.querySelectorAll('.choice-input')).indexOf(this);
                    questions[qidx].choices[choiceIndex].text = this.value;
                    renderQuestionNavigator();
                });
                correctCheck.addEventListener('change', function() {
                    const choiceIndex = Array.from(container.querySelectorAll('.correct-choice')).indexOf(this);
                    questions[qidx].choices[choiceIndex].correct = this.checked;
                    if (questions[qidx].type === 'truefalse') {
                        container.querySelectorAll('.correct-choice').forEach((cb, ci) => {
                            if (ci !== choiceIndex) {
                                cb.checked = false;
                                questions[qidx].choices[ci].correct = false;
                            }
                        });
                    }
                });
            }
        }

        function removeChoice(element, qidx, cidx) {
            if (questions[qidx].choices.length <= 2) { alert('At least 2 choices are required'); return; }
            element.closest('.choice-row').remove();
            questions[qidx].choices.splice(cidx, 1);
            rebuildChoiceUI(qidx);
        }

        function rebuildChoiceUI(qidx) {
            const container = document.getElementById(`choices-${qidx}`);
            if (!container) return;
            const rows = Array.from(container.querySelectorAll('.choice-row'));
            const snapshot = questions[qidx].choices.map(choice => ({ ...choice }));
            container.innerHTML = '';
            questions[qidx].choices = [];
            snapshot.forEach((choice, cidx) => {
                const row = document.createElement('div');
                row.className = 'choice-row';
                row.innerHTML = `
                    <input type="text" class="form-control choice-input" value="${(choice.text || '').replace(/"/g, '&quot;')}" placeholder="Enter choice ${String.fromCharCode(65 + cidx)}..." required>
                    <div class="form-check form-switch">
                        <input class="form-check-input correct-choice" type="checkbox" id="correct-${qidx}-${cidx}" ${choice.correct ? 'checked' : ''}>
                        <label class="form-check-label" for="correct-${qidx}-${cidx}">Correct</label>
                    </div>
                    ${cidx > 1 ? `<span class="remove-choice" onclick="removeChoice(this, ${qidx}, ${cidx})" title="Remove choice"><i class="fas fa-trash"></i></span>` : ''}`;
                container.appendChild(row);
                questions[qidx].choices.push({ text: choice.text || '', correct: !!choice.correct });
                row.querySelector('.choice-input').addEventListener('input', function() {
                    questions[qidx].choices[cidx].text = this.value;
                    renderQuestionNavigator();
                });
                row.querySelector('.correct-choice').addEventListener('change', function() {
                    questions[qidx].choices[cidx].correct = this.checked;
                });
            });
        }

        function setupTrueFalseChoices(qidx) {
            const container = document.getElementById(`choices-${qidx}`);
            if (!container) return;
            container.innerHTML = '';
            questions[qidx].choices = [];
            addChoice(qidx, 2);
            const inputs = container.querySelectorAll('.choice-input');
            const checks = container.querySelectorAll('.correct-choice');
            if (inputs[0]) inputs[0].value = 'True';
            if (inputs[1]) inputs[1].value = 'False';
            if (checks[0]) checks[0].checked = true;
            if (checks[1]) checks[1].checked = false;
            questions[qidx].choices[0] = { text: 'True', correct: true };
            questions[qidx].choices[1] = { text: 'False', correct: false };
            inputs.forEach((input, i) => input.setAttribute('readonly', true));
        }

        function typeChanged(select, qidx) {
            const newType = select.value;
            questions[qidx].type = newType;
            const container = document.getElementById(`choices-${qidx}`);
            if (!container) return;
            container.innerHTML = '';
            questions[qidx].choices = [];
            if (newType === 'multiple') addChoice(qidx, 4);
            else setupTrueFalseChoices(qidx);
            renderQuestionNavigator();
            updateBuilderMeta();
        }

        function attachQuestionListeners(block, qidx) {
            const textArea = block.querySelector('.question-text');
            const typeSelect = block.querySelector('.question-type');
            if (textArea) {
                textArea.addEventListener('input', function() {
                    questions[qidx].text = this.value;
                    validateQuestionText(qidx);
                    renderQuestionNavigator();
                });
                textArea.addEventListener('blur', () => validateQuestionText(qidx));
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', function() { typeChanged(this, qidx); });
            }
        }

        function validateQuestionText(qidx) {
            const text = questions[qidx]?.text?.trim() || '';
            const errorEl = document.getElementById(`question-error-${qidx}`);
            if (!errorEl) return;
            if (!text) {
                errorEl.textContent = 'Question text is required';
                errorEl.style.display = 'block';
            } else {
                errorEl.style.display = 'none';
            }
        }

        function handleImageUpload(event, qidx) {
            const file = event.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('file', file);
            const preview = document.getElementById(`image-preview-${qidx}`);
            preview.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
            fetch('../partial/upload_question_media.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        questions[qidx].image_path = data.path;
                        showImagePreview(preview, data.path, data.filename);
                        renderQuestionNavigator();
                    } else {
                        alert('Error uploading image: ' + data.msg);
                        preview.innerHTML = '';
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    alert('Upload failed');
                    preview.innerHTML = '';
                });
        }

        function showImagePreview(container, path, filename) {
            const idx = container.id.match(/image-preview-(\d+)/)[1];
            container.innerHTML = `
                <img src="../${path}" class="question-image-preview" alt="${filename}" onerror="this.style.display='none'">
                <div class="mt-2">
                    <small class="text-muted">${filename}</small>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeMedia(${idx}, 'image')"><i class="fas fa-trash"></i></button>
                </div>`;
        }

        function removeMedia(qidx, type) {
            if (type === 'image') {
                questions[qidx].image_path = '';
                document.getElementById(`image-preview-${qidx}`).innerHTML = '';
                document.getElementById(`imageUpload-${qidx}`).value = '';
                renderQuestionNavigator();
            }
        }

        function populateReview() {
            document.getElementById('reviewTitle').textContent = document.getElementById('examTitle').value;
            document.getElementById('reviewTopic').textContent = document.getElementById('examTopic').value;
            document.getElementById('reviewCategory').textContent = document.getElementById('examCategory').value;
            document.getElementById('reviewDuration').textContent = document.getElementById('examDuration').value;
            document.getElementById('reviewTotalItems').textContent = questions.length;
            document.getElementById('reviewDescription').textContent = document.getElementById('examDescription').value || 'No description provided';
            document.getElementById('reviewDifficulty').textContent = document.getElementById('examDifficulty').value;
            document.getElementById('reviewPassingScore').textContent = document.getElementById('passingScore').value;
            const reviewContainer = document.getElementById('reviewQuestions');
            reviewContainer.innerHTML = '';
            questions.forEach((q, qidx) => {
                const questionDiv = document.createElement('div');
                questionDiv.className = 'builder-summary-item';
                let mediaHtml = '';
                if (q.image_path) mediaHtml += `<img src="../${q.image_path}" class="question-image-preview mb-2" alt="Question image">`;
                questionDiv.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0">Question ${qidx + 1} (${q.type === 'multiple' ? 'Multiple Choice' : 'True/False'})</h6>
                        ${mediaHtml ? `<span class="badge bg-info">Has Media</span>` : ''}
                    </div>
                    <p class="mb-2"><strong>Q:</strong> ${q.text || '<em>(No text)</em>'}</p>
                    ${mediaHtml}
                    <div class="mt-3">
                        <strong>Choices:</strong>
                        ${q.choices.map((c, cidx) => {
                            const letter = String.fromCharCode(65 + cidx);
                            const status = c.correct ? 'correct' : 'incorrect';
                            const indicator = c.correct ? '<i class="fas fa-check text-success ms-2"></i>' : '';
                            return `<div class="choice-preview ${status} mt-1"><strong>${letter}.</strong> ${c.text || '<em>(No text)</em>'}${indicator}</div>`;
                        }).join('')}
                    </div>`;
                reviewContainer.appendChild(questionDiv);
            });
            updateBuilderMeta();
        }

        function createExam() {
            if (!validateStep1()) return;
            if (questions.length < 1) { alert('Please add at least 1 question'); showStep(2); return; }
            for (let i = 0; i < questions.length; i++) {
                if (!questions[i].text.trim()) { alert(`Question ${i + 1} needs text`); showStep(2); return; }
                for (let j = 0; j < questions[i].choices.length; j++) {
                    if (!questions[i].choices[j].text.trim()) { alert(`Choice ${String.fromCharCode(65 + j)} for question ${i + 1} needs text`); showStep(2); return; }
                }
                if (!questions[i].choices.some(c => c.correct)) { alert(`Question ${i + 1} needs at least one correct answer`); showStep(2); return; }
            }
            const payload = {
                exam_id: currentExamId,
                title: document.getElementById('examTitle').value,
                topic: document.getElementById('examTopic').value,
                description: document.getElementById('examDescription').value,
                category: document.getElementById('examCategory').value,
                difficulty: document.getElementById('examDifficulty').value,
                total_questions: parseInt(document.getElementById('totalItems').value),
                duration_minutes: parseInt(document.getElementById('examDuration').value),
                passing_score: parseInt(document.getElementById('passingScore').value),
                questions: questions.map(q => ({
                    text: q.text,
                    type: q.type,
                    image_path: q.image_path || null,
                    choices: q.choices.map(c => ({ text: c.text, correct: c.correct ? 1 : 0 }))
                }))
            };
            const url = isEditMode ? '../partial/exam_update.php' : '../partial/exam_create.php';
            const btn = document.getElementById('createExamBtn');
            const topBtn = document.getElementById('builderSaveTopBtn');
            const originalText = btn.innerHTML;
            const originalTop = topBtn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';
            topBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';
            btn.disabled = true; topBtn.disabled = true;
            fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
})
.then(async r => {
    const data = await r.json();
    if (!r.ok) {
        throw new Error(data.msg || 'Request failed');
    }
    return data;
})
.then(res => {
    console.log(res);

    if (res.success) {
        showToast(
            'Exam saved successfully!',
            'success'
        );

        resetForm();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        // 🔥 Custom meaningful message
        let message = res.msg || 'Something went wrong while saving the exam.';

        if (message.toLowerCase().includes('invalid')) {
            message = 'Some questions or data are incomplete. Please review your inputs.';
        }

        showToast(message, 'warning'); // 🔥 use warning instead of alert
        showStep(2);
    }
})
.catch(err => {
    console.error('Save error:', err);

    showToast(
        'Unable to save exam. Please check your inputs and try again.',
        'danger'
    );

    showStep(2);
})
// .then(res => {
//     console.log(res);

//     if (res.success) {
//         showToast(
//             'Exam saved successfully!',
//             'success'
//         );

//         resetForm();
//         window.scrollTo({ top: 0, behavior: 'smooth' });
//     } else {
//         alert('Error: ' + (res.msg || 'Failed to save exam'));
//         showStep(2);
//     }
// })
// .catch(err => {
//     console.error('Save error:', err);
//     alert(err.message || 'Error saving exam. Please try again.');
//     showStep(2);
// })
.finally(() => {
    btn.innerHTML = originalText;
    topBtn.innerHTML = originalTop;
    btn.disabled = false;
    topBtn.disabled = false;
});
        }

        function resetForm() {
            questions = [];
            currentExamId = 0;
            isEditMode = false;
            document.getElementById('examBuilderPage').querySelectorAll('input, textarea, select').forEach(el => {
                if (el.type === 'checkbox' || el.type === 'radio') el.checked = false;
                else el.value = '';
            });
            document.getElementById('questionsContainer').innerHTML = '';
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
            // document.getElementById('modalTitle').textContent = 'Create New Exam';
            document.getElementById('builderModeText').textContent = 'Create';
            document.getElementById('builderMainHeading').textContent = 'Create Exam';
            document.getElementById('builderMainSubheading').textContent = 'Fill in your exam details, add questions, and review before saving.';
            document.getElementById('builderSaveTopBtn').textContent = 'Save Exam';
            document.getElementById('createExamBtn').innerHTML = '<i class="fas fa-check me-2"></i>Create Exam';
            renderQuestionNavigator();
            updateBuilderMeta();
            showStep(1);
        }

        function renderQuestionNavigator() {
            const list = document.getElementById('builderQuestionList');
            if (!list) return;
            list.innerHTML = '';
            questions.forEach((q, idx) => {
                const card = document.createElement('div');
                card.className = 'question-nav-card';
                if (document.querySelector(`#question-block-${idx}`)) card.classList.add('active');
                const typeLabel = q.type === 'truefalse' ? 'True / False' : 'Multiple Choice';
                card.innerHTML = `
                    <div class="question-nav-index">${idx + 1}</div>
                    <div class="question-nav-meta">
                        <div class="question-nav-type">${typeLabel}</div>
                        <div class="question-nav-preview">${q.text?.trim() || 'Untitled question...'}</div>
                    </div>
                    <div class="question-nav-actions">
                        <button class="builder-mini-btn" type="button" onclick="scrollToQuestion(${idx})" title="Open"><i class="fas fa-arrow-right"></i></button>
                    </div>`;
                list.appendChild(card);
            });
            if (!questions.length) {
                list.innerHTML = '<div class="builder-muted">No questions yet. Start by adding one.</div>';
            }
        }

        function scrollToQuestion(idx) {
            showStep(2);
            const el = document.getElementById(`question-block-${idx}`);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function updateBuilderMeta() {
            document.getElementById('builderQuestionCount').textContent = questions.length;
            document.getElementById('builderProgressQuestions').textContent = questions.length;
            document.getElementById('builderDeclaredCount').textContent = document.getElementById('totalItems').value || 0;
            document.getElementById('summaryTitle').textContent = document.getElementById('examTitle').value || 'Untitled';
            document.getElementById('summaryTopic').textContent = document.getElementById('examTopic').value || '—';
            document.getElementById('summaryCategory').textContent = document.getElementById('examCategory').value || '—';
            document.getElementById('summaryDifficulty').textContent = document.getElementById('examDifficulty').value || '—';
            const chipWrap = document.getElementById('builderQuestionTypeChips');
            const counts = {
                multiple: questions.filter(q => q.type === 'multiple').length,
                truefalse: questions.filter(q => q.type === 'truefalse').length
            };
            chipWrap.innerHTML = `
                <span class="builder-right-chip"><i class="fas fa-list"></i> Multiple Choice: ${counts.multiple}</span>
                <span class="builder-right-chip"><i class="fas fa-check-circle"></i> True / False: ${counts.truefalse}</span>
                <span class="builder-right-chip"><i class="fas fa-file-alt"></i> Total Added: ${questions.length}</span>`;
        }

        function viewExam(id) {
            currentExamId = id;
                        fetch(`../partial/exam_get.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const exam = data.exam;
                        const avgScore = parseInt(exam.avg_score) || 0;
                        const completions = parseInt(exam.completions) || 0;
                        let html = `<div class="row"><div class="col-md-8"><h5><i class="fas fa-clipboard-list me-2"></i>${exam.title || 'Untitled'}</h5><p class="text-muted mb-1"><strong>Topic:</strong> ${exam.topic || 'General'}</p><p class="text-muted mb-1"><strong>Category:</strong> <span class="badge bg-primary">${exam.category || ''}</span></p><p class="text-muted mb-3"><strong>Difficulty:</strong> <span class="badge bg-warning text-dark">${exam.difficulty || ''}</span></p><div class="mb-3"><h6><i class="fas fa-align-left me-2"></i>Description</h6><p>${exam.description || 'No description provided'}</p></div><h6><i class="fas fa-list me-2"></i>Questions (${exam.questions?.length || 0})</h6>`;
                        if (exam.questions && Array.isArray(exam.questions)) {
                            exam.questions.forEach((q, qidx) => {
                                let mediaHtml = q.image_path ? `<img src="../${q.image_path}" class="img-fluid rounded mb-2" style="max-width: 300px;" alt="Question image" onerror="this.style.display='none'">` : '';
                                html += `<div class="card mb-3"><div class="card-body"><h6 class="card-title mb-2"><strong>Question ${qidx + 1}:</strong> <span class="badge bg-${q.type === 'multiple' ? 'info' : 'secondary'}">${q.type === 'multiple' ? 'Multiple Choice' : 'True/False'}</span></h6><p class="card-text mb-2">${q.text || '(No text)'}</p>${mediaHtml}<h6 class="mt-3 mb-2">Choices:</h6><ul class="list-unstyled">`;
                                if (q.choices && Array.isArray(q.choices)) {
                                    q.choices.forEach((c, cidx) => {
                                        const letter = String.fromCharCode(65 + cidx);
                                        const correctMark = c.correct ? ' <span class="text-success"><i class="fas fa-check-circle"></i> (Correct)</span>' : '';
                                        html += `<li class="mb-1"><strong>${letter}.</strong> ${c.text || '(No text)'}${correctMark}</li>`;
                                    });
                                }
                                html += `</ul></div></div>`;
                            });
                        }
                        html += `</div><div class="col-md-4"><div class="card"><div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Exam Info</h6></div><div class="card-body"><p class="mb-1"><strong>Duration:</strong> ${exam.duration_minutes || 0} minutes</p><p class="mb-1"><strong>Passing Score:</strong> ${exam.passing_score || 0}%</p><p class="mb-1"><strong>Total Questions:</strong> ${exam.total_questions || 0}</p><p class="mb-1"><strong>Created:</strong> ${exam.created_at ? new Date(exam.created_at).toLocaleDateString() : 'N/A'}</p><hr><h6 class="mb-2">Stats</h6><div class="progress mb-2" style="height: 20px;"><div class="progress-bar" style="width: ${avgScore}%"></div></div><small class="text-muted">Average Score: ${avgScore}%</small><p class="mb-0 mt-2"><small class="text-muted">Attempts: ${completions}</small></p></div></div></div></div>`;
                        document.getElementById('viewExamContent').innerHTML = html;
                    } else {
                        document.getElementById('viewExamContent').innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading exam details: ${data.msg || 'Unknown error'}</div>`;
                    }
                })
                .catch(err => {
                    console.error('View exam error:', err);
                    document.getElementById('viewExamContent').innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load exam details</div>`;
                });
        }

        function editExam(id) {
            currentExamId = id;
            isEditMode = true;
            // document.getElementById('modalTitle').textContent = 'Edit Exam';
            document.getElementById('builderModeText').textContent = 'Edit';
            document.getElementById('builderMainHeading').textContent = 'Edit Exam';
            document.getElementById('builderMainSubheading').textContent = 'Update exam details and question content before saving.';
            document.getElementById('createExamBtn').innerHTML = '<i class="fas fa-check me-2"></i>Update Exam';
            fetch(`../partial/exam_get.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { alert('Error loading exam: ' + (data.msg || 'Unknown')); return; }
                    const exam = data.exam;
                    document.getElementById('examTitle').value = exam.title || '';
                    document.getElementById('examTopic').value = exam.topic || '';
                    document.getElementById('examCategory').value = exam.category || '';
                    document.getElementById('examDifficulty').value = exam.difficulty || '';
                    document.getElementById('examDuration').value = exam.duration_minutes || '';
                    document.getElementById('totalItems').value = exam.total_questions || '';
                    document.getElementById('passingScore').value = exam.passing_score || '';
                    document.getElementById('examDescription').value = exam.description || '';
                    questions = [];
                    document.getElementById('questionsContainer').innerHTML = '';
                    if (!exam.questions || exam.questions.length === 0) {
                        alert('This exam has no questions.');
                    } else {
                        exam.questions.forEach((q, qidx) => {
                            addQuestion();
                            const block = document.getElementById(`question-block-${qidx}`);
                            if (!block) return;
                            const textArea = block.querySelector('.question-text');
                            textArea.value = q.text || '';
                            questions[qidx].text = q.text || '';
                            const typeSelect = block.querySelector('.question-type');
                            typeSelect.value = q.type || 'multiple';
                            questions[qidx].type = q.type || 'multiple';
                            typeChanged(typeSelect, qidx);
                            setTimeout(() => {
                                const container = document.getElementById(`choices-${qidx}`);
                                if (!container) return;
                                container.innerHTML = '';
                                questions[qidx].choices = [];
                                (q.choices || []).forEach((c, cidx) => {
                                    const row = document.createElement('div');
                                    row.className = 'choice-row';
                                    const choiceText = (c.text || '').replace(/"/g, '&quot;');
                                    row.innerHTML = `
                                        <input type="text" class="form-control choice-input" value="${choiceText}" required>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input correct-choice" type="checkbox" id="correct-${qidx}-${cidx}" ${c.correct ? 'checked' : ''}>
                                            <label class="form-check-label" for="correct-${qidx}-${cidx}">Correct</label>
                                        </div>
                                        ${cidx > 1 ? `<span class="remove-choice" onclick="removeChoice(this, ${qidx}, ${cidx})"><i class="fas fa-trash"></i></span>` : ''}`;
                                    container.appendChild(row);
                                    questions[qidx].choices.push({ text: c.text || '', correct: !!c.correct });
                                    row.querySelector('.choice-input').addEventListener('input', function() { questions[qidx].choices[cidx].text = this.value; renderQuestionNavigator(); });
                                    row.querySelector('.correct-choice').addEventListener('change', function() {
                                        questions[qidx].choices[cidx].correct = this.checked;
                                        if (questions[qidx].type === 'truefalse') {
                                            container.querySelectorAll('.correct-choice').forEach((cb, ci) => {
                                                if (ci !== cidx) { cb.checked = false; questions[qidx].choices[ci].correct = false; }
                                            });
                                        }
                                    });
                                });
                                if (q.image_path) {
                                    questions[qidx].image_path = q.image_path;
                                    const preview = document.getElementById(`image-preview-${qidx}`);
                                    if (preview) showImagePreview(preview, q.image_path, q.image_path.split('/').pop());
                                }
                                renderQuestionNavigator();
                                updateBuilderMeta();
                            }, 50);
                        });
                    }
                                        showStep(1);
                    updateBuilderMeta();
                })
                .catch(err => { console.error(err); alert('Failed to load exam'); });
        }

        function deleteExam(id) {
            if (confirm('Are you sure you want to delete this exam? This action cannot be undone.')) {
                fetch(`../partial/exam_delete.php?id=${id}`)
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) { loadExams(); showToast('Exam deleted successfully!', 'success'); }
                        else alert('Error deleting exam: ' + res.msg);
                    })
                    .catch(err => { console.error('Delete error:', err); alert('Failed to delete exam'); });
            }
        }

        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) return;
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'danger' ? 'danger' : 'primary'} border-0 mb-2`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: 5000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }

        document.addEventListener('DOMContentLoaded', function() {
            ['examTitle', 'examTopic', 'examCategory', 'examDifficulty', 'totalItems'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', updateBuilderMeta);
                if (el && el.tagName === 'SELECT') el.addEventListener('change', updateBuilderMeta);
            });
            const totalItemsInput = document.getElementById('totalItems');
            if (totalItemsInput) totalItemsInput.addEventListener('input', updateBuilderMeta);
            const collapseBtn = document.querySelector('.collapse-btn');
            if (collapseBtn) collapseBtn.addEventListener('click', toggleSidebar);
                renderQuestionNavigator();
            updateBuilderMeta();
        });


        setInterval(() => {
            if (currentStep > 1 && questions.length > 0) {
                localStorage.setItem('exam_draft', JSON.stringify({
                    questions: questions,
                    basic: {
                        title: document.getElementById('examTitle')?.value,
                        topic: document.getElementById('examTopic')?.value,
                        category: document.getElementById('examCategory')?.value,
                        difficulty: document.getElementById('examDifficulty')?.value,
                        duration: document.getElementById('examDuration')?.value,
                        total: document.getElementById('totalItems')?.value,
                        passing: document.getElementById('passingScore')?.value,
                        description: document.getElementById('examDescription')?.value
                    }
                }));
            }
        }, 30000);
    </script>
</body>
</html>
