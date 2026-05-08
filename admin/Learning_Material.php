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
    <title>ChemEase Admin - Learning Materials</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="learning_material.css">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">
    <link rel="apple-touch-icon" href="/apple-icon.png">
    <link rel="apple-touch-icon-precomposed" href="/apple-icon-precomposed.png">
    <link rel="icon" type="image/png" sizes="36x36" href="/android-icon-36x36.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/android-icon-48x48.png">
    <link rel="icon" type="image/png" sizes="72x72" href="/android-icon-72x72.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/android-icon-96x96.png">
    <link rel="icon" type="image/png" sizes="144x144" href="/android-icon-144x144.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/android-icon-192x192.png">
    <meta name="msapplication-TileColor" content="#0d6efd">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <meta name="msapplication-square70x70logo" content="/ms-icon-70x70.png">
    <meta name="msapplication-square150x150logo" content="/ms-icon-150x150.png">
    <meta name="msapplication-square310x310logo" content="/ms-icon-310x310.png">

    <style>
        :root {
            --primary: #17a2b8;
            --primary-dark: #138496;
            --danger: #dc3545;
            --success: #28a745;
            --gray: #6c757d;
            --light: #f8f9fa;
            --dark: #343a40;
        }

       .category-section {
            grid-column: 1 / -1;
            margin-bottom: 10px;
        }
        
        .category-header {
            background: #ffffff;
            padding: 12px 18px;
            border-radius: 14px;
            cursor: pointer;
            border: 1px solid #e5e7eb;
            margin-bottom: 0;
            min-height: 58px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        
        .category-header:hover {
            background: #f8fafc;
            border-color: #dbeafe;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        }
        
        .category-header .fw-semibold {
            font-size: 0.98rem;
            line-height: 1.2;
            color: #1f2937;
            margin-bottom: 2px;
        }
        
        .category-header small {
            font-size: 0.82rem;
            line-height: 1.1;
        }
        
        .category-header .badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.38rem 0.62rem;
            border-radius: 999px;
        }
        
        .category-icon-wrap {
            width: 38px;
            height: 38px;
            min-width: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #fff7d6;
        }
        
        .category-content {
            background: #ffffff;
            border: 1px solid #eef2f7;
            border-top: none;
            border-radius: 0 0 14px 14px;
            padding: 14px;
            margin-top: -2px;
            margin-bottom: 0;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        }
        
        .category-content.d-none {
            display: none !important;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }
        
        .rotate {
            transform: rotate(180deg);
            transition: transform 0.2s ease;
        }

        .rotate {
            transform: rotate(180deg);
            transition: transform 0.25s ease;
        }
        
        .materials-grid {
            display: grid;
            gap: 12px;
        }

        .material-card {
            border-radius: 18px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e9ecef;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
            transition: all 0.25s ease;
            min-height: 240px;
        }

        .material-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
        }

        .material-thumbnail {
            height: 120px;
        }

        .material-content {
            padding: 16px 16px 18px;
        }

        .material-title {
            font-size: 1rem;
            line-height: 1.45;
            margin-bottom: 10px;
            min-height: 48px;
        }

        .material-type {
            margin-bottom: 10px;
            font-size: 0.92rem;
            color: #6c757d;
        }

        .material-category {
            display: inline-flex;
            align-items: center;
            padding: 0.38rem 0.7rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .file-list {
            margin: 1rem 0;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f1f8ff;
            border-radius: 12px;
            margin-bottom: 0.8rem;
            transition: all 0.3s;
            border-left: 4px solid var(--primary);
        }

        .file-item:hover {
            background: #e3f2fd;
            transform: translateX(5px);
        }

        .file-item i {
            font-size: 1.8rem;
            margin-right: 1rem;
            color: var(--primary);
        }

        .file-item.pdf i {
            color: #dc3545;
        }

        .file-item.youtube i {
            color: #ff0000;
        }

        .file-item a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .file-item a:hover {
            text-decoration: underline;
        }

        .modal-header {
            background: var(--primary);
            color: white;
        }

        .modal-title {
            font-weight: 700;
        }

        .editable-file-name {
            flex: 1;
            margin: 0 0.5rem;
            padding: 0.4rem 0.8rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .delete-file-btn {
            color: var(--danger);
            background: none;
            border: none;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.3rem 0.6rem;
        }

        .delete-file-btn:hover {
            color: #c82333;
        }

        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin: 3rem 0 1rem;
        }

        .pagination-btn {
            min-width: 44px;
            height: 44px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            background: white;
            color: var(--gray);
            font-weight: 600;
            transition: all 0.3s;
        }

        .pagination-btn:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .pagination-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .load-more-btn {
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            border-radius: 12px;
        }

        .material-actions .btn {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }

        .upload-preview-container {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px dashed #ced4da;
            max-height: 220px;
            overflow-y: auto;
        }

        .preview-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 1rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 0.6rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: background 0.2s;
        }

        .preview-item:hover {
            background: #f0f8ff;
        }

        .preview-item i {
            font-size: 1.6rem;
            margin-right: 1rem;
        }

        .preview-item.pdf i {
            color: #dc3545;
        }

        .preview-item.youtube i {
            color: #ff0000;
        }

        .preview-filename {
            flex: 1;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .preview-size {
            color: #6c757d;
            font-size: 0.85rem;
            margin-left: 1rem;
        }

        .remove-preview-btn {
            color: #dc3545;
            background: none;
            border: none;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.4rem;
            margin-left: 0.8rem;
        }

        .remove-preview-btn:hover {
            color: #c82333;
        }

        .youtube-preview-list {
            margin-top: 0.5rem;
            padding: 0.8rem;
            background: #fff5f5;
            border-radius: 8px;
            border: 1px dashed #ffcccc;
        }

        .youtube-preview-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.8rem;
            background: white;
            border-radius: 6px;
            margin-bottom: 0.4rem;
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
            <div class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a></div>
            <div class="nav-item"><a href="Users.php" class="nav-link"><i class="fas fa-users"></i><span>Users</span></a></div>
            <?php if ($isAdmin): ?>
                <div class="nav-item"><a href="Learning_Material.php" class="nav-link active"><i class="fas fa-book"></i><span>Learning Materials</span></a></div>
                <div class="nav-item"><a href="Practice_Exams.php" class="nav-link"><i class="fas fa-clipboard-list"></i><span>Practice Exams</span></a></div>
            <?php endif; ?>
            <?php if ($isSuperAdmin || $isAdmin): ?>
                <div class="nav-item">
                    <a href="Discussion_Forums.php" class="nav-link">
                        <i class="fas fa-comments"></i>
                        <span>Discussion Forums</span>
                    </a>
                </div>
            <?php endif; ?>
            <?php if ($isSuperAdmin): ?>
                <div class="nav-item"><a href="Generate_Reports.php" class="nav-link"><i class="fas fa-file-lines"></i><span>Reports & Analytics</span></a></div>
            <?php endif; ?>
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
        <div class="materials-container">
            <div class="materials-header">
                <h1 class="materials-title">Learning Materials</h1>
                <div class="stats-row">
                    <?php
                    $total = $conn->query("SELECT COUNT(*) FROM study_materials")->fetch_row()[0];
                    $files = $conn->query("SELECT COUNT(*) FROM study_material_files")->fetch_row()[0];
                    ?>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-label">Total Materials</div>
                            <div class="stat-number"><?= $total ?></div>
                        </div>
                        <div class="stat-icon blue"><i class="fas fa-book-open"></i></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-label">Total Files/Videos</div>
                            <div class="stat-number"><?= $files ?></div>
                        </div>
                        <div class="stat-icon green"><i class="fas fa-file-alt"></i></div>
                    </div>
                </div>
            </div>

            <div class="materials-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search materials...">
                </div>
                <div class="control-buttons">
                    <button class="add-material-btn" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> ADD MATERIALS
                    </button>
                </div>
            </div>

            <div class="materials-grid" id="materialsGrid">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3 text-muted">Loading materials...</p>
                </div>
            </div>

            <div class="pagination-container" id="paginationContainer" style="display:none;">
                <button class="btn load-more-btn" id="loadMoreBtn">Load More</button>
                <div id="pageNumbers" class="d-flex gap-2"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="Analytical Chemistry">Analytical Chemistry</option>
                                <option value="Organic Chemistry">Organic Chemistry</option>
                                <option value="Physical Chemistry">Physical Chemistry</option>
                                <option value="Inorganic Chemistry">Inorganic Chemistry</option>
                                <option value="BioChemistry">BioChemistry</option>
                            </select>
                        </div>

                        <hr>

                        <h6>PDF Files (multiple)</h6>
                        <div class="mb-3">
                            <input type="file" name="pdfs[]" accept=".pdf" class="form-control" multiple id="pdfInput">
                        </div>
                        <div id="pdfPreview" class="upload-preview-container" style="display:none;"></div>

                        <h6>YouTube Videos (one per line)</h6>
                        <div class="mb-3">
                            <textarea name="youtube_urls" class="form-control" rows="5" placeholder="https://www.youtube.com/watch?v=xxxx&#10;https://youtu.be/yyyy" id="youtubeInput"></textarea>
                            <small class="text-muted">Paste full YouTube URLs (one per line).</small>
                        </div>
                        <div id="youtubePreview" class="youtube-preview-list" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload & Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function toggleSidebar() {
                document.querySelectorAll('.sidebar, .top-navbar, .main-content').forEach(el => el.classList.toggle('collapsed'));
                const i = document.querySelector('.collapse-btn i');
                i.classList.toggle('fa-chevron-left');
                i.classList.toggle('fa-chevron-right');
            }

            window.toggleSidebar = toggleSidebar;

            let currentPage = 1;
            let currentSearch = '';
            let totalPages = 1;
            let isLoading = false;

            async function loadMaterials(page = 1, append = false) {
                if (isLoading) return;
                isLoading = true;

                const grid = document.getElementById('materialsGrid');
                if (!grid) {
                    isLoading = false;
                    return;
                }

                if (!append) {
                    grid.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3 text-muted">Loading...</p></div>';
                }

                try {
                    const url = `../partial/get_materials_paginated.php?all=1&search=${encodeURIComponent(currentSearch)}`;
                    const resp = await fetch(url);

                    if (!resp.ok) {
                        const text = await resp.text().catch(() => '');
                        throw new Error(`HTTP ${resp.status} ${resp.statusText} ${text ? '- ' + text.slice(0, 200) : ''}`);
                    }

                    const data = await resp.json();

                    if (!append) grid.innerHTML = '';

                    if (!data.folders || Object.keys(data.folders).length === 0) {
                        grid.innerHTML = '<div class="text-center py-5"><p class="text-muted fs-5">No materials found.</p></div>';
                    } else {
                        renderFolders(data.folders || {});
                    }

                    totalPages = data.total_pages || 1;
                    currentPage = data.current_page || 1;

                    const pagContainer = document.getElementById('paginationContainer');
                    const loadMoreBtn = document.getElementById('loadMoreBtn');
                    const pageNumbers = document.getElementById('pageNumbers');

                    if (pagContainer) {
                        pagContainer.style.display = (data.total_items > 0 && totalPages > 1) ? 'flex' : 'none';
                    }

                    if (loadMoreBtn) {
                        loadMoreBtn.style.display = data.has_more && totalPages > 1 ? 'block' : 'none';
                    }

                    if (pageNumbers) {
                        pageNumbers.innerHTML = '';

                        if (totalPages > 1) {
                            const maxVisible = 5;
                            let start = Math.max(1, currentPage - 2);
                            let end = Math.min(totalPages, start + maxVisible - 1);

                            if (end - start + 1 < maxVisible) {
                                start = Math.max(1, end - maxVisible + 1);
                            }

                            if (start > 1) {
                                pageNumbers.innerHTML += `<button class="btn pagination-btn" onclick="goToPage(1)">1</button><span>...</span>`;
                            }

                            for (let i = start; i <= end; i++) {
                                pageNumbers.innerHTML += `
                                    <button class="btn pagination-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">
                                        ${i}
                                    </button>
                                `;
                            }

                            if (end < totalPages) {
                                pageNumbers.innerHTML += `<span>...</span><button class="btn pagination-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`;
                            }
                        }
                    }

                    attachCardEvents();
                } catch (err) {
                    console.error(err);
                    grid.innerHTML = `<div class="text-center py-5 text-danger">Error loading materials.<br><small>${(err && err.message) ? err.message : ''}</small></div>`;
                } finally {
                    isLoading = false;
                }
            }

            window.goToPage = function(page) {
                if (page < 1 || page > totalPages || page === currentPage) return;
                currentPage = page;
                loadMaterials(page, false);
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            };

            document.getElementById('loadMoreBtn')?.addEventListener('click', () => {
                loadMaterials(currentPage + 1, true);
            });

            document.getElementById('searchInput')?.addEventListener('input', e => {
                currentSearch = e.target.value.trim();
                currentPage = 1;
                loadMaterials(1, false);
            });

            function attachCardEvents() {
                document.querySelectorAll('.clickable-card').forEach(card => {
                    card.onclick = () => {
                        const id = card.dataset.id;
                        window.location.href = `View_Material.php?id=${id}`;
                    };
                });
            }

            function renderFolders(folders) {
                const grid = document.getElementById('materialsGrid');
                grid.innerHTML = '';

                const categoryOrder = [
                    "Analytical Chemistry",
                    "Organic Chemistry",
                    "Physical Chemistry",
                    "Inorganic Chemistry",
                    "BioChemistry"
                ];

                categoryOrder.forEach((category, index) => {
                    const materials = folders?.[category] ?? [];
                    const isFirst = index === 0;

                    const section = document.createElement('div');
                    section.className = 'category-section';

                    section.innerHTML = `
                        <div class="category-header" onclick="toggleCategory(this)">
                            <div class="d-flex align-items-center w-100">
                                <div class="category-icon-wrap">
                                    <i class="fas fa-folder text-warning"></i>
                                </div>

                                <div class="d-flex flex-column">
                                    <span class="fw-semibold">${category}</span>
                                    <small class="text-muted">${materials.length} material(s)</small>
                                </div>

                                <span class="badge bg-secondary ms-auto me-2">${materials.length}</span>
                                <i class="fas fa-chevron-down ${isFirst ? 'rotate' : ''}"></i>
                            </div>
                        </div>

                        <div class="category-content ${isFirst ? '' : 'd-none'}">
                            <div class="category-grid"></div>
                            ${materials.length === 0 ? `<div class="text-muted small mt-2">No materials in this category.</div>` : ``}
                        </div>
                    `;

                    const innerGrid = section.querySelector('.category-grid');
                    materials.forEach(row => {
                        innerGrid.insertAdjacentHTML('beforeend', buildMaterialCard(row));
                    });

                    grid.appendChild(section);
                });

                attachCardEvents();
            }

            window.toggleCategory = function(header) {
                const content = header.nextElementSibling;
                content.classList.toggle('d-none');
                header.querySelector('.fa-chevron-down')?.classList.toggle('rotate');
            };

            function buildMaterialCard(row) {
                const catClass = row.category.toLowerCase().replace(/ /g, '-');
                return `
                    <div class="material-card clickable-card" data-id="${row.id}" style="cursor:pointer;">
                        <div class="material-thumbnail ${catClass}">
                            <div class="material-placeholder"><i class="fas fa-book"></i></div>
                        </div>

                        <div class="material-content">
                            <h3 class="material-title">${row.title}</h3>
                            <div class="material-type"><span>${row.file_count} item(s)</span></div>
                            <span class="material-category ${catClass}">${row.category}</span>
                        </div>
                    </div>
                `;
            }

            const pdfInput = document.getElementById('pdfInput');
            const pdfPreview = document.getElementById('pdfPreview');
            const youtubeInput = document.getElementById('youtubeInput');
            const youtubePreview = document.getElementById('youtubePreview');

            pdfInput?.addEventListener('change', function() {
                pdfPreview.innerHTML = '';
                pdfPreview.style.display = 'none';

                if (this.files.length === 0) return;

                pdfPreview.style.display = 'block';

                Array.from(this.files).forEach((file, index) => {
                    const item = document.createElement('div');
                    item.className = 'preview-item pdf';
                    item.style.cursor = 'pointer';
                    item.innerHTML = `
                        <i class="fas fa-file-pdf"></i>
                        <div class="preview-filename">${file.name}</div>
                        <div class="preview-size">${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                        <button class="remove-preview-btn" type="button" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    pdfPreview.appendChild(item);

                    item.addEventListener('click', (e) => {
                        if (e.target.closest('.remove-preview-btn')) return;
                        const url = URL.createObjectURL(file);
                        window.open(url, '_blank');
                        setTimeout(() => URL.revokeObjectURL(url), 30000);
                    });

                    item.querySelector('.remove-preview-btn').onclick = (e) => {
                        e.stopPropagation();
                        const dt = new DataTransfer();

                        Array.from(pdfInput.files).forEach((f, i) => {
                            if (i !== index) dt.items.add(f);
                        });

                        pdfInput.files = dt.files;
                        item.remove();

                        if (pdfPreview.children.length === 0) {
                            pdfPreview.style.display = 'none';
                        }
                    };
                });
            });

            function updateYoutubePreview() {
                youtubePreview.innerHTML = '';
                youtubePreview.style.display = 'none';

                const text = youtubeInput.value.trim();
                if (!text) return;

                const lines = text.split('\n').map(l => l.trim()).filter(l => l);
                if (lines.length === 0) return;

                youtubePreview.style.display = 'block';

                lines.forEach((url, index) => {
                    const item = document.createElement('div');
                    item.className = 'youtube-preview-item';
                    item.innerHTML = `
                        <i class="fab fa-youtube"></i>
                        <div class="preview-filename">${url}</div>
                        <button class="remove-preview-btn" type="button" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    youtubePreview.appendChild(item);

                    item.querySelector('.remove-preview-btn').onclick = () => {
                        const lines = youtubeInput.value.split('\n').map(l => l.trim());
                        lines.splice(index, 1);
                        youtubeInput.value = lines.join('\n');
                        updateYoutubePreview();
                    };
                });
            }

            youtubeInput?.addEventListener('input', updateYoutubePreview);

            const addModalEl = document.getElementById('addModal');
            addModalEl?.addEventListener('hidden.bs.modal', () => {
                pdfInput.value = '';
                youtubeInput.value = '';
                pdfPreview.innerHTML = '';
                pdfPreview.style.display = 'none';
                youtubePreview.innerHTML = '';
                youtubePreview.style.display = 'none';
            });

            document.getElementById('uploadForm')?.addEventListener('submit', async e => {
                e.preventDefault();
                const formData = new FormData(e.target);

                try {
                    const resp = await fetch('../partial/upload_material_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await resp.json();
                    alert(data.message || data.error || 'Unknown response');

                    if (data.status === 'success') {
                        bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
                        loadMaterials(1, false);
                    }
                } catch (err) {
                    alert('Upload failed');
                }
            });

            loadMaterials(1);
        });
    </script>
</body>

</html>