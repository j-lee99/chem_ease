<?php
session_start();
require_once '../partial/db_conn.php';
// If NOT logged in OR not admin → back to login
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
            echo "ChemEase Super Admin Panel - Discussion Forum";
        } elseif ($isAdmin) {
            echo "ChemEase Admin Panel - Discussion Forum";
        } else {
            echo "ChemEase - Discussion Forum";
        }
        ?>
    </title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="forum.css">
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
        .avatar-wrapper {
            position: relative;
            width: 50px;
            height: 50px;
            flex-shrink: 0;
        }

        .post-avatar,
        .post-avatar-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
            background-color: #6c757d;
        }

        .post-avatar-img {
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .reply-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
        }

        .reply-avatar.initials {
            background-color: #17a2b8;
            color: white;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
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
            <div class="nav-item"><a href="Users.php" class="nav-link"><i class="fas fa-users"></i><span>Users</span></a></div>
            <?php if ($isAdmin): ?>
                <div class="nav-item"><a href="Learning_Material.php" class="nav-link"><i class="fas fa-book"></i><span>Learning Materials</span></a></div>
                <div class="nav-item"><a href="Practice_Exams.php" class="nav-link"><i class="fas fa-clipboard-list"></i><span>Practice Exams</span></a></div>
            <?php endif; ?>
              <?php if ($isSuperAdmin || $isAdmin): ?>
                <div class="nav-item">
                    <a href="Discussion_Forums.php" class="nav-link active">
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="forum-container">
            <div class="forum-header">
                <h1 class="forum-title">Discussion Forum</h1>
                <div class="stats-row">
                    <?php
                    $total = $conn->query("SELECT COUNT(*) FROM forum_threads")->fetch_row()[0];
                    // FIXED: Only count non-flagged open threads (matches what users see)
                    $active = $conn->query("SELECT COUNT(*) FROM forum_threads WHERE is_closed = 0 AND is_flagged = 0")->fetch_row()[0];
                    $flagged = $conn->query("SELECT COUNT(*) FROM forum_threads WHERE is_flagged = 1")->fetch_row()[0];
                    ?>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-label">Total Posts</div>
                            <div class="stat-number"><?= $total ?></div>
                        </div>
                        <div class="stat-icon blue"><i class="fas fa-comments"></i></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-label">Active Discussions</div>
                            <div class="stat-number"><?= $active ?></div>
                        </div>
                        <div class="stat-icon green"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <div class="stat-label">Flagged Posts</div>
                            <div class="stat-number"><?= $flagged ?></div>
                        </div>
                        <div class="stat-icon red"><i class="fas fa-flag"></i></div>
                    </div>
                </div>
            </div>

            <div class="forum-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search discussions..." id="adminSearch">
                </div>
            </div>

            <div class="forum-tabs">
                <button class="forum-tab active" data-filter="all">ALL POSTS</button>
                <button class="forum-tab" data-filter="BioChemistry">BioChemistry</button>
                <button class="forum-tab" data-filter="Organic Chemistry">Organic Chemistry</button>
                <button class="forum-tab" data-filter="Physical Chemistry">Physical Chemistry</button>
                <button class="forum-tab" data-filter="Analytical Chemistry">Analytical Chemistry</button>
                <button class="forum-tab" data-filter="Inorganic Chemistry">Inorganic Chemistry</button>
                <button class="forum-tab" data-filter="Exam Preparation">Exam Preparation</button>
                <button class="forum-tab" data-filter="flagged">FLAGGED</button>
                <button class="forum-tab" data-filter="closed">CLOSED</button>
            </div>

            <div class="forum-posts" id="adminForumPosts">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3 text-muted">Loading discussions...</p>
                </div>
            </div>

            <div class="text-center mt-4">
                <button class="btn btn-primary" id="loadMoreBtn" style="display:none;">Load More</button>
            </div>
        </div>
    </div>

    <!-- View Thread Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Thread Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="threadViewer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        let adminOffset = 0;
        let adminCurrentFilter = 'all';
        let adminSearchTerm = '';

        // Generate avatar: image or initials
        function generateAvatar(fullName, profileImage) {
            const initials = fullName.split(' ').map(n => n[0]?.toUpperCase() || '').join('').substring(0, 2) || 'AD';
            if (profileImage && profileImage.trim() !== '') {
                return `<img src="../${profileImage}?t=${Date.now()}" class="post-avatar-img" alt="${fullName}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">`;
            }
            return `<div class="post-avatar">${initials}</div>`;
        }

        function generateReplyAvatar(fullName, profileImage) {
            const initials = fullName.split(' ').map(n => n[0]?.toUpperCase() || '').join('').substring(0, 2) || 'U';
            if (profileImage && profileImage.trim() !== '') {
                return `<img src="../${profileImage}?t=${Date.now()}" class="reply-avatar" alt="${fullName}" onerror="this.src=''; this.className='reply-avatar initials'; this.textContent='${initials}';">`;
            }
            return `<div class="reply-avatar initials">${initials}</div>`;
        }

        function loadAdminThreads(replace = true) {
            let url = `../partial/forum_handler.php?action=get_threads_admin&filter=${adminCurrentFilter}&search=${adminSearchTerm}&offset=${adminOffset}&limit=15`;

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('adminForumPosts');
                    if (replace) {
                        container.innerHTML = '';
                        adminOffset = 0;
                    }

                    if (data.length === 0 && replace) {
                        container.innerHTML = '<div class="text-center py-5"><p class="text-muted">No discussions found.</p></div>';
                        document.getElementById('loadMoreBtn').style.display = 'none';
                        return;
                    }

                    let renderedCount = 0;

                    data.forEach(thread => {
                        const flagged = thread.is_flagged ? `<span class="flagged-tag">Flagged</span>` : '';
                        const closed = thread.is_closed ? `<span class="badge bg-secondary ms-2">Closed</span>` : '';
                        const catClass = (thread.category || '').replace(/ /g, '').toLowerCase();

                        container.innerHTML += `
                        <div class="forum-post">
                            <div class="avatar-wrapper">
                                ${generateAvatar(thread.full_name, thread.profile_image)}
                            </div>
                            <div class="post-content clickable-content" onclick="viewThread(${thread.id})">
                                <div class="post-title">
                                    ${thread.title}
                                    <span class="subject-tag ${catClass}">${thread.category || ''}</span>
                                    ${flagged}${closed}
                                </div>
                                <div class="post-meta">
                                    Posted by <strong>${thread.full_name}</strong> · ${thread.replies || 0} replies · ${thread.views || 0} views · ${thread.likes_count || 0} likes ·
                                    <small>${new Date(thread.created_at).toLocaleString()}</small>
                                </div>
                            </div>
                            <div class="post-actions">
                                <button class="btn btn-sm btn-outline-danger flag-btn"
                                        data-id="${thread.id}"
                                        data-flag="${thread.is_flagged ? 0 : 1}">
                                    ${thread.is_flagged ? 'Unflag' : 'Flag'}
                                </button>
                            </div>
                        </div>`;

                        renderedCount++;
                    });

                    adminOffset += data.length;

                    // Show/hide load more button
                    document.getElementById('loadMoreBtn').style.display = data.length >= 15 ? 'block' : 'none';
                });
        }

        function viewThread(id) {
            fetch(`../partial/forum_handler.php?action=view_thread&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    let html = `
                    <div class="p-4">
                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="avatar-wrapper">
                                ${generateAvatar(data.thread.full_name, data.thread.profile_image)}
                            </div>
                            <div class="flex-grow-1">
                                <h4>${data.thread.title}</h4>
                                <p class="text-muted mb-3">Posted by <strong>${data.thread.full_name}</strong> on ${new Date(data.thread.created_at).toLocaleString()}</p>
                                <div class="bg-light p-4 rounded mb-4">
                                    ${data.thread.content.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h5>Replies (${data.replies.length})</h5>`;

                    data.replies.forEach(r => {
                        html += `
                        <div class="d-flex gap-3 py-3 border-bottom">
                            ${generateReplyAvatar(r.full_name, r.profile_image)}
                            <div class="flex-grow-1">
                                <strong>${r.full_name}</strong>
                                <small class="text-muted d-block mb-2">${new Date(r.created_at).toLocaleString()}</small>
                                <div>${r.content.replace(/\n/g, '<br>')}</div>
                            </div>
                        </div>`;
                    });

                    html += `</div>`;
                    document.getElementById('threadViewer').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('viewModal')).show();
                });
        }

        // Tabs
        document.querySelectorAll('.forum-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.forum-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                adminCurrentFilter = tab.dataset.filter;
                adminOffset = 0;
                loadAdminThreads();
            });
        });

        // Search
        document.getElementById('adminSearch').addEventListener('input', e => {
            adminSearchTerm = e.target.value;
            adminOffset = 0;
            loadAdminThreads();
        });

        // Load More
        document.getElementById('loadMoreBtn').addEventListener('click', () => {
            loadAdminThreads(false);
        });

        // Flag/Unflag
        document.getElementById('adminForumPosts').addEventListener('click', e => {
            const btn = e.target.closest('.flag-btn');
            if (btn) {
                e.stopPropagation();
                const id = btn.dataset.id;
                const flag = btn.dataset.flag;
                fetch('../partial/forum_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=flag_thread&thread_id=${id}&flag=${flag}`
                }).then(() => {
                    adminOffset = 0;
                    loadAdminThreads(true);
                });
            }
        });

        // Sidebar collapse
        document.querySelector('.collapse-btn').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.top-navbar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('collapsed');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-chevron-left');
            icon.classList.toggle('fa-chevron-right');
        });

        // Load on start
        loadAdminThreads();
    </script>
</body>

</html>