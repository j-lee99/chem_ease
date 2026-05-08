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
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --purple: #6f42c1;
            --orange: #fd7e14;
        }
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .section-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .section-card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #eef1f4;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fcfcfd;
        }
        
        .section-card-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-card-subtitle {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .section-card-body {
            padding: 0;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.35rem 0.7rem;
            font-size: 0.78rem;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
        }
        
        .status-inactive {
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
        }
        
        .switch-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
        }
        
        .form-check.form-switch {
            margin-bottom: 0;
        }
        
        .table-section-empty {
            padding: 2rem;
            text-align: center;
            color: #6c757d;
        }
        
        .count-pill {
            background: #e9f7fa;
            color: #0f7f91;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
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
            display: block;
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

        .modal {
            z-index: 2000;
        }

        .modal-backdrop {
            z-index: 1990;
        }
    </style>
</head>

<body>
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
            <?php if ($isSuperAdmin || $isAdmin): ?>
                <div class="nav-item">
                    <a href="Discussion_Forums.php" class="nav-link">
                        <i class="fas fa-comments"></i>
                        <span>Discussion Forums</span>
                    </a>
                </div>
            <?php endif; ?>
            <?php if ($isSuperAdmin): ?>
                <div class="nav-item"><a href="Generate_Reports.php" class="nav-link"><i class="fas fa-file-alt"></i><span>Reports & Analytics</span></a></div>
            <?php endif; ?>
        </nav>
    </div>

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
        <div class="users-container">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h1 class="page-title">User Management</h1>
                        <p class="page-subtitle">View and manage all registered users</p>
                    </div>
            
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control search-input" id="searchInput" placeholder="Search users...">
                        </div>
            
                        <?php if ($isSuperAdmin): ?>
                            <button type="button" class="btn btn-primary" id="openAddAdminModalBtn">
                                <i class="fas fa-user-plus me-2"></i>Add Admin
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                <p class="mt-3 text-muted">Loading users...</p>
            </div>

            <div id="usersTableContainer" style="display:none;">
                <div class="section-card">
                    <div class="section-card-header">
                        <div>
                            <h2 class="section-card-title">
                                <i class="fas fa-user-graduate text-primary"></i>
                                User Accounts
                            </h2>
                            <div class="section-card-subtitle">Registered student and regular user accounts</div>
                        </div>
                    </div>
                    <div class="section-card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
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
                    </div>
                </div>
            
                <?php if ($isSuperAdmin): ?>
                    <div class="section-card">
                        <div class="section-card-header">
                            <div>
                                <h2 class="section-card-title">
                                    <i class="fas fa-user-shield text-info"></i>
                                    Admin Accounts
                                </h2>
                                <div class="section-card-subtitle">Administrative accounts with access control</div>
                            </div>
                            <span class="count-pill" id="adminAccountsCount">0</span>
                        </div>
                        <div class="section-card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Admin</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Access</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="adminTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <nav aria-label="Page navigation" id="paginationNav" style="display:none;">
                <ul class="pagination justify-content-center"></ul>
            </nav>
        </div>
    </div>

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
    </div>

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
                            <div class="col-12 col-lg-5" id="userProfileColumn">
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

                            <div class="col-12 col-lg-7" id="userExtraSections">
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
                                                        <th>Total Questions</th>
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
                                                        <th>Category</th>
                                                        <th>Title</th>
                                                        <th>Progress</th>
                                                        <th>Status</th>
                                                        <th>Updated At</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="progressBody">
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-3">No progress records found.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-shield me-2"></i>Add Admin
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form id="addAdminForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="fullName" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mobile</label>
                                <input type="text" class="form-control" name="mobile" placeholder="09XXXXXXXXX" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Birthday</label>
                                <input type="date" class="form-control" name="birthday" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2" required></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required minlength="8">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirmPassword" required minlength="8">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" required>
                                    <option value="admin" selected>Admin</option>
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-light border mt-3 mb-0 small">
                            <i class="fas fa-info-circle me-2 text-primary"></i>
                            This form creates an internal admin account. Email verification and terms acceptance are not necessary here.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitAddAdminBtn">
                            <i class="fas fa-save me-2"></i>Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let currentPage = 1;
        const limit = 10;
        let searchTerm = '';
        let userToDelete = null;

        ['confirmModal', 'alertModal', 'userDetailsModal', 'addAdminModal'].forEach((id) => {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });

        const confirmModalEl = document.getElementById('confirmModal');
        const alertModalEl = document.getElementById('alertModal');
        const userDetailsModalEl = document.getElementById('userDetailsModal');
        const addAdminModalEl = document.getElementById('addAdminModal');

        const confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;
        const alertModal = alertModalEl ? new bootstrap.Modal(alertModalEl) : null;
        const userDetailsModal = userDetailsModalEl ? new bootstrap.Modal(userDetailsModalEl) : null;
        const addAdminModal = addAdminModalEl ? new bootstrap.Modal(addAdminModalEl) : null;

        const addAdminForm = document.getElementById('addAdminForm');
        const submitAddAdminBtn = document.getElementById('submitAddAdminBtn');

        function showAlert(message, success = true) {
            const header = document.getElementById('alertModalHeader');
            const title = document.getElementById('alertModalTitle');
            const body = document.getElementById('alertModalBody');

            if (!header || !title || !body || !alertModal) return;

            header.className = success
                ? 'modal-header bg-success text-white'
                : 'modal-header bg-danger text-white';

            title.innerHTML = success
                ? '<i class="fas fa-check-circle me-2"></i>Success'
                : '<i class="fas fa-exclamation-circle me-2"></i>Error';

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

        async function safeJsonResponse(response) {
            const raw = await response.text();

            try {
                return {
                    ok: response.ok,
                    status: response.status,
                    data: JSON.parse(raw)
                };
            } catch (e) {
                throw new Error('Server returned an invalid response. Check create_admin_api.php for warnings, redirects, or HTML output.');
            }
        }

        function setUserInfoTable(user) {
            const infoBody = document.getElementById('userInfoTable');
            if (!infoBody) return;

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
                ['Role', user.role],
                ['Joined', user.created_at],
                ['Updated', user.updated_at],
            ];

            rows.forEach(([label, value]) => {
                if (value === undefined) return;

                const display = (label === 'Joined' || label === 'Updated')
                    ? formatDate(value)
                    : escapeHtml(value || '');

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
            if (!summaryEl || !body) return;

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
                const examLabel = a.exam_title ?? a.title ?? a.exam_name ?? a.exam_id ?? '—';
                const scoreLabel = (a.score !== undefined && a.score !== null) ? a.score : '—';

                body.innerHTML += `
                    <tr>
                        <td>${escapeHtml(examLabel)}</td>
                        <td>${escapeHtml(scoreLabel)}</td>
                        <td>${escapeHtml(a.total_correct ?? '—')}</td>
                        <td>${escapeHtml(a.total_answered ?? '—')}</td>
                        <td>${formatDate(a.attempted_at ?? a.created_at) || '<span class="text-muted">—</span>'}</td>
                    </tr>
                `;
            });
        }

        function setProgress(data) {
            const summaryEl = document.getElementById('progressSummary');
            const body = document.getElementById('progressBody');
            if (!summaryEl || !body) return;

            const s = data.progress_summary;
            if (!s || !s.total_records || Number(s.total_records) === 0) {
                summaryEl.textContent = 'No progress records found for this user.';
            } else {
                const parts = [];
                parts.push(`Total records: ${s.total_records}`);
                if (s.completed_count !== undefined && s.completed_count !== null) parts.push(`Completed: ${s.completed_count}`);
                if (s.avg_progress !== undefined && s.avg_progress !== null) parts.push(`Avg progress: ${Number(s.avg_progress).toFixed(2)}%`);
                if (s.last_updated) parts.push(`Last updated: ${formatDate(s.last_updated)}`);
                summaryEl.textContent = parts.join(' • ');
            }

            body.innerHTML = '';
            const rows = data.progress_rows || [];

            if (rows.length === 0) {
                body.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">No progress records found.</td></tr>`;
                return;
            }

            rows.forEach(r => {
                const numericProgress = Number(r.progress ?? 0);
                const safeProgress = Number.isFinite(numericProgress) ? numericProgress : 0;
                const status = safeProgress >= 100 ? 'Complete' : 'In Progress';

                const materialTitle = r.material_title || '-';
                const materialCategory = r.material_category || '-';

                body.innerHTML += `
                    <tr>
                        <td>${escapeHtml(materialCategory)}</td>
                        <td>${escapeHtml(materialTitle)}</td>
                        <td>${escapeHtml(`${safeProgress}%`)}</td>
                        <td>${escapeHtml(status)}</td>
                        <td>${formatDate(r.updated_at) || '<span class="text-muted">—</span>'}</td>
                    </tr>
                `;
            });
        }

        function isAdminAccount(user) {
            const role = String(user?.role || '').toLowerCase().trim();
            return role === 'admin' || role === 'super_admin';
        }

        function toggleUserDetailsSections(user) {
            const extraSections = document.getElementById('userExtraSections');
            const profileColumn = document.getElementById('userProfileColumn');

            if (!extraSections || !profileColumn) return;

            if (isAdminAccount(user)) {
                extraSections.style.display = 'none';
                profileColumn.className = 'col-12';
            } else {
                extraSections.style.display = '';
                profileColumn.className = 'col-12 col-lg-5';
            }
        }

        function openUserDetails(userId) {
            const loadingEl = document.getElementById('userDetailsLoading');
            const contentEl = document.getElementById('userDetailsContent');
            const titleEl = document.getElementById('userDetailsTitle');

            if (!loadingEl || !contentEl || !titleEl || !userDetailsModal) return;

            loadingEl.style.display = 'block';
            contentEl.style.display = 'none';
            titleEl.innerHTML = `<i class="fas fa-user me-2"></i>User Details #${escapeHtml(userId)}`;

            userDetailsModal.show();

            fetch(`../partial/get_users.php?user_id=${encodeURIComponent(userId)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        showAlert(data.error, false);
                        userDetailsModal.hide();
                        return;
                    }

                    const user = data.user || {};

                    setUserInfoTable(user);
                    toggleUserDetailsSections(user);

                    if (!isAdminAccount(user)) {
                        setExamAttempts(data);
                        setProgress(data);
                    }

                    loadingEl.style.display = 'none';
                    contentEl.style.display = 'block';
                })
                .catch(() => {
                    showAlert('Network error while loading user details.', false);
                    userDetailsModal.hide();
                });
        }

        function generateAvatar(fullName, profileImage) {
            const safeName = fullName || 'User';
            const initials = safeName
                .split(' ')
                .map(n => n[0]?.toUpperCase() || '')
                .join('')
                .substring(0, 2) || 'U';

            if (profileImage && profileImage.trim() !== '') {
                return `
                    <img src="../${profileImage}?t=${Date.now()}" class="user-avatar-img" alt="${escapeHtml(safeName)}"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="user-avatar-initials" style="display:none;">${escapeHtml(initials)}</div>
                `;
            }

            return `<div class="user-avatar-initials">${escapeHtml(initials)}</div>`;
        }
        
        function getRoleLabel(role) {
            if (!role) return 'User';
            return role
                .replaceAll('_', ' ')
                .replace(/\b\w/g, (char) => char.toUpperCase());
        }
        
        function renderUserRow(user) {
            const joined = formatDate(user.created_at) || '—';
            const fullName = user.full_name || 'Unnamed User';
            const email = user.email || '—';
        
            return `
                <tr class="user-row" data-id="${user.id}" style="cursor:pointer;">
                    <td><strong>${escapeHtml(user.u_uid ? user.u_uid : user.id)}</strong></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-wrapper me-3">
                                ${generateAvatar(fullName, user.profile_image)}
                            </div>
                            <div>
                                <div class="fw-semibold">${escapeHtml(fullName)}</div>
                            </div>
                        </div>
                    </td>
                    <td><a href="mailto:${escapeHtml(email)}" class="text-decoration-none">${escapeHtml(email)}</a></td>
                    <td>${joined}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger delete-user" data-id="${user.id}" data-name="${escapeHtml(fullName)}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        }
        
        function renderAdminRow(user) {
            const joined = formatDate(user.created_at) || '—';
            const fullName = user.full_name || 'Unnamed Admin';
            const email = user.email || '—';
            const roleLabel = getRoleLabel(user.role || 'admin');
            const isActive = Number(user.is_active ?? 1) === 1;
        
            return `
                <tr class="user-row" data-id="${user.id}" style="cursor:pointer;">
                    <td><strong>${escapeHtml(user.u_uid ? user.u_uid : user.id)}</strong></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-wrapper me-3">
                                ${generateAvatar(fullName, user.profile_image)}
                            </div>
                            <div>
                                <div class="fw-semibold">${escapeHtml(fullName)}</div>
                                <div class="small text-muted">Joined ${joined}</div>
                            </div>
                        </div>
                    </td>
                    <td><a href="mailto:${escapeHtml(email)}" class="text-decoration-none">${escapeHtml(email)}</a></td>
                    <td>
                        <span class="badge text-bg-light border">${escapeHtml(roleLabel)}</span>
                    </td>
                    <td>
                        <span class="status-badge ${isActive ? 'status-active' : 'status-inactive'}">
                            <i class="fas ${isActive ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>
                            ${isActive ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <div class="switch-wrapper" onclick="event.stopPropagation()">
                            <div class="form-check form-switch">
                                <input 
                                    class="form-check-input admin-status-toggle" 
                                    type="checkbox" 
                                    role="switch"
                                    data-id="${user.id}"
                                    ${isActive ? 'checked' : ''}
                                >
                            </div>
                            <span class="small text-muted">${isActive ? 'Enabled' : 'Disabled'}</span>
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger delete-user" data-id="${user.id}" data-name="${escapeHtml(fullName)}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        }
        
        function loadUsers(page = 1, search = '') {
            const loadingSpinner = document.getElementById('loadingSpinner');
            const tableContainer = document.getElementById('usersTableContainer');
            const paginationNav = document.getElementById('paginationNav');
            const usersTbody = document.getElementById('usersTableBody');
        
            if (!loadingSpinner || !tableContainer || !paginationNav || !usersTbody) return;
        
            loadingSpinner.style.display = 'block';
            loadingSpinner.innerHTML = `
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                <p class="mt-3 text-muted">Loading accounts...</p>
            `;
            tableContainer.style.display = 'none';
            paginationNav.style.display = 'none';
        
            fetch(`../partial/get_users.php?scope=users&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`)
                .then(r => r.json())
                .then(data => {
                    loadingSpinner.style.display = 'none';
                    tableContainer.style.display = 'block';
        
                    usersTbody.innerHTML = '';
        
                    const users = Array.isArray(data.users) ? data.users : [];
        
                    if (users.length === 0) {
                        usersTbody.innerHTML = `<tr><td colspan="5" class="table-section-empty">No user accounts found.</td></tr>`;
                    } else {
                        users.forEach(user => {
                            usersTbody.innerHTML += renderUserRow(user);
                        });
                    }
        
                    const totalPages = Math.ceil((data.total || 0) / limit);
                    if (totalPages > 1) {
                        paginationNav.style.display = 'flex';
                        renderPagination(page, totalPages);
                    }
                })
                .catch((err) => {
                    console.error('loadUsers error:', err);
                    loadingSpinner.innerHTML = `<p class="text-danger">Error loading accounts.</p>`;
                });
        }
        
        function loadAdmins(search = '') {
            const adminTbody = document.getElementById('adminTableBody');
            const adminAccountsCount = document.getElementById('adminAccountsCount');
        
            if (!adminTbody) return;
        
            fetch(`../partial/get_users.php?scope=admins&search=${encodeURIComponent(search)}`)
                .then(r => r.json())
                .then(data => {
                    adminTbody.innerHTML = '';
        
                    const admins = Array.isArray(data.users) ? data.users : [];
        
                    if (adminAccountsCount) {
                        adminAccountsCount.textContent = admins.length;
                    }
        
                    if (admins.length === 0) {
                        adminTbody.innerHTML = `<tr><td colspan="7" class="table-section-empty">No admin accounts found.</td></tr>`;
                        return;
                    }
        
                    admins.forEach(user => {
                        adminTbody.innerHTML += renderAdminRow(user);
                    });
                })
                .catch((err) => {
                    console.error('loadAdmins error:', err);
                    adminTbody.innerHTML = `<tr><td colspan="7" class="table-section-empty text-danger">Error loading admin accounts.</td></tr>`;
                });
        }
        
        function renderPagination(current, total) {
            const ul = document.querySelector('#paginationNav ul');
            if (!ul) return;

            ul.innerHTML = '';
            ul.innerHTML += `<li class="page-item ${current === 1 ? 'disabled' : ''}"><a class="page-link" href="#">Previous</a></li>`;

            for (let i = 1; i <= total; i++) {
                ul.innerHTML += `<li class="page-item ${i === current ? 'active' : ''}"><a class="page-link" href="#">${i}</a></li>`;
            }

            ul.innerHTML += `<li class="page-item ${current === total ? 'disabled' : ''}"><a class="page-link" href="#">Next</a></li>`;
        }

        document.getElementById('openAddAdminModalBtn')?.addEventListener('click', function () {
            addAdminForm?.reset();
            addAdminModal?.show();
        });

        addAdminModalEl?.addEventListener('hidden.bs.modal', function () {
            addAdminForm?.reset();
            if (submitAddAdminBtn) {
                submitAddAdminBtn.disabled = false;
                submitAddAdminBtn.innerHTML = `<i class="fas fa-save me-2"></i>Create Account`;
            }
        });

        addAdminForm?.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(addAdminForm);

            if (submitAddAdminBtn) {
                submitAddAdminBtn.disabled = true;
                submitAddAdminBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Creating...`;
            }

            try {
                const response = await fetch('../partial/create_admin_api.php', {
                    method: 'POST',
                    body: formData
                });

                const { ok, data } = await safeJsonResponse(response);

                if (!ok || data.status === 'error' || data.success === false) {
                    throw new Error(data.message || data.error || 'Failed to create account.');
                }

                addAdminModal?.hide();
                showAlert(data.message || 'Account created successfully.', true);
                loadUsers(currentPage, searchTerm);
                
                if (isSuperAdmin) {
                    loadAdmins(searchTerm);
                }
            } catch (err) {
                showAlert(err.message || 'Network error while creating account.', false);
            } finally {
                if (submitAddAdminBtn) {
                    submitAddAdminBtn.disabled = false;
                    submitAddAdminBtn.innerHTML = `<i class="fas fa-save me-2"></i>Create Account`;
                }
            }
        });

        document.getElementById('searchInput')?.addEventListener('input', function () {
            searchTerm = this.value.trim();
            currentPage = 1;
            loadUsers(currentPage, searchTerm);
        
            if (isSuperAdmin) {
                loadAdmins(searchTerm);
            }
        });

        document.getElementById('paginationNav')?.addEventListener('click', function (e) {
            if (e.target.tagName === 'A' && !e.target.closest('.disabled')) {
                e.preventDefault();
                const text = e.target.textContent.trim();

                if (text === 'Previous') currentPage--;
                else if (text === 'Next') currentPage++;
                else currentPage = parseInt(text);

                loadUsers(currentPage, searchTerm);
                if (isSuperAdmin) {
                    loadAdmins(searchTerm);
                }
            }
        });

        function handleDeleteClick(e) {
            const btn = e.target.closest('.delete-user');
            if (!btn) return;
        
            userToDelete = btn.dataset.id;
            document.getElementById('confirmMessage').textContent =
                `Are you sure you want to delete "${btn.dataset.name}" (ID: ${userToDelete})? This cannot be undone.`;
        
            confirmModal?.show();
        }
        
        document.getElementById('usersTableBody')?.addEventListener('click', handleDeleteClick);
        document.getElementById('adminTableBody')?.addEventListener('click', handleDeleteClick);

        document.getElementById('confirmDeleteBtn')?.addEventListener('click', function () {
            if (!userToDelete) return;

            fetch('../partial/delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `user_id=${encodeURIComponent(userToDelete)}`
            })
            .then(r => r.json())
            .then(data => {
                confirmModal?.hide();

                if (data.success) {
                    showAlert('User deleted successfully.', true);
                    loadUsers(currentPage, searchTerm);
                    if (isSuperAdmin) {
                        loadAdmins(searchTerm);
                    }
                } else {
                    showAlert(data.error || 'Failed to delete user', false);
                }
            })
            .catch(() => {
                confirmModal?.hide();
                showAlert('Network error.', false);
            });
        });
        
        document.addEventListener('change', async function (e) {
            const toggle = e.target.closest('.admin-status-toggle');
            if (!toggle) return;
        
            const adminId = toggle.dataset.id;
            const newStatus = toggle.checked ? 1 : 0;
            const previousState = !toggle.checked;
        
            toggle.disabled = true;
        
            try {
                const response = await fetch('../partial/toggle_admin_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `user_id=${encodeURIComponent(adminId)}&is_active=${encodeURIComponent(newStatus)}`
                });
        
                const result = await response.json();
        
                if (!response.ok || result.status === 'error' || result.success === false) {
                    throw new Error(result.message || result.error || 'Failed to update admin status.');
                }
        
                showAlert(result.message || 'Admin account status updated successfully.', true);
                loadUsers(currentPage, searchTerm);
                if (isSuperAdmin) {
                    loadAdmins(searchTerm);
                }
            } catch (err) {
                toggle.checked = previousState;
                showAlert(err.message || 'Failed to update admin status.', false);
            } finally {
                toggle.disabled = false;
            }
        });

        document.querySelector('.collapse-btn')?.addEventListener('click', function () {
            document.querySelector('.sidebar')?.classList.toggle('collapsed');
            document.querySelector('.top-navbar')?.classList.toggle('collapsed');
            document.querySelector('.main-content')?.classList.toggle('collapsed');
            this.querySelector('i')?.classList.toggle('fa-chevron-left');
            this.querySelector('i')?.classList.toggle('fa-chevron-right');
        });

        document.getElementById('usersTableBody')?.addEventListener('click', function (e) {
            const row = e.target.closest('tr.user-row');
            if (!row) return;

            if (e.target.closest('button') || e.target.closest('a')) return;

            const userId = row.getAttribute('data-id');
            if (userId) openUserDetails(userId);
        });
        
        document.getElementById('adminTableBody')?.addEventListener('click', function (e) {
            const row = e.target.closest('tr.user-row');
            if (!row) return;
        
            if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input') || e.target.closest('.form-check')) return;
        
            const userId = row.getAttribute('data-id');
            if (userId) openUserDetails(userId);
        });

        loadUsers(1, searchTerm);
        const isSuperAdmin = <?= json_encode($isSuperAdmin) ?>;

        if (isSuperAdmin) {
            loadAdmins(searchTerm);
        }
    });
</script>
</body>

</html>