<?php
session_start();
require_once '../partial/db_conn.php';

$role = $_SESSION['role'] ?? '';
$isAdmin = ($role === 'admin');
$isSuperAdmin = ($role === 'super_admin');

// Allow only admin & superAdmin into this panel
if (!isset($_SESSION['user_id']) || !in_array($role, ['admin', 'super_admin'], true)) {
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
            echo "ChemEase Super Admin Panel";
        } elseif ($isAdmin) {
            echo "ChemEase Admin Panel";
        } else {
            echo "ChemEase";
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
            z-index: 1000;
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
            color: var(--primary);
        }

        .sidebar.collapsed .brand span {
            display: none;
        }

        .sidebar-nav {
            padding: 0;
        }

        .nav-item {
            margin: 0;
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
            background-color: var(--primary);
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
            background: var(--primary);
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

        .top-navbar .navbar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
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

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            margin-bottom: 1.5rem;
        }

        .dashboard-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        .dashboard-header p {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-card.users .stat-icon {
            background: var(--primary);
        }

        .stat-card.quizzes .stat-icon {
            background: var(--success);
        }

        .stat-card.materials .stat-icon {
            background: var(--warning);
        }

        .stat-card.forums .stat-icon {
            background: var(--purple);
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0.5rem 0 0;
        }

        .stat-subtitle {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }

        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 992px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
        }

        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .chart-period {
            font-size: 0.8rem;
            color: #6c757d;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        .chart-container,
        .donut-chart-container {
            position: relative;
            height: 300px;
        }

        .quick-access {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .quick-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
        }

        .quick-access-card {
            background: white;
            padding: 1.2rem;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .quick-access-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            color: var(--primary);
        }

        .quick-access-card .icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 0.8rem;
            background: #f8f9fa;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--primary);
        }

        .quick-access-card .label {
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
            }

            .sidebar .brand span,
            .sidebar .nav-link span {
                display: none;
            }

            .main-content,
            .top-navbar {
                margin-left: 60px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* --- Leaderboard --- */
        .lb-top3-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: .75rem;
        }

        @media (max-width: 992px) {
            .lb-top3-grid {
                grid-template-columns: 1fr;
            }
        }

        .lb-top3-card {
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            gap: .75rem;
            align-items: center;
            background: rgba(248, 249, 250, 0.6);
        }

        .lb-avatar {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #2c3e50;
            background: #e9ecef;
            flex: 0 0 auto;
            overflow: hidden;
        }

        .lb-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .lb-name {
            font-weight: 700;
            color: #2c3e50;
            font-size: .95rem;
            line-height: 1.2;
        }

        .lb-points {
            color: #6c757d;
            font-size: .85rem;
        }

        .lb-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: .75rem;
        }

        .lb-table-avatar {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e9ecef;
            font-weight: 700;
            color: #2c3e50;
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
            <div class="nav-item">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="Users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </div>

            <?php if ($isAdmin): ?>
                <div class="nav-item">
                    <a href="Learning_Material.php" class="nav-link">
                        <i class="fas fa-book"></i>
                        <span>Learning Materials</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="Practice_Exams.php" class="nav-link">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Practice Exams</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($isSuperAdmin): ?>
                <div class="nav-item">
                    <a href="Discussion_Forums.php" class="nav-link">
                        <i class="fas fa-comments"></i>
                        <span>Discussion Forums</span>
                    </a>
                </div>
            <?php endif; ?>
            <?php if ($isSuperAdmin): ?>
                <div class="nav-item">
                    <a href="Generate_Reports.php" class="nav-link">
                        <i class="fas fa-file-alt"></i>
                        <span>Generate Reports</span>
                    </a>
                </div>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Top Navigation -->
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
            <a href="https://chemease.site/" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                LOGOUT
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-container">
            <!-- Dashboard Header -->
            <div class="dashboard-header d-flex justify-content-between align-items-start">
                <div>
                    <h1>Dashboard Overview</h1>
                    <p>Last Updated: <?= date('m/d/Y h:i A') ?> PH</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <?php
                // Total Users
                $total_users = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0] ?? 0;

                // Total Exams
                $total_exams = $conn->query("SELECT COUNT(*) FROM exams")->fetch_row()[0] ?? 0;

                // Total Study Materials
                $total_materials = $conn->query("SELECT COUNT(*) FROM study_materials")->fetch_row()[0] ?? 0;

                // Total Forum Threads
                $total_threads = $conn->query("SELECT COUNT(*) FROM forum_threads")->fetch_row()[0] ?? 0;

                // === TOTAL EXAM ATTEMPTS (finished only) ===
                $total_attempts = $conn->query("SELECT COUNT(*) FROM user_exam_attempts WHERE finished_at IS NOT NULL")->fetch_row()[0] ?? 0;

                // === GLOBAL AVERAGE SCORE ===
                $avg_result = $conn->query("SELECT COALESCE(AVG(score), 0) FROM user_exam_attempts WHERE finished_at IS NOT NULL AND score IS NOT NULL");
                $global_avg_score = $avg_result ? round($avg_result->fetch_row()[0]) : 0;
                ?>
                <div class="stat-card users">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h2 class="stat-number"><?= number_format($total_users) ?></h2>
                    <p class="stat-label">Total Users</p>
                </div>

                <div class="stat-card quizzes">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h2 class="stat-number"><?= number_format($total_attempts) ?></h2>
                    <p class="stat-label">Total Attempts</p>
                </div>

                <div class="stat-card materials">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h2 class="stat-number"><?= $global_avg_score ?>%</h2>
                    <p class="stat-label">Average Score</p>
                    <p class="stat-subtitle">Based on <?= number_format($total_attempts) ?> attempt<?= $total_attempts == 1 ? '' : 's' ?></p>
                </div>

                <?php if ($isSuperAdmin): ?>
                    <div class="stat-card forums">
                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h2 class="stat-number"><?= number_format($total_threads) ?></h2>
                        <p class="stat-label">Forum Threads</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <?php
                // User Growth (last 6 months)
                $growth_data = [];
                $labels = [];
                for ($i = 5; $i >= 0; $i--) {
                    $month = date('Y-m', strtotime("-$i months"));
                    $labels[] = date('M', strtotime("-$i months"));
                    $count = $conn->query("SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetch_row()[0] ?? 0;
                    $growth_data[] = $count;
                }

                // Subject Distribution
                $subjects = ['Analytical Chemistry', 'Organic Chemistry', 'Physical Chemistry', 'Inorganic Chemistry', 'BioChemistry'];
                $subject_counts = [];
                foreach ($subjects as $subject) {
                    $count = $conn->query("SELECT COUNT(*) FROM study_materials WHERE category = '$subject'")->fetch_row()[0] ?? 0;
                    $subject_counts[] = $count;
                }
                ?>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">User Growth (6 Months)</h3>
                        <span class="chart-period">MONTHLY</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Subject Distribution</h3>
                    </div>
                    <div class="donut-chart-container">
                        <canvas id="subjectChart"></canvas>
                    </div>
                </div>
            </div>


            <!-- Leaderboard -->
            <div class="chart-card" style="margin-bottom: 2rem;">
                <div class="chart-header">
                    <h3 class="chart-title">Leaderboard</h3>
                    <span class="chart-period">TOP 3</span>
                </div>

                <div id="lbTop3" class="lb-top3-grid">
                </div>

                <div class="lb-actions">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#leaderboardModal">
                        View Full Leaderboard
                    </button>
                </div>
            </div>

            <!-- Full Leaderboard Modal -->
            <div class="modal fade" id="leaderboardModal" tabindex="-1" aria-labelledby="leaderboardModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="leaderboardModalLabel">Leaderboard</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex gap-2 mb-3">
                                <input id="lbSearch" type="text" class="form-control" placeholder="Search name...">
                                <button id="lbSearchBtn" class="btn btn-primary">Search</button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width:70px;">Rank</th>
                                            <th>User</th>
                                            <th style="width:120px;" class="text-end">Points</th>
                                        </tr>
                                    </thead>
                                    <tbody id="lbTableBody">
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="text-muted small" id="lbPageInfo"></div>
                                <div class="btn-group">
                                    <button id="lbPrev" class="btn btn-outline-secondary btn-sm">Prev</button>
                                    <button id="lbNext" class="btn btn-outline-secondary btn-sm">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Access -->
            <div class="quick-access">
                <h3 class="section-title">Quick Access</h3>
                <div class="quick-access-grid">
                    <?php if ($isSuperAdmin): ?>
                        <a href="Users.php" class="quick-access-card">
                            <div class="icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <p class="label">User Management</p>
                        </a>
                    <?php endif; ?>
                    <a href="Learning_Material.php" class="quick-access-card">
                        <div class="icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <p class="label">Learning Materials</p>
                    </a>
                    <a href="Practice_Exams.php" class="quick-access-card">
                        <div class="icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <p class="label">Practice Exams</p>
                    </a>

                    <?php if ($isSuperAdmin): ?>
                        <a href="Generate_Reports.php" class="quick-access-card">
                            <div class="icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <p class="label">Generate Reports</p>
                        </a>
                    <?php endif; ?>
                    <?php if ($isSuperAdmin): ?>
                        <a href="Discussion_Forums.php" class="quick-access-card">
                            <div class="icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <p class="label">Discussion Forum</p>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar Toggle
        document.querySelector('.collapse-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.top-navbar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('collapsed');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-chevron-left');
            icon.classList.toggle('fa-chevron-right');
        });

        // User Growth Line Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?= json_encode($growth_data) ?>,
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Subject Distribution Doughnut Chart
        const subjectCtx = document.getElementById('subjectChart').getContext('2d');
        new Chart(subjectCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($subjects) ?>,
                datasets: [{
                    data: <?= json_encode($subject_counts) ?>,
                    backgroundColor: ['#17a2b8', '#28a745', '#ffc107', '#fd7e14', '#6f42c1'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // Hover Effects
        document.querySelectorAll('.stat-card, .quick-access-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-4px)';
                card.style.boxShadow = '0 8px 20px rgba(0,0,0,0.1)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 2px 10px rgba(0,0,0,0.05)';
            });
        });
    </script>

    <script>
        // ---- Leaderboard logic (same fetch/parse approach as dashboard.php) ----
        const LB_ENDPOINT = "../partial/get_dashboard_data.php?mode=leaderboard";
        const LB_LIMIT = 10;

        function lbGet(obj, path, fallback = undefined) {
            try {
                return path.split('.').reduce((acc, key) => (acc && acc[key] !== undefined ? acc[key] : undefined), obj) ?? fallback;
            } catch {
                return fallback;
            }
        }

        function lbPickUsers(payload) {
            const direct = payload?.users ?? payload?.data ?? payload?.results ?? payload?.leaderboard ?? payload;
            if (Array.isArray(direct)) return direct;
            if (Array.isArray(direct?.users)) return direct.users;
            if (Array.isArray(direct?.data)) return direct.data;
            return [];
        }

        function lbPickTotal(payload) {
            // Prefer explicit totals from API
            const t = payload?.total ?? payload?.meta?.total ?? payload?.pagination?.total ?? payload?.total_count ?? payload?.count;
            return (t == null) ? null : Number(t);
        }

        function lbPickLastPage(payload) {
            const lp =
                payload?.last_page ??
                payload?.meta?.last_page ??
                payload?.pagination?.last_page ??
                payload?.total_pages ??
                payload?.meta?.total_pages ??
                payload?.pagination?.total_pages ??
                payload?.pages;
            return (lp == null) ? null : Number(lp);
        }

        function lbPickName(u) {
            return u?.name ?? u?.full_name ?? u?.fullname ?? u?.username ?? "Unknown";
        }

        function lbPickId(u) {
            return u?.id ?? u?.user_id ?? u?.uid ?? null;
        }

        function lbPickPoints(u) {
            const v = u?.points ?? u?.score ?? u?.total_score ?? u?.total_points ?? u?.xp ?? u?.rank_points ?? 0;
            const n = Number(v);
            return Number.isFinite(n) ? n : 0;
        }

        function lbPickProfilePic(u) {
            return u?.profile_pic ?? u?.avatar ?? u?.avatar_url ?? u?.profilePic ?? u?.photo ?? null;
        }

        function lbInitials(name) {
            const s = String(name || "").trim();
            if (!s) return "?";
            const parts = s.split(/\s+/).filter(Boolean);
            const a = parts[0]?.[0] || "";
            const b = parts.length > 1 ? parts[parts.length - 1]?.[0] : (parts[0]?.[1] || "");
            return (a + b).toUpperCase();
        }

        function lbResolveProfilePicUrl(profilePic) {
            if (!profilePic) return null;
            let pic = String(profilePic).trim();
            if (!pic) return null;

            if (/^https?:\/\//i.test(pic)) return pic;

            pic = pic.replace(/\\/g, "/");

            const idx = pic.indexOf("/uploads/");
            if (idx !== -1) {
                return `${window.location.origin}${pic.slice(idx)}`;
            }

            if (pic.startsWith("/")) return `${window.location.origin}${pic}`;

            if (pic.startsWith("uploads/")) return `${window.location.origin}/${pic}`;

            return `${window.location.origin}/${pic}`;
        }

        function lbAvatarHTML(name, profilePic, size = "lg") {
            const initials = lbInitials(name);
            const url = lbResolveProfilePicUrl(profilePic);

            const cls = (size === "sm") ? "lb-table-avatar" : "lb-avatar";
            if (!url) {
                return `<div class="${cls}" aria-hidden="true">${initials}</div>`;
            }

            // fallback to initials if image fails
            const safeInitials = initials.replace(/"/g, "&quot;");
            return `
          <div class="${cls}" aria-hidden="true" data-initials="${safeInitials}">
            <img src="${url}" alt="" onerror="
              try {
                const host = this.closest('[data-initials]');
                if (host) { host.textContent = host.dataset.initials || '?'; }
              } catch(e) {}
            ">
          </div>
        `.trim();
        }

        async function lbFetchUsers({
            page = 1,
            limit = LB_LIMIT,
            search = ""
        } = {}) {
            const url = `${LB_ENDPOINT}&page=${encodeURIComponent(page)}&limit=${encodeURIComponent(limit)}&search=${encodeURIComponent(search)}`;
            const res = await fetch(url, {
                credentials: "same-origin"
            });
            if (!res.ok) throw new Error(`Leaderboard fetch failed: ${res.status}`);
            return res.json();
        }

        function lbRenderTop3(users) {
            const el = document.getElementById("lbTop3");
            if (!el) return;

            if (!users.length) {
                el.innerHTML = `<div class="text-muted">No leaderboard data yet.</div>`;
                return;
            }

            el.innerHTML = users.slice(0, 3).map((u, i) => {
                const name = lbPickName(u);
                const points = lbPickPoints(u);
                const pic = lbPickProfilePic(u);
                const rank = u?.rank ?? (i + 1);

                return `
            <div class="lb-top3-card">
              ${lbAvatarHTML(name, pic, "lg")}
              <div style="min-width:0;">
                <div class="lb-name text-truncate">#${rank} ${name}</div>
                <div class="lb-points">${points.toLocaleString()} pts</div>
              </div>
            </div>
          `;
            }).join("");
        }

        function lbRenderTable(users, {
            page,
            limit
        }) {
            const body = document.getElementById("lbTableBody");
            if (!body) return;

            if (!users.length) {
                body.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-4">No results</td></tr>`;
                return;
            }

            body.innerHTML = users.map((u, i) => {
                const name = lbPickName(u);
                const points = lbPickPoints(u);
                const pic = lbPickProfilePic(u);
                const computedRank = (((page - 1) * limit) + i + 1);
                const idKey = lbPickId(u);
                const mapRank = lbModalSearch ? (lbRankMap?.get(String(idKey)) ?? lbRankMap?.get(String(name))) : null;
                const rank = u?.rank ?? mapRank ?? computedRank;

                return `
            <tr>
              <td class="fw-semibold">#${rank}</td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  ${lbAvatarHTML(name, pic, "sm")}
                  <div class="text-truncate">${name}</div>
                </div>
              </td>
              <td class="text-end fw-semibold">${points.toLocaleString()}</td>
            </tr>
          `;
            }).join("");
        }

        function lbSetPageInfo({
            page,
            limit,
            total,
            lastPage
        }) {
            const info = document.getElementById("lbPageInfo");
            if (!info) return;

            const hasTotal = typeof total === "number" && !Number.isNaN(total);
            const hasLast = typeof lastPage === "number" && !Number.isNaN(lastPage);

            if (hasTotal) {
                const start = total === 0 ? 0 : ((page - 1) * limit + 1);
                const end = Math.min(page * limit, total);
                info.textContent = `Showing ${start}-${end} of ${total}`;
                return;
            }

            if (hasLast) {
                info.textContent = `Page ${page} of ${lastPage}`;
                return;
            }

            info.textContent = `Page ${page}`;
        }

        function lbSetPagerState({
            page,
            limit,
            total,
            lastPage
        }) {
            const prev = document.getElementById("lbPrev");
            const next = document.getElementById("lbNext");
            if (!prev || !next) return;

            prev.disabled = page <= 1;

            const hasLast = typeof lastPage === "number" && !Number.isNaN(lastPage);
            if (hasLast) {
                next.disabled = page >= lastPage;
                return;
            }

            const hasTotal = typeof total === "number" && !Number.isNaN(total);
            if (hasTotal) {
                next.disabled = (page * limit) >= total;
                return;
            }

            next.disabled = false;
        }

        let lbModalPage = 1;
        let lbModalSearch = "";


        let lbRankMap = null;
        let lbRankMapBuilt = false;

        async function lbBuildRankMap() {
            lbRankMap = new Map();
            lbRankMapBuilt = true;

            const BIG_LIMIT = 1000;
            let page = 1;

            const firstPayload = await lbFetchUsers({
                page,
                limit: BIG_LIMIT,
                search: ""
            });
            const firstUsers = lbPickUsers(firstPayload);
            const lastPage = lbPickLastPage(firstPayload);

            const addUsers = (users, offset) => {
                users.forEach((u, i) => {
                    const id = lbPickId(u);
                    const name = lbPickName(u);
                    const r = (u?.rank != null) ? Number(u.rank) : (offset + i + 1);
                    if (id != null) lbRankMap.set(String(id), r);
                    if (name) lbRankMap.set(String(name), r);
                });
            };

            addUsers(firstUsers, 0);

            if (lastPage && lastPage > 1) {
                for (page = 2; page <= lastPage; page++) {
                    const payload = await lbFetchUsers({
                        page,
                        limit: BIG_LIMIT,
                        search: ""
                    });
                    const users = lbPickUsers(payload);
                    addUsers(users, (page - 1) * BIG_LIMIT);
                    if (users.length < BIG_LIMIT) break; // safety
                }
            }
        }

        async function lbEnsureRankMap() {
            if (lbRankMapBuilt) return;
            try {
                await lbBuildRankMap();
            } catch (e) {
                lbRankMapBuilt = true;
                lbRankMap = null;
            }
        }

        async function lbLoadModalPage() {
            const tbody = document.getElementById("lbTableBody");
            if (tbody) tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-4">Loading...</td></tr>`;

            if (lbModalSearch) {
                await lbEnsureRankMap();
            }

            const payload = await lbFetchUsers({
                page: lbModalPage,
                limit: LB_LIMIT,
                search: lbModalSearch
            });
            const users = lbPickUsers(payload);

            const total = lbPickTotal(payload);
            const lastPage = lbPickLastPage(payload);

            const inferredLast = (lastPage == null && users.length < LB_LIMIT) ? lbModalPage : lastPage;

            lbRenderTable(users, {
                page: lbModalPage,
                limit: LB_LIMIT
            });
            lbSetPageInfo({
                page: lbModalPage,
                limit: LB_LIMIT,
                total: total ?? null,
                lastPage: inferredLast ?? null
            });
            lbSetPagerState({
                page: lbModalPage,
                limit: LB_LIMIT,
                total: total ?? null,
                lastPage: inferredLast ?? null
            });
        }

        async function lbInit() {
            try {
                const payload = await lbFetchUsers({
                    page: 1,
                    limit: 3,
                    search: ""
                });
                const users = lbPickUsers(payload);
                lbRenderTop3(users);
            } catch (e) {
                const el = document.getElementById("lbTop3");
                if (el) el.innerHTML = `<div class="text-danger">Failed to load leaderboard.</div>`;
            }

            const modalEl = document.getElementById("leaderboardModal");
            if (modalEl) {
                modalEl.addEventListener("shown.bs.modal", () => {
                    lbModalPage = 1;
                    lbModalSearch = document.getElementById("lbSearch")?.value?.trim() || "";
                    lbLoadModalPage();
                });

                modalEl.addEventListener("hidden.bs.modal", () => {
                    document.querySelectorAll(".modal-backdrop").forEach(b => b.remove());
                    document.body.classList.remove("modal-open");
                    document.body.style.overflow = "";
                    document.body.style.paddingRight = "";
                });
            }

            document.getElementById("lbPrev")?.addEventListener("click", () => {
                lbModalPage = Math.max(1, lbModalPage - 1);
                lbLoadModalPage();
            });
            document.getElementById("lbNext")?.addEventListener("click", () => {
                lbModalPage = lbModalPage + 1;
                lbLoadModalPage();
            });

            document.getElementById("lbSearchBtn")?.addEventListener("click", () => {
                lbModalSearch = document.getElementById("lbSearch")?.value?.trim() || "";
                lbModalPage = 1;
                lbLoadModalPage();
            });

            document.getElementById("lbSearch")?.addEventListener("keydown", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    document.getElementById("lbSearchBtn")?.click();
                }
            });
        }

        document.addEventListener("DOMContentLoaded", lbInit);
    </script>

</body>

</html>