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
    <div class="top-navbar">
        <h4>ADMIN PANEL</h4>
        <div class="navbar-actions">
 
            <a href="#" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                LOGOUT
            </a>
        </div>
    </div>

    <!-- Main Content Area -->

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>