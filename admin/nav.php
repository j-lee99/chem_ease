<?php
    session_start();
    require_once '../partial/db_conn.php';
    
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../signin.php');
        exit;
    }
    
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChemEase Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: all 0.3s ease;
        }
        
        .sidebar {
            background: #ffffff;
            min-height: 100vh;
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
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
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed .brand {
            justify-content: center;
        }
        
        .sidebar .brand img {
            width: 32px;
            height: 32px;
            margin-right: 12px;
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed .brand img {
            margin-right: 0;
        }
        
        .sidebar .brand span {
            font-size: 20px;
            font-weight: 600;
            color: #17a2b8;
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed .brand span {
            display: none;
        }
        
        .sidebar-nav {
            padding: 0;
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
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            transition: all 0.2s ease;
        }
        
        .profile-trigger:hover {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.5);
        }
        
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-initials {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            color: #17a2b8;
            font-weight: 700;
            font-size: 14px;
        }
        
        .dropdown-menu {
            min-width: 220px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            padding: 0.5rem 0;
        }
        
        .dropdown-item {
            padding: 10px 16px;
            font-size: 14px;
        }
        
        .dropdown-item i {
            width: 18px;
        }
        
        .nav-item {
            margin: 0;
            position: relative;
        }
        
        .nav-link {
            color: #6c757d !important;
            padding: 15px 20px;
            border: none;
            border-radius: 0;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-size: 14px;
            border-bottom: 1px solid #f8f9fa;
            position: relative;
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
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 18px;
        }
        
        .sidebar.collapsed .nav-link:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 60px;
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
        }
        
        .top-navbar {
            background: #17a2b8;
            padding: 12px 30px;
            margin-left: 250px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        
        .top-navbar .navbar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .top-navbar .notification-btn {
            background: transparent;
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 16px;
        }
        
        .top-navbar .notification-btn:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .logout-btn {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 12px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 0;
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
            transition: all 0.2s ease;
            margin-left: auto;
            font-size: 14px;
        }
        
        .collapse-btn:hover {
            color: #495057;
        }

        /* Match the exact icons from the image */
        .nav-link[data-section="dashboard"] i:before { content: "\f015"; }
        .nav-link[data-section="users"] i:before { content: "\f0c0"; }
        .nav-link[data-section="learning"] i:before { content: "\f02d"; }
        .nav-link[data-section="exams"] i:before { content: "\f0ea"; }
        .nav-link[data-section="forums"] i:before { content: "\f086"; }
        .nav-link[data-section="analytics"] i:before { content: "\f080"; }
        .nav-link[data-section="feedback"] i:before { content: "\f4ad"; }
        .nav-link[data-section="settings"] i:before { content: "\f013"; }


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
            <div class="nav-item">
                <a href="index.php" class="nav-link" data-section="dashboard" data-tooltip="Dashboard">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Users.php" class="nav-link active" data-section="users" data-tooltip="Users">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Learning_Material.php" class="nav-link" data-section="learning" data-tooltip="Learning Materials">
                    <i class="fas fa-book"></i>
                    <span>Learning Materials</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Practice_Exams.php" class="nav-link" data-section="exams" data-tooltip="Practice Exams">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Practice Exams</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Discussion_Forums.php" class="nav-link" data-section="forums" data-tooltip="Discussion Forums">
                    <i class="fas fa-comments"></i>
                    <span>Discussion Forums</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Analytics.php" class="nav-link" data-section="analytics" data-tooltip="Analytics">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Feedback.php" class="nav-link" data-section="feedback" data-tooltip="Feedback">
                    <i class="fas fa-wine-bottle"></i>
                    <span>Feedback</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Settings.php" class="nav-link" data-section="settings" data-tooltip="Settings">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Top Navigation -->
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
                    <strong><?php echo htmlspecialchars($full_name); ?></strong>
                </li>
    
                <li><hr class="dropdown-divider"></li>
    
                <li>
                    <a class="dropdown-item" href="Profile_Settings.php">
                        <i class="fas fa-user-cog me-2"></i> Profile Settings
                    </a>
                </li>
    
                <li>
                    <a class="dropdown-item" href="Settings.php">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </li>
    
                <li><hr class="dropdown-divider"></li>
    
                <li>
                    <a class="dropdown-item text-danger" href="../partial/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content Area -->

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>