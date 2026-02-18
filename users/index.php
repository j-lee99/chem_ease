<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../signin.php');
    exit;
}

require_once '../partial/db_conn.php';

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

if (!$user) {
    session_destroy();
    header('Location: ../signin.php');
    exit;
}

$full_name     = $user['full_name'];
$profile_image = $user['profile_image'] ?? '';

$_SESSION['full_name']    = $full_name;
$_SESSION['profile_image'] = $profile_image;

$initials = '';
if ($full_name) {
    $name_parts = explode(' ', trim($full_name));
    foreach ($name_parts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($initials) >= 2) break;
    }
}
if (empty($initials)) $initials = 'U';

$page = $_GET['page'] ?? 'dashboard';
$valid_pages = ['dashboard', 'study-materials', 'practical-exams', 'analytics', 'forums', 'profile', 'calculator', 'periodic-table'];
if (!in_array($page, $valid_pages)) $page = 'dashboard';
$content_file = $page . '.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<body>
    <div class="loader" id="loader">
        <img src="../images/logo.png" alt="ChemEase Logo">
        <div class="loader-text">Loading...</div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../images/logo.png" alt="ChemEase Logo" width="35" height="35" class="me-2">
                ChemEase
            </a>
            <!-- Hamburger + Profile Icon (visible together only on mobile) -->
            <div class="d-flex align-items-center d-lg-none">
                <!-- Profile icon shown in top bar on mobile -->
                <div class="dropdown profile-dropdown me-3 d-block d-lg-none">
                    <div class="profile-trigger" id="profileDropdownMobile" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                        <?php if ($profile_image && file_exists('../' . $profile_image)): ?>
                            <img src="../<?php echo htmlspecialchars($profile_image); ?>?t=<?php echo time(); ?>" alt="Profile" class="profile-img">
                        <?php else: ?>
                            <div class="profile-initials"><?php echo htmlspecialchars($initials); ?></div>
                        <?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdownMobile">
                        <li><a class="dropdown-item" href="index.php?page=profile"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item logout-trigger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'study-materials' ? 'active' : ''; ?>" href="index.php?page=study-materials">
                            <i class="fas fa-book"></i> Study Materials
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'practical-exams' ? 'active' : ''; ?>" href="index.php?page=practical-exams">
                            <i class="fas fa-flask"></i> Practice Exams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'analytics' ? 'active' : ''; ?>" href="index.php?page=analytics">
                            <i class="fas fa-chart-bar"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'forums' ? 'active' : ''; ?>" href="index.php?page=forums">
                            <i class="fas fa-users"></i> Forums
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'calculator' ? 'active' : ''; ?>" href="index.php?page=calculator">
                            <i class="fas fa-calculator"></i> Calculator
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'periodic-table' ? 'active' : ''; ?>" href="index.php?page=periodic-table">
                            <i class="fas fa-table"></i> Periodic Table
                        </a>
                    </li>
                </ul>
                <!-- Original profile dropdown (visible only on desktop/tablet when expanded) -->
                <div class="dropdown profile-dropdown d-none d-lg-flex">
                    <div class="profile-trigger" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                        <?php if ($profile_image && file_exists('../' . $profile_image)): ?>
                            <img src="../<?php echo htmlspecialchars($profile_image); ?>?t=<?php echo time(); ?>" alt="Profile" class="profile-img">
                        <?php else: ?>
                            <div class="profile-initials"><?php echo htmlspecialchars($initials); ?></div>
                        <?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="index.php?page=profile"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item logout-trigger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to log out of ChemEase?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="https://chemease.site/" class="btn btn-danger">Yes, Logout</a>
                </div>
            </div>
        </div>
    </div>

    <main class="container" style="margin-top: 90px; padding-bottom: 2rem;">
        <?php
        if (file_exists($content_file)) {
            include $content_file;
        } else {
            echo '<p class="text-danger">Page not found!</p>';
        }
        ?>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                const loader = document.getElementById('loader');
                loader.classList.add('fade-out');
                setTimeout(() => loader.style.display = 'none', 500);
            }, 1200);
        });
    </script>
</body>

</html>