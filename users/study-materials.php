<?php
require_once '../partial/db_conn.php';
$cats = ['Analytical Chemistry', 'Organic Chemistry', 'Physical Chemistry', 'Inorganic Chemistry', 'BioChemistry'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
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
</head>

<body>
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
                    $stmt = $conn->prepare("SELECT id, title, description FROM study_materials WHERE category = ? ORDER BY created_at DESC");
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
                            <div class="material-card" data-id="<?= $mid ?>">
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
                                                        if ($videoId) {
                                                            $oembed = @file_get_contents("https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=$videoId&format=json");
                                                            if ($oembed) {
                                                                $json = json_decode($oembed, true);
                                                                $displayName = $json['title'] ?? 'YouTube Video';
                                                            } else {
                                                                $displayName = 'YouTube Video';
                                                            }
                                                        } else {
                                                            $displayName = 'YouTube Video';
                                                        }
                                                    }
                                                }
                                            ?>
                                                <div class="material-item" data-file-id="<?= $f['id'] ?>" data-file-name="<?= htmlspecialchars($displayName) ?>" data-file-type="<?= $f['type'] ?>">
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
                                                            <a href="../<?= htmlspecialchars($f['path']) ?>" download class="download-btn">
                                                                <i class="fas fa-download"></i>
                                                            </a>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let currentModalInstance = null;
            let currentModalElement = null;

            // one-time bypass flags (used for the "Go back" button)
            let bypassPrereqOnce = false;
            // -----------------------------
            // Post-test prerequisite gating
            // -----------------------------
            const POSTTEST_STATUS = new Map(); // key: "Category::ModuleCode" => { passed: bool, bestPct, passingPct }

            function parseModuleCodeFromMaterialTitle(title) {
                const m = String(title || '').match(/^\s*Module\s+([A-Za-z0-9IVXLCDM]+)\s*\./i);
                return m ? m[1].trim() : null;
            }

            function parseModuleCodeFromPostTestTitle(title) {
                const m = String(title || '').match(/POST\s*TEST\s*\(\s*Module\s+([A-Za-z0-9IVXLCDM]+)\s*\)/i);
                return m ? m[1].trim() : null;
            }

            function toPercent(score, total) {
                const s = Number(score);
                const t = Number(total);
                if (!isFinite(s) || !isFinite(t) || t <= 0) return null;
                return Math.round((s / t) * 100);
            }

            async function loadPostTestStatus() {
                try {
                    const r = await fetch('../partial/exam_list.php', {
                        cache: 'no-store'
                    });
                    const j = await r.json();
                    const exams = Array.isArray(j.data) ? j.data : [];

                    for (const e of exams) {
                        const moduleCode = parseModuleCodeFromPostTestTitle(e.title);
                        if (!moduleCode) continue;

                        const totalItems = Number(e.total_questions || e.actual_questions || 0);
                        const bestPct = (e.user_score !== null && e.user_score !== undefined) ?
                            toPercent(e.user_score, totalItems) :
                            null;
                        const passingPct = (e.passing_score !== null && e.passing_score !== undefined) ?
                            Math.round(Number(e.passing_score)) :
                            null;

                        const passed = (bestPct !== null && passingPct !== null && bestPct >= passingPct);
                        POSTTEST_STATUS.set(`${e.category}::${moduleCode}`, {
                            passed,
                            bestPct,
                            passingPct
                        });
                    }
                } catch (err) {
                    console.error('Failed to load post-test status:', err);
                }
            }

            let prereqPrevMaterialId = null;

            function getLockedMessage(reason) {
                // Default message requested
                const def = "Locked content: You failed to pass the exam, haven't taken the exam yet, or haven't finished the previous module.";
                if (reason === 'not_finished') return "Locked content: You haven't finished the previous module yet. Complete it (100%) before proceeding.";
                if (reason === 'not_taken') return "Locked content: You haven't taken the previous module's post test yet. Take and pass it to unlock the next module.";
                if (reason === 'failed') return "Locked content: You failed to pass the exam. Please review the previous module/lesson and try again.";
                return def;
            }

            function showPrereqModal(prevMaterialId, reason = null) {
                prereqPrevMaterialId = prevMaterialId || null;
                const el = document.getElementById('prereqModalMessage');
                if (el) el.textContent = getLockedMessage(reason);

                // Hide Go back button if we don't know where to go
                const backBtn = document.getElementById('goBackToPrevBtn');
                if (backBtn) backBtn.style.display = prereqPrevMaterialId ? '' : 'none';

                const m = new bootstrap.Modal(document.getElementById('prereqModal'));
                m.show();
            }

            // Make "Go back" functional: open the previous module card
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

                    // Allow opening the previous module even if we're currently locked
                    bypassPrereqOnce = true;
                    btn.click();
                });
            }

            function getMaterialAverageProgress(mid) {

                const cont = document.getElementById('prog-' + mid);
                if (!cont) return 0;

                const items = cont.querySelectorAll('.progress-item .progress-percentage');
                if (!items.length) return 0;

                let sum = 0;
                let count = 0;
                items.forEach(el => {
                    const v = parseFloat(String(el.textContent || '').replace('%', '').trim());
                    if (!Number.isNaN(v)) {
                        sum += v;
                        count++;
                    }
                });
                return count ? (sum / count) : 0;
            }

            const tabs = document.querySelectorAll('.topic-tab');
            const sections = document.querySelectorAll('.topic-section');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const target = tab.dataset.topic;
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    sections.forEach(sec => sec.classList.toggle('active', sec.dataset.topic === target));
                });
            });

            function getYouTubeEmbed(url) {
                if (!url) return '';
                const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
                const match = url.match(regExp);
                const videoId = match && match[2].length === 11 ? match[2] : null;
                return videoId ? `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1` : url;
            }

            async function getYouTubeTitle(videoUrl) {
                try {
                    const videoIdMatch = videoUrl.match(/(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/watch\?.+&v=))([^"&?\/\s]{11})/i);
                    if (!videoIdMatch) return 'Untitled Video';
                    const videoId = videoIdMatch[1];
                    const response = await fetch(`https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=${videoId}&format=json`);
                    if (!response.ok) return 'Untitled Video';
                    const data = await response.json();
                    return data.title || 'Untitled Video';
                } catch (err) {
                    return 'Untitled Video';
                }
            }

            function getFileDisplayName(fileId) {
                const item = document.querySelector(`[data-file-id="${fileId}"]`);
                return item ? (item.dataset.fileName || 'File') : 'File';
            }

            function markAsCompleted(fileId) {
                // Kept for backward compatibility
                saveProgressSmart(fileId, 100, {
                    force: true
                });
                updateProgressBar(fileId, 100);
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
                    const iframes = currentModalElement.querySelectorAll('iframe');
                    iframes.forEach(iframe => iframe.src = 'about:blank');
                    currentModalElement.remove();
                    currentModalElement = null;
                }
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }

            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', e => {
                    e.stopPropagation();
                    const fid = btn.dataset.fid;
                    destroyModal();
                    openSingleFile(fid);
                });
            });

            document.querySelectorAll('.start-learning-btn').forEach(btn => {
                btn.addEventListener('click', async e => {
                    e.stopPropagation();
                    const mid = btn.dataset.id;

                    // Prerequisite: if this is not the first module in the category,
                    // require previous module to be finished (100%) and its POST TEST passed.
                    if (!bypassPrereqOnce) {
                        try {
                            const card = btn.closest('.material-card');
                            const section = btn.closest('.topic-section');
                            const category = section?.dataset?.topic || null;

                            if (category && card && section) {
                                const cards = Array.from(section.querySelectorAll('.material-card'));
                                const idx = cards.indexOf(card);

                                if (idx > 0) {
                                    const prevCard = cards[idx - 1];
                                    const prevMid = prevCard?.dataset?.id || null;
                                    const prevTitle = prevCard?.querySelector('.card-title')?.textContent?.trim() || '';
                                    const prevModuleCode = parseModuleCodeFromMaterialTitle(prevTitle);

                                    // 1) Check previous module finished (100%)
                                    if (prevMid) {
                                        const prevAvg = getMaterialAverageProgress(prevMid);
                                        if (prevAvg < 100) {
                                            showPrereqModal(prevMid, 'not_finished');
                                            return;
                                        }
                                    }

                                    // 2) Check previous module post-test passed
                                    if (prevModuleCode) {
                                        const st = POSTTEST_STATUS.get(`${category}::${prevModuleCode}`);
                                        if (!st) {
                                            showPrereqModal(prevMid, 'not_taken');
                                            return;
                                        }
                                        if (st.passed !== true) {
                                            showPrereqModal(prevMid, 'failed');
                                            return;
                                        }
                                    }
                                }
                            }
                        } catch (err) {
                            console.error('Prereq check failed:', err);
                        }
                    } else {
                        bypassPrereqOnce = false;
                    }
                    destroyModal();

                    try {
                        const resp = await fetch(`../partial/get_material_files.php?id=${mid}`);
                        const data = await resp.json();

                        const materialTitle = btn.closest('.material-card').querySelector('.card-title').textContent.trim();
                        const materialDesc = btn.closest('.material-card').querySelector('.card-description').textContent.trim();

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
                                const fname = f.title || f.path.split('/').pop()
                                    .replace(/^pdf_[a-f0-9]{11}\./i, '')
                                    .replace('.pdf', '')
                                    .replace(/_/g, ' ');
                                list += `
                        <div class="file-list-item pdf" onclick="openFile(${f.id})">
                            <i class="fas fa-file-pdf file-icon"></i>
                            <div class="file-info">
                                <div class="file-title">${fname}</div>
                             </div>
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
                                if (!displayTitle || displayTitle.trim() === '' || displayTitle === 'Untitled Video') {
                                    displayTitle = await getYouTubeTitle(v.path);
                                }
                                list += `
                        <div class="file-list-item video" onclick="openFile(${v.id})">
                            <i class="fas fa-play-circle file-icon"></i>
                            <div class="file-info">
                                <div class="file-title">${displayTitle}</div>
                             </div>
                            <i class="fas fa-arrow-right action-icon"></i>
                        </div>`;
                            }
                            const showActive = !hasPdfs ? ' show active' : '';
                            panesHtml += `<div class="tab-pane fade${showActive}" id="tab-videos">${list}</div>`;
                        }

                        body.innerHTML = `
                    <div class="h-100 d-flex flex-column">
                        <div class="border-bottom bg-white px-3 py-3 px-md-4">
                            <h4 class="mb-3 fw-bold text-center text-dark">${materialTitle}</h4>
                            <div class="material-desc">${materialDesc || 'Select a resource to begin studying.'}</div>
                        </div>
                        <ul class="nav nav-tabs px-2 pt-2 border-bottom-0">${tabsHtml}</ul>
                        <div class="tab-content flex-grow-1 overflow-auto p-2 p-md-3">${panesHtml}</div>
                    </div>`;

                        currentModalInstance = new bootstrap.Modal(modal, {
                            backdrop: 'static',
                            keyboard: false
                        });
                        currentModalElement = modal;
                        currentModalInstance.show();

                    } catch (err) {
                        alert('Error loading materials');
                        console.error(err);
                    }
                });
            });

            window.openFile = function(fid) {
                destroyModal();
                openSingleFile(fid);
            };

            async function openSingleFile(fid) {

                fetch(`../partial/get_one_file.php?fid=${fid}`)
                    .then(r => r.json())
                    .then(d => {
                        if (!d?.type) {
                            alert('File not found');
                            return;
                        }

                        const title = d.title || (d.type === 'pdf' ? 'PDF Document' : 'Video Lesson');
                        const modal = createModal(title, 'custom-modal');
                        const body = modal.querySelector('#viewerBody');

                        if (d.type === 'pdf') {
                            body.innerHTML = `<iframe src="../${d.path}" style="width:100%;height:100%;border:none;"></iframe>`;
                            setupPdfTimeTracking(fid, `../${d.path}`);
                        } else if (d.type === 'youtube') {
                            body.innerHTML = renderYouTubePlayerHtml(fid, d.path);
                            setupYouTubeProgressTracking(fid, d.path);
                        }

                        currentModalInstance = new bootstrap.Modal(modal, {
                            backdrop: 'static',
                            keyboard: false
                        });
                        currentModalElement = modal;

                        modal.addEventListener('hidden.bs.modal', destroyModal);
                        currentModalInstance.show();
                    })
                    .catch(() => alert('Error loading content'));
            }

            function setupPdfTimeTracking(fileId, url) {
                const MIN_SECONDS = 120;
                const MAX_SECONDS = 2400;
                const SECONDS_PER_MB = 300;

                async function getPdfSizeBytes(u) {
                    try {
                        const head = await fetch(u, {
                            method: 'HEAD',
                            cache: 'no-store'
                        });
                        if (head && head.ok) {
                            const len = head.headers.get('content-length');
                            if (len && !isNaN(Number(len))) return Number(len);
                        }
                    } catch (e) {}

                    try {
                        const rangeRes = await fetch(u, {
                            method: 'GET',
                            headers: {
                                'Range': 'bytes=0-0'
                            },
                            cache: 'no-store'
                        });
                        if (rangeRes && (rangeRes.status === 206 || rangeRes.ok)) {
                            const cr = rangeRes.headers.get('content-range');
                            if (cr) {
                                const total = cr.split('/')[1];
                                if (total && !isNaN(Number(total))) return Number(total);
                            }
                            const len = rangeRes.headers.get('content-length');
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

                getPdfSizeBytes(url).then((bytes) => {
                    const secondsToComplete = estimateSeconds(bytes);
                    startTimeBasedTracking(fileId, secondsToComplete);
                });
            }


            function createModal(title = "Viewer", extraClass = "") {
                const modal = document.createElement('div');
                modal.className = `modal fade ${extraClass}`;
                modal.innerHTML = `
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" style="min-height: 70vh;">
                    <div class="modal-header">
                        <h5 class="modal-title w-100 text-center">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0" id="viewerBody">
                        <div class="d-flex justify-content-center align-items-center h-100 bg-light">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
                document.body.appendChild(modal);
                return modal;
            }

            function saveProgress(fid, pct) {
                fetch('../partial/save_progress.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `file_id=${fid}&progress=${pct}`
                }).catch(() => {});
            }

            // =============================
            // Dynamic progress tracking utils
            // =============================

            const __progressState = {
                lastSavedPct: new Map(),
                lastSentAt: new Map(),
                lastUiPct: new Map(),
                cleanups: [],
            };

            function registerCleanup(fn) {
                if (typeof fn === 'function') __progressState.cleanups.push(fn);
            }

            function runAndClearCleanups() {
                try {
                    __progressState.cleanups.forEach(fn => {
                        try {
                            fn();
                        } catch (e) {}
                    });
                } finally {
                    __progressState.cleanups = [];
                }
            }

            function clampPct(pct) {
                pct = Number(pct);
                if (!Number.isFinite(pct)) return 0;
                return Math.max(0, Math.min(100, pct));
            }

            function saveProgressSmart(fileId, pct, opts = {}) {
                const {
                    force = false
                } = opts;
                pct = Math.round(clampPct(pct));

                const prev = __progressState.lastSavedPct.get(String(fileId)) ?? 0;
                if (pct < prev) pct = prev;

                const now = Date.now();
                const lastAt = __progressState.lastSentAt.get(String(fileId)) ?? 0;

                // throttle: at most 1 request per 3 seconds (unless forced)
                if (!force && now - lastAt < 3000) return;

                // only save if it moved forward by at least 2% (unless forced or completed)
                if (!force && pct !== 100 && (pct - prev) < 2) return;

                __progressState.lastSavedPct.set(String(fileId), pct);
                __progressState.lastSentAt.set(String(fileId), now);

                saveProgress(fileId, pct);
            }

            function updateProgressUiSmart(fileId, pct) {
                pct = Math.round(clampPct(pct));
                const prevUi = __progressState.lastUiPct.get(String(fileId)) ?? 0;
                if (pct < prevUi) pct = prevUi; // no decrease in UI
                if (pct === prevUi) return;

                __progressState.lastUiPct.set(String(fileId), pct);
                updateProgressBar(fileId, pct);
            }

            function startTimeBasedTracking(fileId, secondsToComplete = 600) {
                let seconds = 0;

                const intervalId = setInterval(() => {
                    seconds += 5;

                    const pct = (seconds / Math.max(1, secondsToComplete)) * 100;
                    updateProgressUiSmart(fileId, pct);
                    saveProgressSmart(fileId, pct);
                }, 5000);

                const warmup = setTimeout(() => {
                    updateProgressUiSmart(fileId, 1);
                    saveProgressSmart(fileId, 1, {
                        force: true
                    });
                }, 1500);

                registerCleanup(() => {
                    clearInterval(intervalId);
                    clearTimeout(warmup);
                    // this save on close
                    const pct = (seconds / Math.max(1, secondsToComplete)) * 100;
                    saveProgressSmart(fileId, pct, {
                        force: true
                    });
                });
            }

            // -----------------------------
            // YouTube tracking (IFrame API)
            // -----------------------------

            let __ytApiPromise = null;

            function ensureYouTubeApi() {
                if (window.YT && window.YT.Player) return Promise.resolve();

                if (__ytApiPromise) return __ytApiPromise;

                __ytApiPromise = new Promise((resolve) => {
                    if (!document.querySelector('script[src*="youtube.com/iframe_api"]')) {
                        const tag = document.createElement('script');
                        tag.src = 'https://www.youtube.com/iframe_api';
                        document.head.appendChild(tag);
                    }

                    const prev = window.onYouTubeIframeAPIReady;
                    window.onYouTubeIframeAPIReady = function() {
                        try {
                            if (typeof prev === 'function') prev();
                        } catch (e) {}
                        resolve();
                    };

                    // will fallback: poll
                    const poll = setInterval(() => {
                        if (window.YT && window.YT.Player) {
                            clearInterval(poll);
                            resolve();
                        }
                    }, 200);
                    setTimeout(() => clearInterval(poll), 8000);
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
                const src = vid ?
                    `https://www.youtube.com/embed/${vid}?enablejsapi=1&origin=${origin}&rel=0&modestbranding=1` :
                    getYouTubeEmbed(url);

                return `
                    <div class="ratio ratio-16x9 h-100">
                        <iframe
                            id="${iframeId}"
                            data-yt-file-id="${fileId}"
                            data-yt-video-id="${vid || ''}"
                            src="${src}"
                            allowfullscreen
                            allow="autoplay; encrypted-media; picture-in-picture"
                            style="border:none;"></iframe>
                    </div>
                `;
            }

            function setupYouTubeProgressTracking(fileId, url) {
                const vid = extractYouTubeId(url);
                // If can't extract id, we can't use API reliably, fallback to marking 100 after some watch time.
                if (!vid) {
                    startTimeBasedTracking(fileId, 420);
                    return;
                }

                ensureYouTubeApi().then(() => {
                    const iframe = document.querySelector(`iframe[data-yt-file-id="${fileId}"]`);
                    if (!iframe) return;

                    let player = null;
                    let tick = null;

                    try {
                        player = new YT.Player(iframe.id, {
                            events: {
                                onReady: () => {
                                    // Poll current time/duration
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
                                            saveProgressSmart(fileId, pct, {
                                                force: true
                                            });
                                        }
                                        if (e.data === YT.PlayerState.ENDED) {
                                            updateProgressUiSmart(fileId, 100);
                                            saveProgressSmart(fileId, 100, {
                                                force: true
                                            });
                                        }
                                    } catch (err) {}
                                }
                            }
                        });
                    } catch (e) {
                        // Fallback: time-based
                        startTimeBasedTracking(fileId, 420);
                        return;
                    }

                    registerCleanup(() => {
                        if (tick) clearInterval(tick);
                        try {
                            const dur = player?.getDuration?.() || 0;
                            const cur = player?.getCurrentTime?.() || 0;
                            if (dur) saveProgressSmart(fileId, (cur / dur) * 100, {
                                force: true
                            });
                        } catch (e) {}
                        try {
                            player?.destroy?.();
                        } catch (e) {}
                    });
                });
            }

            function updateProgressBar(fid, pct) {
                fetch(`../partial/get_material_from_file.php?fid=${fid}`)
                    .then(r => r.json())
                    .then(d => {
                        if (!d?.material_id) return;

                        const cont = document.getElementById('prog-' + d.material_id);
                        if (!cont) return;

                        let item = cont.querySelector(`[data-fid="${fid}"]`);
                        const name = getFileDisplayName(fid);

                        if (!item) {
                            if (!cont.querySelector('.progress-header')) {
                                cont.innerHTML = `<div class="progress-header"><i class="fas fa-chart-line"></i> Your Progress</div>`;
                            }

                            cont.insertAdjacentHTML('beforeend', `
                        <div class="progress-item" data-fid="${fid}">
                            <div class="progress-item-left">
                                <div class="progress-item-name" title="${name}">${name}</div>
                                <div class="progress-bar-wrapper">
                                    <div class="progress-bar-fill" style="width:${pct}%"></div>
                                </div>
                            </div>
                            <div class="progress-item-right">
                                <span class="progress-percentage">${pct}%</span>
                                ${pct >= 100 ? '<i class="fas fa-check-circle progress-check"></i>' : ''}
                            </div>
                        </div>`);
                        } else {
                            item.querySelector('.progress-bar-fill').style.width = pct + '%';
                            item.querySelector('.progress-percentage').textContent = pct + '%';

                            if (pct >= 100 && !item.querySelector('.progress-check')) {
                                item.querySelector('.progress-item-right').insertAdjacentHTML('beforeend',
                                    '<i class="fas fa-check-circle progress-check"></i>');
                            }
                        }
                    });
            }
            fetch('../partial/get_progress.php')
                .then(r => r.json())
                .then(({
                    data
                }) => {
                    if (!Array.isArray(data)) return;

                    const materialMap = new Map();

                    data.forEach(mat => {
                        materialMap.set(String(mat.id), mat.files);
                    });

                    document.querySelectorAll('.material-card').forEach(card => {
                        const mid = card.dataset.id;
                        const cont = document.getElementById('prog-' + mid);
                        const files = materialMap.get(mid);

                        if (!cont || !files || files.length === 0) return;

                        cont.innerHTML = `
        <div class="progress-header">
          <i class="fas fa-chart-line"></i> Your Progress
        </div>
      `;

                        files.forEach(p => {
                            const name = getFileDisplayName(p.id);

                            cont.insertAdjacentHTML('beforeend', `
          <div class="progress-item" data-fid="${p.id}">
            <div class="progress-item-left">
              <div class="progress-item-name" title="${name}">${name}</div>
              <div class="progress-bar-wrapper">
                <div class="progress-bar-fill" style="width:${p.progress}%"></div>
              </div>
            </div>
            <div class="progress-item-right">
              <span class="progress-percentage">${p.progress}%</span>
              ${p.progress >= 100 
                  ? '<i class="fas fa-check-circle progress-check"></i>' 
                  : ''}
            </div>
          </div>
        `);
                        });
                    });
                })
                .catch(err => console.error('Progress fetch error:', err));

            // document.querySelectorAll('.material-card').forEach(card => {
            //     const mid = card.dataset.id;
            //     fetch(`../partial/get_progress.php?material_id=${mid}`)
            //         .then(r => r.json())
            //         .then(data => {
            //             const cont = document.getElementById('prog-' + mid);
            //             if (!cont || !Array.isArray(data) || data.length === 0) return;

            //             cont.innerHTML = `<div class="progress-header"><i class="fas fa-chart-line"></i> Your Progress</div>`;

            //             data.forEach(p => {
            //                 const name = getFileDisplayName(p.file_id);
            //                 cont.insertAdjacentHTML('beforeend', `
            //                     <div class="progress-item" data-fid="${p.file_id}">
            //                         <div class="progress-item-left">
            //                             <div class="progress-item-name" title="${name}">${name}</div>
            //                             <div class="progress-bar-wrapper">
            //                                 <div class="progress-bar-fill" style="width:${p.progress}%"></div>
            //                             </div>
            //                         </div>
            //                         <div class="progress-item-right">
            //                             <span class="progress-percentage">${p.progress}%</span>
            //                             ${p.progress >= 100 ? '<i class="fas fa-check-circle progress-check"></i>' : ''}
            //                         </div>
            //                     </div>`);
            //             });
            //         });
            // });
        });
    </script>
</body>

</html>