<?php
session_start();
require_once '../partial/db_conn.php';

// If NOT logged in OR not admin → back to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
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
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
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
        .logout-btn {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.3);
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
            background: rgba(255,255,255,0.1);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
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
        .stat-card.users .stat-icon { background: var(--primary); }
        .stat-card.quizzes .stat-icon { background: var(--success); }
        .stat-card.materials .stat-icon { background: var(--warning); }
        .stat-card.forums .stat-icon { background: var(--purple); }
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .quick-access-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
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
            <div class="nav-item">
                <a href="Discussion_Forums.php" class="nav-link">
                    <i class="fas fa-comments"></i>
                    <span>Discussion Forums</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Top Navigation -->
    <div class="top-navbar">
        <h4>ADMIN PANEL</h4>
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
                $total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0;

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

                <div class="stat-card forums">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h2 class="stat-number"><?= number_format($total_threads) ?></h2>
                    <p class="stat-label">Forum Threads</p>
                </div>
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

            <!-- Quick Access -->
            <div class="quick-access">
                <h3 class="section-title">Quick Access</h3>
                <div class="quick-access-grid">
                    <a href="Users.php" class="quick-access-card">
                        <div class="icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <p class="label">User Management</p>
                    </a>
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
                    <a href="Discussion_Forums.php" class="quick-access-card">
                        <div class="icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <p class="label">Discussion Forum</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    },
                    x: { grid: { display: false } }
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
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } }
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
</body>
</html>