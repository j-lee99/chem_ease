<?php
session_start();
require_once '../partial/db_conn.php';

$role = $_SESSION['role'] ?? '';
$isAdmin = ($role === 'admin');
$isSuperAdmin = ($role === 'super_admin');

if (!isset($_SESSION['user_id']) || !in_array($role, ['admin', 'super_admin'], true)) {
    header("Location: ../index.php");
    exit();
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

if (!$isSuperAdmin) {
    header("Location: index.php");
    exit();
}

$settings = [
    'maintenance_mode' => 0,
    'maintenance_message' => '',
    'site_banner_enabled' => 0,
    'site_banner_message' => ''
];

$settingsQuery = $conn->query("SELECT * FROM system_settings WHERE id = 1 LIMIT 1");
if ($settingsQuery && $settingsQuery->num_rows > 0) {
    $settings = $settingsQuery->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChemEase System Settings</title>
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

        .page-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        .page-header p {
            color: #6c757d;
            font-size: 0.95rem;
            margin-top: 0.35rem;
        }

        .settings-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
            <div class="nav-item">
                <a href="index.php" class="nav-link">
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

            <div class="nav-item">
                <a href="Discussion_Forums.php" class="nav-link">
                    <i class="fas fa-comments"></i>
                    <span>Discussion Forums</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="Generate_Reports.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports & Analytics</span>
                </a>
            </div>

            <!--<div class="nav-item">-->
            <!--    <a href="Settings.php" class="nav-link active">-->
            <!--        <i class="fas fa-cog"></i>-->
            <!--        <span>Settings</span>-->
            <!--    </a>-->
            <!--</div>-->
        </nav>
    </div>

    <div class="top-navbar">
        <h4>SUPER ADMIN PANEL</h4>

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
                        <img src="../<?= htmlspecialchars($profile_image) ?>?t=<?= time() ?>" alt="Profile" class="profile-img">
                    <?php else: ?>
                        <div class="profile-initials"><?= htmlspecialchars($initials) ?></div>
                    <?php endif; ?>
                </div>

                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminProfileDropdown">
                    <li class="dropdown-header px-3 py-2">
                        <strong><?= htmlspecialchars($full_name) ?></strong><br>
                        <small class="text-muted">Super Admin</small>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <a class="dropdown-item" href="Profile_Settings.php">
                            <i class="fas fa-user-cog me-2"></i> Profile Settings
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="Settings.php">
                            <i class="fas fa-cog me-2"></i> System Settings
                        </a>
                    </li>

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
        <div class="page-container">
            <div class="page-header">
                <h1>System Settings</h1>
                <p>Manage maintenance mode and site-wide announcement banners.</p>
            </div>

            <div class="settings-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">System Controls</h4>
                        <p class="text-muted mb-0">Super Admin only configuration page.</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="border rounded-3 p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Maintenance Mode</h5>
                                    <p class="text-muted small mb-0">
                                        Restrict student and normal user access during updates.
                                    </p>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="maintenanceModeToggle">
                                </div>
                            </div>

                            <div class="alert alert-warning small mb-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Only admin and super admin accounts can access the platform while maintenance mode is enabled.
                            </div>

                            <label class="form-label fw-semibold">Maintenance Message</label>
                            <textarea
                                class="form-control"
                                id="maintenanceMessage"
                                rows="5"
                                placeholder="The system is currently under maintenance. Please try again later."
                            ></textarea>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded-3 p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Site Announcement Banner</h5>
                                    <p class="text-muted small mb-0">
                                        Display a banner message across all pages.
                                    </p>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="siteBannerToggle">
                                </div>
                            </div>

                            <div class="alert alert-info small mb-3">
                                <i class="fas fa-bullhorn me-2"></i>
                                Example: System maintenance on April 10 at 8:00 PM.
                            </div>

                            <label class="form-label fw-semibold">Banner Message</label>
                            <textarea
                                class="form-control"
                                id="siteBannerMessage"
                                rows="5"
                                placeholder="Enter your site-wide announcement..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary px-4" id="saveSystemSettingsBtn">
                        <i class="fas fa-save me-2"></i>
                        Save Settings
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="settingsToast" class="toast align-items-center border-0 text-white bg-success" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div id="settingsToastBody" class="toast-body">
                    Settings saved successfully.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script>
        document.querySelector('.collapse-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.top-navbar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('collapsed');

            const icon = this.querySelector('i');
            icon.classList.toggle('fa-chevron-left');
            icon.classList.toggle('fa-chevron-right');
        });

        const maintenanceModeToggle = document.getElementById('maintenanceModeToggle');
        const maintenanceMessage = document.getElementById('maintenanceMessage');
        const siteBannerToggle = document.getElementById('siteBannerToggle');
        const siteBannerMessage = document.getElementById('siteBannerMessage');
        const saveSystemSettingsBtn = document.getElementById('saveSystemSettingsBtn');

        function showToast(message, success = true) {
            const toastEl = document.getElementById('settingsToast');
            const toastBody = document.getElementById('settingsToastBody');
            if (!toastEl || !toastBody) return;

            toastBody.textContent = message;
            toastEl.className = `toast align-items-center border-0 text-white bg-success`;

            const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3000 });
            toast.show();
        }

        function loadSystemSettings() {
            fetch('../partial/get_system_settings.php')
                .then(r => r.json())
                .then(data => {
                    if (data.status !== 'success') {
                        throw new Error(data.message || 'Failed to load settings.');
                    }

                    const settings = data.data || {};
                    maintenanceModeToggle.checked = Number(settings.maintenance_mode) === 1;
                    maintenanceMessage.value = settings.maintenance_message || '';
                    siteBannerToggle.checked = Number(settings.site_banner_enabled) === 1;
                    siteBannerMessage.value = settings.site_banner_message || '';
                })
                .catch(err => {
                    showToast(err.message || 'Failed to load system settings.', false);
                });
        }

        saveSystemSettingsBtn?.addEventListener('click', async () => {
            const formData = new FormData();
            formData.append('maintenance_mode', maintenanceModeToggle?.checked ? '1' : '0');
            formData.append('maintenance_message', maintenanceMessage?.value.trim() || '');
            formData.append('site_banner_enabled', siteBannerToggle?.checked ? '1' : '0');
            formData.append('site_banner_message', siteBannerMessage?.value.trim() || '');

            try {
                saveSystemSettingsBtn.disabled = true;
                saveSystemSettingsBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';

                const response = await fetch('../partial/save_system_settings.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!response.ok || result.status !== 'success') {
                    throw new Error(result.message || 'Failed to save settings.');
                }

                showToast(result.message || 'System settings saved successfully.', true);
                loadSystemSettings();
            } catch (error) {
                showToast(error.message || 'Failed to save settings.', false);
            } finally {
                saveSystemSettingsBtn.disabled = false;
                saveSystemSettingsBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Settings';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            loadSystemSettings();
        });
    </script>
</body>
</html>