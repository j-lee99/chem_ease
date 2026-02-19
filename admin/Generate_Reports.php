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

if (!$isSuperAdmin) {
    $accessDenied = true;
} else {
    $accessDenied = false;
}

$total_users = 0;
$total_attempts = 0;
$avg_score = 0;
$top_users = [];
$attempts_last_30 = [];

if (!$accessDenied) {
    // Total users
    $res = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='user'");
    if ($res) {
        $row = $res->fetch_assoc();
        $total_users = (int)($row['c'] ?? 0);
    } else {
        $res2 = $conn->query("SELECT COUNT(*) AS c FROM users");
        if ($res2) {
            $row = $res2->fetch_assoc();
            $total_users = (int)($row['c'] ?? 0);
        }
    }

    // Total attempts
    $res = $conn->query("SELECT COUNT(*) AS c FROM user_exam_attempts WHERE finished_at IS NOT NULL");
    if ($res) {
        $row = $res->fetch_assoc();
        $total_attempts = (int)($row['c'] ?? 0);
    }

    // Avg score
    $res = $conn->query("SELECT COALESCE(AVG(score), 0) AS a FROM user_exam_attempts WHERE finished_at IS NOT NULL AND score IS NOT NULL");
    if ($res) {
        $row = $res->fetch_assoc();
        $avg_score = (int)round((float)($row['a'] ?? 0));
    }

    // Attempts last 30 days
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
            $attempts_last_30[] = ['date' => $r['d'], 'count' => (int)$r['c']];
        }
    }

    // Top users by avg score
    $res = $conn->query("
        SELECT u.id, u.full_name, COALESCE(AVG(a.score), 0) AS avg_score, COUNT(*) AS attempts
        FROM user_exam_attempts a
        JOIN users u ON u.id = a.user_id
        WHERE a.finished_at IS NOT NULL AND a.score IS NOT NULL
        GROUP BY u.id, u.full_name
        HAVING COUNT(*) >= 1
        ORDER BY avg_score DESC, attempts DESC, u.id ASC
        LIMIT 10
    ");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $top_users[] = [
                'id' => (int)$r['id'],
                'name' => $r['full_name'] ?? 'Unknown',
                'avg_score' => (int)round((float)($r['avg_score'] ?? 0)),
                'attempts' => (int)($r['attempts'] ?? 0),
            ];
        }
    }
}

$labels = array_map(fn($x) => $x['date'], $attempts_last_30);
$counts = array_map(fn($x) => $x['count'], $attempts_last_30);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChemEase Admin - Generate Reports</title>
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
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            border-right: 1px solid #e9ecef;
            transition: width 0.3s ease;
            overflow: hidden;
            z-index: 1000;
        }
        .sidebar.collapsed { width: 60px; }
        .sidebar .brand {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            background: #ffffff;
        }
        .sidebar.collapsed .brand { justify-content: center; }
        .sidebar .brand img { width: 32px; height: 32px; margin-right: 12px; }
        .sidebar.collapsed .brand img { margin-right: 0; }
        .sidebar .brand span { font-size: 20px; font-weight: 600; color: var(--primary); }
        .sidebar.collapsed .brand span { display: none; }

        .sidebar-nav { padding: 0; }
        .nav-item { margin: 0; }
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
        .sidebar.collapsed .nav-link span { display: none; }
        .nav-link:hover { background-color: #f8f9fa; color: #495057 !important; }
        .nav-link.active { background-color: var(--primary); color: white !important; }
        .nav-link i { width: 20px; margin-right: 12px; text-align: center; font-size: 16px; }
        .sidebar.collapsed .nav-link i { margin-right: 0; font-size: 18px; }

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
        .top-navbar.collapsed { margin-left: 60px; }
        .top-navbar h4 { color: white; margin: 0; font-weight: 600; font-size: 18px; }
        .top-navbar .navbar-actions { display: flex; align-items: center; gap: 15px; }
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
        .logout-btn:hover { background: rgba(255,255,255,0.1); color: white; }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: calc(100vh - 54px);
            background: #e9ecef;
            transition: margin-left 0.3s ease;
        }
        .main-content.collapsed { margin-left: 60px; }

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
        .collapse-btn:hover { color: #495057; }

        .dashboard-container { max-width: 1400px; margin: 0 auto; }
        .page-header { margin-bottom: 1.25rem; display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-end; }
        .page-title { font-size: 1.8rem; font-weight: 700; color: #2c3e50; margin: 0; }
        .page-subtitle { color: #6c757d; font-size: 0.9rem; margin-top: 0.25rem; }

        .btn-soft {
            border: 1px solid #d9dee3;
            background: #fff;
            color: #34495e;
        }
        .btn-soft:hover { background:#f8f9fa; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .top-navbar { margin-left: 0; padding: 12px 16px; }
            .main-content { margin-left: 0; padding: 16px; }
        }

        .metric-card .metric-icon {
            width: 44px;
            height: 44px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius: 999px;
            flex: 0 0 auto;
        }
        .metric-value { font-size: 28px; line-height: 1; font-weight: 800; }

        .table thead th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
    </style>
</head>
<body>

    <!-- Sidebar -->
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
                    <i class="fas fa-file-alt"></i><span>Generate Reports</span>
                </a>
            </div>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar" id="topNavbar">
        <h4>ADMIN PANEL</h4>
        <div class="navbar-actions">
            <a href="https://chemease.site/" class="logout-btn"><i class="fas fa-sign-out-alt"></i> LOGOUT</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="dashboard-container">

            <div class="page-header">
                <div>
                    <div class="page-title">Generate Reports</div>
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

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <div class="card shadow-sm h-100 metric-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="metric-icon" style="background:#e7f7fa;color:#17a2b8;">
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

                <div class="col-12 col-md-4">
                    <div class="card shadow-sm h-100 metric-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="metric-icon" style="background:#eaf7ee;color:#28a745;">
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

                <div class="col-12 col-md-4">
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
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-7">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div class="fw-semibold">Attempts (Last 30 Days)</div>
                        </div>
                        <div class="card-body">
                            <canvas id="attemptsChart" height="120"></canvas>
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
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:60px;">#</th>
                                            <th>User</th>
                                            <th class="text-end">Avg</th>
                                            <th class="text-end">Attempts</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($top_users)): ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">No data.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($top_users as $i => $u): ?>
                                                <tr>
                                                    <td class="fw-semibold"><?= $i + 1 ?></td>
                                                    <td><?= htmlspecialchars($u['name']) ?></td>
                                                    <td class="text-end"><?= (int)$u['avg_score'] ?>%</td>
                                                    <td class="text-end"><?= (int)$u['attempts'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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

            const canvas = document.getElementById('attemptsChart');
            if (!canvas) return;

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Attempts',
                        data: counts,
                        tension: 0.35,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        })();
    </script>
</body>
</html>
