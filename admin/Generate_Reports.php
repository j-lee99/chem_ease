<?php
session_start();
require_once '../partial/db_conn.php';

$role = $_SESSION['role'] ?? '';
$isAdmin = ($role === 'admin');
$isSuperAdmin = ($role === 'super_admin');

if (!isset($_SESSION['user_id']) || (!$isAdmin && !$isSuperAdmin)) {
    header('Location: ../index.php');
    exit;
}

$accessDenied = !$isSuperAdmin;

function fetchScalar(mysqli $conn, string $sql, $default = 0)
{
    $res = $conn->query($sql);
    if (!$res) return $default;

    $row = $res->fetch_row();
    return $row[0] ?? $default;
}

function safePercent(int|float $part, int|float $whole, int $precision = 0): float
{
    if ((float)$whole <= 0) return 0;
    return round(((float)$part / (float)$whole) * 100, $precision);
}

$total_users = 0;
$new_users_last_30 = 0;
$active_users_last_30 = 0;
$total_attempts = 0;
$avg_score = 0;
$passed_attempts = 0;
$pass_rate = 0;

$top_users = [];
$attempts_last_30 = [];
$material_engagement = [];

if (!$accessDenied) {
    $total_users = (int) fetchScalar(
        $conn,
        "SELECT COUNT(*) FROM users WHERE role = 'user'",
        0
    );

    $new_users_last_30 = (int) fetchScalar(
        $conn,
        "SELECT COUNT(*)
         FROM users
         WHERE role = 'user'
           AND created_at IS NOT NULL
           AND created_at >= (NOW() - INTERVAL 30 DAY)",
        0
    );

    $active_users_last_30 = (int) fetchScalar(
        $conn,
        "SELECT COUNT(DISTINCT uea.user_id)
         FROM user_exam_attempts uea
         INNER JOIN users u ON u.id = uea.user_id
         WHERE uea.finished_at IS NOT NULL
           AND uea.finished_at >= (NOW() - INTERVAL 30 DAY)
           AND u.role = 'user'",
        0
    );

    $total_attempts = (int) fetchScalar(
        $conn,
        "SELECT COUNT(*)
         FROM user_exam_attempts
         WHERE finished_at IS NOT NULL",
        0
    );

    $avg_score = (int) round((float) fetchScalar(
        $conn,
        "SELECT COALESCE(AVG(score), 0)
         FROM user_exam_attempts
         WHERE finished_at IS NOT NULL
           AND score IS NOT NULL",
        0
    ));

    $passed_attempts = (int) fetchScalar(
        $conn,
        "SELECT COUNT(*)
         FROM user_exam_attempts uea
         INNER JOIN exams e ON e.id = uea.exam_id
         WHERE uea.finished_at IS NOT NULL
           AND uea.score IS NOT NULL
           AND e.passing_score IS NOT NULL
           AND uea.score >= e.passing_score",
        0
    );

    $pass_rate = safePercent($passed_attempts, $total_attempts, 0);

    $res = $conn->query("
        SELECT DATE(finished_at) AS d, COUNT(*) AS c
        FROM user_exam_attempts
        WHERE finished_at IS NOT NULL
          AND finished_at >= (NOW() - INTERVAL 30 DAY)
        GROUP BY DATE(finished_at)
        ORDER BY d ASC
    ");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $attempts_last_30[] = [
                'date' => $r['d'],
                'count' => (int) $r['c']
            ];
        }
    }

    $res = $conn->query("
        SELECT
            u.id,
            u.full_name,
            COALESCE(AVG(a.score), 0) AS avg_score,
            COUNT(*) AS attempts
        FROM user_exam_attempts a
        INNER JOIN users u ON u.id = a.user_id
        WHERE a.finished_at IS NOT NULL
          AND a.score IS NOT NULL
          AND u.role = 'user'
        GROUP BY u.id, u.full_name
        HAVING COUNT(*) >= 1
        ORDER BY avg_score DESC, attempts DESC, u.id ASC
        LIMIT 10
    ");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $top_users[] = [
                'id' => (int) $r['id'],
                'name' => $r['full_name'] ?? 'Unknown',
                'avg_score' => (int) round((float) ($r['avg_score'] ?? 0)),
                'attempts' => (int) ($r['attempts'] ?? 0),
            ];
        }
    }

    // Matches get_progress structure:
    // study_materials -> study_material_files -> user_progress
    $res = $conn->query("
        SELECT
            m.id,
            m.title,
            m.category,
            m.module,
            COUNT(DISTINCT f.id) AS total_files,
            COUNT(DISTINCT p.user_id) AS engaged_users,
            COALESCE(ROUND(AVG(p.progress)), 0) AS avg_progress
        FROM study_materials m
        LEFT JOIN study_material_files f
            ON f.material_id = m.id
        LEFT JOIN user_progress p
            ON p.file_id = f.id
        GROUP BY m.id, m.title, m.category, m.module
        HAVING COUNT(DISTINCT p.user_id) > 0
        ORDER BY engaged_users DESC, avg_progress DESC, m.title ASC
        LIMIT 10
    ");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $material_engagement[] = [
                'id' => (int) $r['id'],
                'title' => $r['title'] ?? 'Untitled Material',
                'category' => $r['category'] ?? '—',
                'module' => $r['module'] ?? null,
                'engaged_users' => (int) ($r['engaged_users'] ?? 0),
                'total_files' => (int) ($r['total_files'] ?? 0),
                'avg_progress' => (int) ($r['avg_progress'] ?? 0),
            ];
        }
    }
}

$labels = array_map(fn($x) => $x['date'], $attempts_last_30);
$counts = array_map(fn($x) => $x['count'], $attempts_last_30);

$topUserLabels = array_map(fn($x) => $x['name'], $top_users);
$topUserScores = array_map(fn($x) => (int) $x['avg_score'], $top_users);

$materialLabels = array_map(fn($x) => $x['title'], $material_engagement);
$materialUsers = array_map(fn($x) => (int) $x['engaged_users'], $material_engagement);

$inactive_users_last_30 = max(0, $total_users - $active_users_last_30);
$activeUserLabels = ['Active Users', 'Inactive Users'];
$activeUserCounts = [$active_users_last_30, $inactive_users_last_30];

$failed_attempts = max(0, $total_attempts - $passed_attempts);
$passRateLabels = ['Passed', 'Failed'];
$passRateCounts = [$passed_attempts, $failed_attempts];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php
        if ($isSuperAdmin) {
            echo "ChemEase Super Admin Panel - Reports & Analytics";
        } elseif ($isAdmin) {
            echo "ChemEase Admin Panel - Reports & Analytics";
        } else {
            echo "ChemEase - Reports & Analytics";
        }
        ?>
    </title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

        .page-header {
            margin-bottom: 1.25rem;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .btn-soft {
            border: 1px solid #d9dee3;
            background: #fff;
            color: #34495e;
        }

        .btn-soft:hover {
            background: #f8f9fa;
        }

        .metric-card .metric-icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            flex: 0 0 auto;
        }

        .metric-value {
            font-size: 28px;
            line-height: 1;
            font-weight: 800;
        }

        .chart-wrap {
            position: relative;
            min-height: 300px;
        }

        .chart-wrap-sm {
            position: relative;
            min-height: 260px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .top-navbar {
                margin-left: 0;
                padding: 12px 16px;
            }

            .main-content {
                margin-left: 0;
                padding: 16px;
            }

            .chart-wrap,
            .chart-wrap-sm {
                min-height: 240px;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar" id="sidebar">
        <div class="brand">
            <img src="../images/logo.png" alt="ChemEase Logo">
            <span>ChemEase</span>
            <button class="collapse-btn ms-auto" type="button" onclick="toggleSidebar()">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="index.php" class="nav-link" data-section="dashboard">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </a>
            </div>

            <?php if ($isSuperAdmin): ?>
                <div class="nav-item">
                    <a href="Users.php" class="nav-link" data-section="users">
                        <i class="fas fa-users"></i><span>Users</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <div class="nav-item">
                    <a href="Learning_Material.php" class="nav-link" data-section="learning">
                        <i class="fas fa-book"></i><span>Learning Materials</span>
                    </a>
                </div>

                <div class="nav-item">
                    <a href="Practice_Exams.php" class="nav-link" data-section="exams">
                        <i class="fas fa-clipboard-list"></i><span>Practice Exams</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($isSuperAdmin): ?>
                <div class="nav-item">
                    <a href="Discussion_Forums.php" class="nav-link" data-section="forums">
                        <i class="fas fa-comments"></i><span>Discussion Forums</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($isSuperAdmin): ?>
                <div class="nav-item">
                    <a href="Generate_Reports.php" class="nav-link active" data-section="reports">
                        <i class="fas fa-file-alt"></i><span>Reports & Analytics</span>
                    </a>
                </div>
            <?php endif; ?>
        </nav>
    </div>

    <div class="top-navbar" id="topNavbar">
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

    <div class="main-content" id="mainContent">
        <div class="dashboard-container">

            <div class="page-header">
                <div>
                    <div class="page-title">Reports & Analytics</div>
                    <div class="page-subtitle">Analytics snapshot + downloadable reports</div>
                </div>

                <?php if ($isSuperAdmin): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-soft" href="download_reports_pdf.php" target="_blank"><i class="fa-solid fa-file-pdf me-2"></i>Download PDF</a>
                        <a class="btn btn-primary" href="download_reports_csv.php" target="_blank"><i class="fa-solid fa-file-csv me-2"></i>Download CSV</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($accessDenied): ?>
                <div class="alert alert-danger">Access denied. This page is available to <strong>Super Admin</strong> only.</div>
            <?php else: ?>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-xl-2">
                        <div class="card shadow-sm h-100 metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="metric-icon" style="background:#e8f7ee;color:#28a745;">
                                        <i class="fa-solid fa-users"></i>
                                    </div>
                                    <div>
                                        <div class="metric-value"><?= number_format($total_users) ?></div>
                                        <div class="text-muted">Total Users</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-2">
                        <div class="card shadow-sm h-100 metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="metric-icon" style="background:#efeaff;color:#6f42c1;">
                                        <i class="fa-solid fa-user-plus"></i>
                                    </div>
                                    <div>
                                        <div class="metric-value"><?= number_format($new_users_last_30) ?></div>
                                        <div class="text-muted">New Users (30d)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-2">
                        <div class="card shadow-sm h-100 metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="metric-icon" style="background:#e9f7ff;color:#17a2b8;">
                                        <i class="fa-solid fa-user-check"></i>
                                    </div>
                                    <div>
                                        <div class="metric-value"><?= number_format($active_users_last_30) ?></div>
                                        <div class="text-muted">Active Users (30d)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-2">
                        <div class="card shadow-sm h-100 metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="metric-icon" style="background:#e9f7ff;color:#17a2b8;">
                                        <i class="fa-solid fa-clipboard-check"></i>
                                    </div>
                                    <div>
                                        <div class="metric-value"><?= number_format($total_attempts) ?></div>
                                        <div class="text-muted">Total Attempts</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-2">
                        <div class="card shadow-sm h-100 metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="metric-icon" style="background:#fff6e5;color:#ffc107;">
                                        <i class="fa-solid fa-chart-line"></i>
                                    </div>
                                    <div>
                                        <div class="metric-value"><?= (int)$avg_score ?>%</div>
                                        <div class="text-muted">Average Score</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-2">
                        <div class="card shadow-sm h-100 metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="metric-icon" style="background:#fff1f1;color:#dc3545;">
                                        <i class="fa-solid fa-award"></i>
                                    </div>
                                    <div>
                                        <div class="metric-value"><?= (int)$pass_rate ?>%</div>
                                        <div class="text-muted">Pass Rate</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <div class="fw-semibold">Pass Rate</div>
                            </div>
                            <div class="card-body">
                                <div class="chart-wrap-sm">
                                    <canvas id="passRateChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <div class="fw-semibold">User Activity (Last 30 Days)</div>
                            </div>
                            <div class="card-body">
                                <div class="chart-wrap-sm">
                                    <canvas id="activeUsersChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-lg-7">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Attempts (Last 30 Days)</div>
                            </div>
                            <div class="card-body">
                                <div class="chart-wrap-sm">
                                    <canvas id="attemptsChart"></canvas>
                                </div>
                                <?php if (empty($attempts_last_30)): ?>
                                    <div class="text-muted small mt-2">No attempts recorded in the last 30 days.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Top Learners (Avg Score)</div>
                            </div>
                            <div class="card-body">
                                <div class="chart-wrap">
                                    <canvas id="topLearnersChart"></canvas>
                                </div>
                                <?php if (empty($top_users)): ?>
                                    <div class="text-muted small mt-2">No learner data available.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Top Learning Material Engagement</div>
                            </div>
                            <div class="card-body">
                                <div class="chart-wrap">
                                    <canvas id="materialEngagementChart"></canvas>
                                </div>
                                <?php if (empty($material_engagement)): ?>
                                    <div class="text-muted small mt-2">No material engagement data available.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const topNavbar = document.getElementById('topNavbar');
            const mainContent = document.getElementById('mainContent');

            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
                return;
            }

            sidebar.classList.toggle('collapsed');
            topNavbar.classList.toggle('collapsed');
            mainContent.classList.toggle('collapsed');
        }

        (function() {
            const labels = <?= json_encode($labels) ?>;
            const counts = <?= json_encode($counts) ?>;

            const topUserLabels = <?= json_encode($topUserLabels) ?>;
            const topUserScores = <?= json_encode($topUserScores) ?>;

            const materialLabels = <?= json_encode($materialLabels) ?>;
            const materialUsers = <?= json_encode($materialUsers) ?>;

            const passRateLabels = <?= json_encode($passRateLabels) ?>;
            const passRateCounts = <?= json_encode($passRateCounts) ?>;

            const activeUserLabels = <?= json_encode($activeUserLabels) ?>;
            const activeUserCounts = <?= json_encode($activeUserCounts) ?>;

            function createHorizontalBarChart(id, labels, data, labelText, backgroundColor = '#17a2b8') {
                const canvas = document.getElementById(id);
                if (!canvas || !labels.length) return;

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: labelText,
                            data: data,
                            backgroundColor: backgroundColor,
                            borderRadius: 8,
                            barThickness: 18
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    usePointStyle: false
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.dataset.label}: ${context.raw}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            },
                            y: {
                                ticks: {
                                    autoSkip: false
                                }
                            }
                        }
                    }
                });
            }

            function createDoughnutChart(id, labels, data, colors) {
                const canvas = document.getElementById(id);
                if (!canvas) return;

                new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: data,
                            backgroundColor: colors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    padding: 14,
                                    boxWidth: 12
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.label}: ${context.raw}`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            const attemptsCanvas = document.getElementById('attemptsChart');
            if (attemptsCanvas) {
                new Chart(attemptsCanvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Attempts',
                            data: counts,
                            tension: 0.35,
                            fill: true,
                            borderColor: '#17a2b8',
                            backgroundColor: 'rgba(23, 162, 184, 0.15)',
                            pointBackgroundColor: '#17a2b8',
                            pointBorderColor: '#17a2b8',
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    boxWidth: 12
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `Attempts: ${context.raw}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }

            createHorizontalBarChart(
                'topLearnersChart',
                topUserLabels,
                topUserScores,
                'Average Score (%)',
                '#17a2b8'
            );

            createHorizontalBarChart(
                'materialEngagementChart',
                materialLabels,
                materialUsers,
                'Engaged Users',
                '#6f42c1'
            );

            createDoughnutChart(
                'passRateChart',
                passRateLabels,
                passRateCounts,
                ['#28a745', '#dc3545']
            );

            createDoughnutChart(
                'activeUsersChart',
                activeUserLabels,
                activeUserCounts,
                ['#17a2b8', '#dee2e6']
            );
        })();
    </script>
</body>

</html>