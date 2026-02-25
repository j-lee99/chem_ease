<?php
session_start();
require_once '../partial/db_conn.php';

$role = $_SESSION['role'] ?? '';
$isAdmin = ($role === 'admin');
$isSuperAdmin = ($role === 'super_admin');
// If NOT logged in OR not admin → back to login
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
            echo "ChemEase Super Admin Panel - Users";
        } elseif ($isAdmin) {
            echo "ChemEase Admin Panel - Users";
        } else {
            echo "ChemEase - Users";
        }
        ?>
    </title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <!-- Apple Touch Icons -->
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
    <!-- Android Icons -->
    <link rel="icon" type="image/png" sizes="36x36" href="/android-icon-36x36.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/android-icon-48x48.png">
    <link rel="icon" type="image/png" sizes="72x72" href="/android-icon-72x72.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/android-icon-96x96.png">
    <link rel="icon" type="image/png" sizes="144x144" href="/android-icon-144x144.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/android-icon-192x192.png">
    <!-- Microsoft Tiles -->
    <meta name="msapplication-TileColor" content="#0d6efd">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <meta name="msapplication-square70x70logo" content="/ms-icon-70x70.png">
    <meta name="msapplication-square150x150logo" content="/ms-icon-150x150.png">
    <meta name="msapplication-square310x310logo" content="/ms-icon-310x310.png">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .sidebar {
            background: #ffffff;
            min-height: 100vh;
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            border-right: 1px solid #e9ecef;
            transition: width 0.3s ease;
            overflow: hidden;
        }

        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar .brand {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            background: #ffffff;
        }

        .sidebar.collapsed .brand {
            justify-content: center;
        }

        .sidebar .brand img {
            width: 32px;
            height: 32px;
            margin-right: 12px;
        }

        .sidebar.collapsed .brand img {
            margin-right: 0;
        }

        .sidebar .brand span {
            font-size: 20px;
            font-weight: 600;
            color: #17a2b8;
        }

        .sidebar.collapsed .brand span {
            display: none;
        }

        .sidebar-nav {
            padding: 0;
        }

        .nav-link {
            color: #6c757d !important;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-size: 14px;
            border-bottom: 1px solid #f8f9fa;
            transition: all 0.2s ease;
        }

        .sidebar.collapsed .nav-link span {
            display: none;
        }

        .nav-link:hover {
            background-color: #f8f9fa;
            color: #495057 !important;
        }

        .nav-link.active {
            background-color: #17a2b8;
            color: white !important;
        }

        .nav-link i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 18px;
        }

        .top-navbar {
            background: #17a2b8;
            padding: 12px 30px;
            margin-left: 250px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: margin-left 0.3s ease;
        }

        .top-navbar.collapsed {
            margin-left: 60px;
        }

        .top-navbar h4 {
            color: white;
            margin: 0;
            font-weight: 600;
            font-size: 18px;
        }

        .logout-btn {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: calc(100vh - 54px);
            background: #e9ecef;
            transition: margin-left 0.3s ease;
        }

        .main-content.collapsed {
            margin-left: 60px;
        }

        .collapse-btn {
            background: transparent;
            border: none;
            color: #6c757d;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            font-size: 14px;
        }

        .collapse-btn:hover {
            color: #495057;
        }

        .users-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .search-box {
            position: relative;
            max-width: 400px;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 2;
        }

        .search-input {
            padding-left: 2.5rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .search-input:focus {
            border-color: #17a2b8;
            box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background: #f8f9fa;
        }

        .table th {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 1rem;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem;
            font-size: 0.95rem;
        }

        /* Avatar Styles */
        .avatar-wrapper {
            position: relative;
            width: 40px;
            height: 40px;
        }

        .user-avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
        }

        .user-avatar-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #17a2b8;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 3rem;
        }

        @media (max-width:768px) {
            .sidebar {
                width: 60px
            }

            .sidebar .brand span,
            .sidebar .nav-link span {
                display: none
            }

            .main-content,
            .top-navbar {
                margin-left: 60px
            }
        }

        /* Fix: ensure modal appears above backdrop even with custom stacking contexts */
        .modal {
            z-index: 2000;
        }

        .modal-backdrop {
            z-index: 1990;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <img src="../images/logo.png" alt="ChemEase Logo">
            <span>ChemEase</span>
            <button class="collapse-btn ms-auto">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a></div>
            <div class="nav-item"><a href="Users.php" class="nav-link active"><i class="fas fa-users"></i><span>Users</span></a></div>
            <?php if (!$isSuperAdmin): ?>
                <div class="nav-item"><a href="Learning_Material.php" class="nav-link"><i class="fas fa-book"></i><span>Learning Materials</span></a></div>
                <div class="nav-item"><a href="Practice_Exams.php" class="nav-link"><i class="fas fa-clipboard-list"></i><span>Practice Exams</span></a></div>
            <?php endif; ?>

            <?php if ($isSuperAdmin): ?>
                <div class="nav-item"><a href="Discussion_Forums.php" class="nav-link"><i class="fas fa-comments"></i><span>Discussion Forums</span></a></div>
                <div class="nav-item"><a href="Generate_Reports.php" class="nav-link"><i class="fas fa-file-alt"></i><span>Generate Reports</span></a></div>
            <?php endif; ?>
        </nav>
    </div>
    <!-- Top Navbar -->
    <div class="top-navbar">
        <?php
        if ($isAdmin) {
            echo "<h4>ADMIN PANEL</h4>";
        } elseif ($isSuperAdmin) {
            echo "<h4>SUPER ADMIN PANEL</h4>";
        } else {
            echo "<h4>ADMIN PANEL</h4>";
        }
        ?>
        <div class="navbar-actions">
            <a href="https://chemease.site/" class="logout-btn"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <div class="users-container">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">User Management</h1>
                        <p class="page-subtitle">View and manage all registered users</p>
                    </div>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control search-input" id="searchInput" placeholder="Search users...">
                    </div>
                </div>
            </div>
            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                <p class="mt-3 text-muted">Loading users...</p>
            </div>
            <div class="table-container" id="usersTableContainer" style="display:none;">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody"></tbody>
                </table>
            </div>
            <nav aria-label="Page navigation" id="paginationNav" style="display:none;">
                <ul class="pagination justify-content-center"></ul>
            </nav>
        </div>
    </div>
    <!-- Confirm Delete Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">Are you sure?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" id="alertModalHeader">
                    <h5 class="modal-title" id="alertModalTitle">Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="alertModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>

        <!-- User Details Modal -->
        <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="userDetailsTitle"><i class="fas fa-user me-2"></i>User Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div id="userDetailsLoading" class="text-center py-5" style="display:none;">
                            <div class="spinner-border" role="status" aria-hidden="true"></div>
                            <div class="mt-3 text-muted">Loading user information...</div>
                        </div>

                        <div id="userDetailsContent" style="display:none;">
                            <div class="row g-3">
                                <div class="col-12 col-lg-5">
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light fw-semibold">
                                            <i class="fas fa-id-card me-2"></i>Profile Information
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm mb-0">
                                                <tbody id="userInfoTable"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-7">
                                    <div class="card shadow-sm mb-3">
                                        <div class="card-header bg-light fw-semibold">
                                            <i class="fas fa-chart-bar me-2"></i>Exam Attempts Summary
                                        </div>
                                        <div class="card-body">
                                            <div id="examSummary" class="small text-muted">No data</div>
                                            <div class="table-responsive mt-3">
                                                <table class="table table-sm table-striped align-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Exam</th>
                                                            <th>Score</th>
                                                            <th>Total Correct</th>
                                                            <th>Total Answered</th>
                                                            <th>Attempted At</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="examAttemptsBody">
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted py-3">No attempts found.</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card shadow-sm">
                                        <div class="card-header bg-light fw-semibold">
                                            <i class="fas fa-tasks me-2"></i>User Progress Summary
                                        </div>
                                        <div class="card-body">
                                            <div id="progressSummary" class="small text-muted">No data</div>
                                            <div class="table-responsive mt-3">
                                                <table class="table table-sm table-striped align-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Item</th>
                                                            <th>Progress</th>
                                                            <th>Status</th>
                                                            <th>Updated At</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="progressBody">
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted py-3">No progress records found.</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- /row -->
                        </div> <!-- /content -->
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentPage = 1;
            const limit = 10;
            let searchTerm = '';
            let userToDelete = null;
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            const alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
            const userDetailsModal = new bootstrap.Modal(document.getElementById('userDetailsModal'));

            // Ensure modals are direct children of <body> (prevents clipping / stacking issues)
            ['confirmModal', 'alertModal', 'userDetailsModal'].forEach((id) => {
                const el = document.getElementById(id);
                if (el && el.parentElement !== document.body) document.body.appendChild(el);
            });


            function showAlert(message, success = true) {
                const header = document.getElementById('alertModalHeader');
                const title = document.getElementById('alertModalTitle');
                const body = document.getElementById('alertModalBody');
                header.className = success ? 'modal-header bg-success text-white' : 'modal-header bg-danger text-white';
                title.innerHTML = success ? '<i class="fas fa-check-circle me-2"></i>Success' : '<i class="fas fa-exclamation-circle me-2"></i>Error';
                body.textContent = message;
                alertModal.show();
            }


            function escapeHtml(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function formatDate(value) {
                if (!value) return '';
                const d = new Date(value);
                if (isNaN(d.getTime())) return escapeHtml(value);
                return d.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            function setUserInfoTable(user) {
                const infoBody = document.getElementById('userInfoTable');
                infoBody.innerHTML = '';

                const rows = [
                    ['User ID', user.u_uid ?? user.id],
                    ['First Name', user.first_name],
                    ['Last Name', user.last_name],
                    ['Full Name', user.full_name],
                    ['Email', user.email],
                    ['Mobile', user.mobile || user.phone],
                    ['Address', user.address],
                    ['Birthday', user.birthday || user.birthdate || user.date_of_birth],
                    ['Gender', user.gender],
                    ['Joined', user.created_at],
                    ['Updated', user.updated_at],
                ];

                rows.forEach(([label, value]) => {
                    if (value === undefined) return; // column may not exist
                    const display = (label === 'Joined' || label === 'Updated') ?
                        formatDate(value) :
                        escapeHtml(value || '');
                    infoBody.innerHTML += `
                        <tr>
                            <td class="text-muted" style="width:38%;">${escapeHtml(label)}</td>
                            <td class="fw-semibold">${display || '<span class="text-muted">—</span>'}</td>
                        </tr>
                    `;
                });
            }

            function setExamAttempts(data) {
                const summaryEl = document.getElementById('examSummary');
                const body = document.getElementById('examAttemptsBody');

                const s = data.exam_attempts_summary;
                if (!s || !s.total_attempts || Number(s.total_attempts) === 0) {
                    summaryEl.textContent = 'No exam attempts found for this user.';
                } else {
                    const parts = [];
                    parts.push(`Total attempts: ${s.total_attempts}`);
                    if (s.avg_score !== undefined && s.avg_score !== null) parts.push(`Avg score: ${Number(s.avg_score).toFixed(2)}`);
                    if (s.best_score !== undefined && s.best_score !== null) parts.push(`Best score: ${s.best_score}`);
                    if (s.sum_total_correct !== undefined && s.sum_total_correct !== null) parts.push(`Total correct: ${s.sum_total_correct}`);
                    if (s.sum_total_answered !== undefined && s.sum_total_answered !== null) parts.push(`Total answered: ${s.sum_total_answered}`);
                    summaryEl.textContent = parts.join(' • ');
                }

                body.innerHTML = '';
                const attempts = data.exam_attempts || [];
                if (attempts.length === 0) {
                    body.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">No attempts found.</td></tr>`;
                    return;
                }

                attempts.forEach(a => {
                    body.innerHTML += `
                        <tr>
                            <td>${escapeHtml(a.exam_id ?? '—')}</td>
                            <td>${escapeHtml(a.score ?? '—')}</td>
                            <td>${escapeHtml(a.total_correct ?? '—')}</td>
                            <td>${escapeHtml(a.total_answered ?? '—')}</td>
                            <td>${formatDate(a.attempted_at) || '<span class="text-muted">—</span>'}</td>
                        </tr>
                    `;
                });
            }

            function setProgress(data) {
                const summaryEl = document.getElementById('progressSummary');
                const body = document.getElementById('progressBody');

                const s = data.progress_summary;
                if (!s || !s.total_records || Number(s.total_records) === 0) {
                    summaryEl.textContent = 'No progress records found for this user.';
                } else {
                    const parts = [];
                    parts.push(`Total records: ${s.total_records}`);
                    if (s.completed_count !== undefined && s.completed_count !== null) parts.push(`Completed: ${s.completed_count}`);
                    if (s.avg_progress !== undefined && s.avg_progress !== null) parts.push(`Avg progress: ${Number(s.avg_progress).toFixed(2)}`);
                    if (s.last_updated) parts.push(`Last updated: ${formatDate(s.last_updated)}`);
                    summaryEl.textContent = parts.join(' • ');
                }

                body.innerHTML = '';
                const rows = data.progress_rows || [];
                if (rows.length === 0) {
                    body.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">No progress records found.</td></tr>`;
                    return;
                }

                rows.forEach(r => {
                    const status = r.status ?? (r.is_completed !== undefined ? (Number(r.is_completed) === 1 ? 'completed' : 'in-progress') : '—');
                    body.innerHTML += `
                        <tr>
                            <td>${escapeHtml(r.item ?? '—')}</td>
                            <td>${escapeHtml(r.progress ?? '—')}</td>
                            <td>${escapeHtml(status)}</td>
                            <td>${formatDate(r.updated_at) || '<span class="text-muted">—</span>'}</td>
                        </tr>
                    `;
                });
            }

            function openUserDetails(userId) {
                document.getElementById('userDetailsLoading').style.display = 'block';
                document.getElementById('userDetailsContent').style.display = 'none';
                document.getElementById('userDetailsTitle').innerHTML = `<i class="fas fa-user me-2"></i>User Details #${escapeHtml(userId)}`;

                userDetailsModal.show();

                fetch(`../partial/get_users.php?user_id=${encodeURIComponent(userId)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            showAlert(data.error, false);
                            userDetailsModal.hide();
                            return;
                        }
                        setUserInfoTable(data.user || {});
                        setExamAttempts(data);
                        setProgress(data);

                        document.getElementById('userDetailsLoading').style.display = 'none';
                        document.getElementById('userDetailsContent').style.display = 'block';
                    })
                    .catch(() => {
                        showAlert('Network error while loading user details.', false);
                        userDetailsModal.hide();
                    });
            }

            function generateAvatar(fullName, profileImage) {
                const initials = fullName.split(' ').map(n => n[0]?.toUpperCase() || '').join('').substring(0, 2) || 'U';
                if (profileImage && profileImage.trim() !== '') {
                    return `<img src="../${profileImage}?t=${Date.now()}" class="user-avatar-img" alt="${fullName}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">`;
                }
                return `<div class="user-avatar-initials">${initials}</div>`;
            }

            function loadUsers(page = 1, search = '') {
                document.getElementById('loadingSpinner').style.display = 'block';
                document.getElementById('usersTableContainer').style.display = 'none';
                document.getElementById('paginationNav').style.display = 'none';

                fetch(`../partial/get_users.php?page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`)
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('loadingSpinner').style.display = 'none';
                        document.getElementById('usersTableContainer').style.display = 'block';

                        const tbody = document.getElementById('usersTableBody');
                        tbody.innerHTML = '';

                        if (data.users.length === 0) {
                            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No users found.</td></tr>`;
                            return;
                        }

                        data.users.forEach(user => {
                            const joined = new Date(user.created_at).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            });
                            tbody.innerHTML += `
                            <tr class="user-row" data-id="${user.id}" style="cursor:pointer;">
                                <td><strong>${user.u_uid ? user.u_uid : user.id}</strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-wrapper me-3">
                                            ${generateAvatar(user.full_name, user.profile_image)}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">${user.full_name}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><a href="mailto:${user.email}" class="text-decoration-none">${user.email}</a></td>
                                <td>${joined}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger delete-user" data-id="${user.id}" data-name="${user.full_name}">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>`;
                        });

                        const totalPages = Math.ceil(data.total / limit);
                        if (totalPages > 1) {
                            document.getElementById('paginationNav').style.display = 'flex';
                            renderPagination(page, totalPages);
                        }
                    })
                    .catch(() => {
                        document.getElementById('loadingSpinner').innerHTML = `<p class="text-danger">Error loading users.</p>`;
                    });
            }

            function renderPagination(current, total) {
                const ul = document.querySelector('#paginationNav ul');
                ul.innerHTML = '';
                ul.innerHTML += `<li class="page-item ${current===1?'disabled':''}"><a class="page-link" href="#">Previous</a></li>`;
                for (let i = 1; i <= total; i++) {
                    ul.innerHTML += `<li class="page-item ${i===current?'active':''}"><a class="page-link" href="#">${i}</a></li>`;
                }
                ul.innerHTML += `<li class="page-item ${current===total?'disabled':''}"><a class="page-link" href="#">Next</a></li>`;
            }

            // Search
            document.getElementById('searchInput')?.addEventListener('input', function() {
                searchTerm = this.value.trim();
                currentPage = 1;
                loadUsers(currentPage, searchTerm);
            });

            // Pagination clicks
            document.getElementById('paginationNav')?.addEventListener('click', function(e) {
                if (e.target.tagName === 'A' && !e.target.closest('.disabled')) {
                    e.preventDefault();
                    const text = e.target.textContent.trim();
                    if (text === 'Previous') currentPage--;
                    else if (text === 'Next') currentPage++;
                    else currentPage = parseInt(text);
                    loadUsers(currentPage, searchTerm);
                }
            });

            // Delete button click → show confirm modal
            document.getElementById('usersTableBody')?.addEventListener('click', function(e) {
                const btn = e.target.closest('.delete-user');
                if (!btn) return;
                userToDelete = btn.dataset.id;
                document.getElementById('confirmMessage').textContent =
                    `Are you sure you want to delete "${btn.dataset.name}" (ID: ${userToDelete})? This cannot be undone.`;
                confirmModal.show();
            });

            // Confirm delete
            document.getElementById('confirmDeleteBtn')?.addEventListener('click', function() {
                if (!userToDelete) return;
                fetch('../partial/delete_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `user_id=${userToDelete}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        confirmModal.hide();
                        if (data.success) {
                            showAlert('User deleted successfully.', true);
                            loadUsers(currentPage, searchTerm);
                        } else {
                            showAlert(data.error || 'Failed to delete user', false);
                        }
                    })
                    .catch(() => {
                        confirmModal.hide();
                        showAlert('Network error.', false);
                    });
            });

            // Sidebar collapse
            document.querySelector('.collapse-btn')?.addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('collapsed');
                document.querySelector('.top-navbar').classList.toggle('collapsed');
                document.querySelector('.main-content').classList.toggle('collapsed');
                this.querySelector('i').classList.toggle('fa-chevron-left');
                this.querySelector('i').classList.toggle('fa-chevron-right');
            });


            // Row click -> open user details modal (ignore clicks on buttons/links inside the row)
            document.getElementById('usersTableBody').addEventListener('click', function(e) {
                const row = e.target.closest('tr.user-row');
                if (!row) return;

                if (e.target.closest('button') || e.target.closest('a')) return;

                const userId = row.getAttribute('data-id');
                if (userId) openUserDetails(userId);
            });
            // Initial load
            loadUsers(1);
        });
    </script>
</body>

</html>