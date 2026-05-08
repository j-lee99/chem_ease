<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = (string)($_SESSION['role'] ?? '');
$isGuestUser = ($role === 'guest');

if (!in_array($role, ['user', 'guest'], true)) {
    header('Location: ../signin.php');
    exit;
}

require_once '../partial/db_conn.php';

$user_id = (isset($_SESSION['user_id']) && ctype_digit((string)$_SESSION['user_id']))
    ? (int)$_SESSION['user_id']
    : 0;

$user = null;
if (!$isGuestUser) {
    if ($user_id <= 0) {
        header('Location: ../signin.php');
        exit;
    }

    $stmt = $conn->prepare("
        SELECT full_name, profile_image 
        FROM users 
        WHERE id = ? AND is_deleted = 0
    "
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        session_destroy();
        header('Location: ../signin.php');
        exit;
    }
}

$full_name     = $isGuestUser ? 'Guest User' : ($user['full_name'] ?? 'Student');
$profile_image = $isGuestUser ? '' : ($user['profile_image'] ?? '');

$_SESSION['full_name']     = $full_name;
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

$page = 'dashboard';
?>

<style>
    :root {
        --primary-blue: #17a2b8;
        --light-blue: #e8f4f8;
        --dark-text: #2c3e50;
        --light-gray: #f8f9fa;
        --input-bg: rgba(255, 255, 255, 0.9);
        --success-color: #28A745;
        --warning-color: #FFC107;
        --danger-color: #DC3545;
        --purple-color: #6F42C1;
        --gradient-start: #17a2b8;
        --gradient-end: #20c5d4;
    }

    .welcome-section {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.1);
    }

    .progress-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(23, 162, 184, 0.05);
        border: none;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        border: 1px solid rgba(23, 162, 184, 0.1);
        height: 100%;
        min-height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .stat-icon {
        font-size: 2rem;
        margin-bottom: 0.75rem;
    }

    .stat-number {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: var(--primary-blue);
    }

    .stat-label {
        font-size: 0.9rem;
        color: var(--dark-text);
        font-weight: 500;
        opacity: 0.8;
        margin-top: auto;
    }

    .progress-item {
        margin: 1rem 0;
        padding: 1rem;
        background: white;
        border-radius: 8px;
        border: 1px solid rgba(23, 162, 184, 0.1);
    }

    .progress {
        background-color: rgba(23, 162, 184, 0.1);
        border-radius: 8px;
        height: 6px;
    }

    .progress-bar {
        border-radius: 8px;
        background: var(--primary-blue);
        transition: width 1s ease;
    }

    .activity-item {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(23, 162, 184, 0.1);
        position: relative;
    }

    .activity-item h6 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .activity-item p {
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }

    .performance-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        margin-bottom: 1rem;
        background: white;
        border-radius: 8px;
        border: 1px solid rgba(23, 162, 184, 0.1);
    }

    .subject-icon {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .analytical-chemistry {
        background: var(--primary-blue);
    }

    .organic-chemistry {
        background: var(--success-color);
    }

    .physical-chemistry {
        background: var(--purple-color);
    }

    .inorganic-chemistry {
        background: var(--warning-color);
    }

    .biochemistry {
        background: var(--danger-color);
    }

    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: 4px;
    }

    @keyframes loading {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    @media (max-width: 768px) {
        .welcome-section {
            padding: 1.5rem;
        }

        .progress-card {
            padding: 1rem;
        }

        .stat-card {
            min-height: 130px;
            padding: 1rem 0.75rem;
        }

        .stat-number {
            font-size: 1.6rem;
        }

        .stat-label {
            font-size: 0.85rem;
        }
    }

    .lb-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(23, 162, 184, 0.06);
        border: 1px solid rgba(23, 162, 184, 0.18);
        overflow: hidden;
    }

    .lb-card .lb-header {
        padding: 1.2rem 1.25rem 0.75rem;
    }

    .lb-title {
        font-weight: 700;
        font-size: 1.15rem;
        color: #243b53;
        margin: 0;
    }

    .lb-top {
        padding: 0 1.25rem 1rem;
    }

    .lb-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 12px;
        border: 1px solid rgba(23, 162, 184, 0.16);
        background: #f8fbfd;
        margin-bottom: 0.6rem;
    }

    .lb-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }

    .lb-badge {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }

    .lb-badge.rank-1 {
        background: #f59e0b;
    }

    .lb-badge.rank-2 {
        background: #22c55e;
    }

    .lb-badge.rank-3 {
        background: #f97316;
    }

    .lb-avatar {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: #0f172a;
        background: #e8f4f8;
        border: 1px solid rgba(23, 162, 184, 0.25);
        flex-shrink: 0;
    }

    .lb-avatar.lb-avatar-sm {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
    }


    .lb-name {
        font-weight: 700;
        color: #243b53;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }


    .lb-name-private {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        color: #334155;
    }

    .lb-avatar-private {
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.14), rgba(32, 197, 212, 0.2));
        color: #0f4f5c;
    }

    .lb-guest-privacy-note {
        display: block;
        margin-top: 0.35rem;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .lb-points {
        font-weight: 800;
        color: #fff;
        background: #1e3a8a;
        padding: 0.35rem 0.65rem;
        border-radius: 8px;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .lb-footer {
        padding: 0.95rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        background: linear-gradient(135deg, #0b3b5c, #0f766e);
        color: #fff;
    }

    .lb-rank-text {
        font-weight: 700;
        opacity: 0.95;
    }

    .lb-btn {
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.22);
        color: #fff;
        font-weight: 700;
        border-radius: 10px;
        padding: 0.55rem 0.9rem;
        transition: all 0.15s ease;
        white-space: nowrap;
    }

    .lb-btn:hover {
        background: rgba(255, 255, 255, 0.22);
        transform: translateY(-1px);
    }

    .lb-table thead th {
        font-size: 0.8rem;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #475569;
        background: #f1f5f9;
        border-bottom: 1px solid rgba(2, 6, 23, 0.08);
    }

    .lb-table tbody tr:nth-child(odd) {
        background: #ecfeff;
    }

    .lb-table td {
        vertical-align: middle;
    }

    .lb-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        border: 1px solid rgba(23, 162, 184, 0.25);
        background: #f8fbfd;
        font-weight: 700;
        color: #0f172a;
        font-size: 0.85rem;
    }

    .lb-pagination-btn {
        border-radius: 10px;
        font-weight: 700;
    }


    .guest-preview-note {
        background: rgba(23, 162, 184, 0.08);
        border: 1px solid rgba(23, 162, 184, 0.18);
        color: #0f4f5c;
        border-radius: 12px;
        padding: 0.9rem 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .guest-locked-action {
        opacity: 0.75;
        cursor: not-allowed !important;
        pointer-events: none;
    }

    .guest-chart-bars {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 0.75rem;
        align-items: end;
        height: 180px;
        padding: 1rem;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.05), rgba(32, 197, 212, 0.09));
        border: 1px solid rgba(23, 162, 184, 0.12);
    }

    .guest-chart-bar {
        display: flex;
        flex-direction: column;
        justify-content: end;
        align-items: center;
        gap: 0.5rem;
        height: 100%;
        min-width: 0;
    }

    .guest-chart-fill {
        width: 100%;
        max-width: 44px;
        border-radius: 10px 10px 4px 4px;
        background: linear-gradient(180deg, var(--gradient-end), var(--primary-blue));
        box-shadow: 0 8px 18px rgba(23, 162, 184, 0.18);
    }

    .guest-chart-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #475569;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    /* Guest dashboard restriction blur: keep leaderboard visible, lock personal analytics. */
    .guest-blur-target {
        position: relative;
        overflow: hidden;
        border-radius: 14px;
        min-height: 120px;
    }

    .guest-blur-target > * {
        filter: blur(5px);
        opacity: 0.58;
        pointer-events: none !important;
        user-select: none;
    }

    .guest-blur-target::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: 8;
        background: linear-gradient(135deg, rgba(255,255,255,0.45), rgba(232,244,248,0.82));
        backdrop-filter: blur(1px);
    }

    .guest-blur-target::after {
        content: attr(data-guest-message);
        position: absolute;
        z-index: 9;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: min(92%, 430px);
        padding: 1rem 1.15rem;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(23, 162, 184, 0.26);
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.12);
        color: #0f4f5c;
        font-weight: 800;
        text-align: center;
        line-height: 1.45;
    }

    .guest-blur-target.guest-blur-small::after {
        font-size: 0.92rem;
        padding: 0.8rem 1rem;
    }

</style>
<div class="row g-4 align-items-stretch">
    <div class="col-lg-8">
        <div class="welcome-section">
            <h1 class="mb-2"><?php echo $isGuestUser ? 'Welcome, Guest!' : 'Welcome back, ' . htmlspecialchars($_SESSION['full_name'] ?? 'Student') . '!'; ?></h1>
            <p class="mb-0 opacity-75"><?php echo $isGuestUser ? 'Preview the leaderboard and dashboard insights. Sign in to track your own rank and progress.' : 'Track your progress and continue your chemistry learning journey.'; ?></p>
        </div>

        <!-- Progress Overview -->
        <div class="card progress-card">
            <div class="card-body">
                <h4 class="card-title mb-4">Your Progress Overview</h4>

                <div class="row mb-4" id="statsContainer">
                    <div class="col-md-3 col-6">
                        <div class="stat-card">
                            <i class="fas fa-trophy text-warning mb-2" style="font-size: 1.5rem;"></i>
                            <div class="stat-number text-warning" id="overallCompletion">--</div>
                            <div class="stat-label">Overall Completion</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card">
                            <i class="fas fa-fire text-danger mb-2" style="font-size: 1.5rem;"></i>
                            <div class="stat-number text-danger" id="studyStreak">--</div>
                            <div class="stat-label">Study Streak</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card">
                            <i class="fas fa-clock text-info mb-2" style="font-size: 1.5rem;"></i>
                            <div class="stat-number text-info" id="daysActive">--</div>
                            <div class="stat-label">Days Active</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card">
                            <i class="fas fa-check-circle text-success mb-2" style="font-size: 1.5rem;"></i>
                            <div class="stat-number text-success" id="topicsCompleted">--</div>
                            <div class="stat-label">Topics Completed</div>
                        </div>
                    </div>
                </div>

                <h6 class="mb-3">Progress by Category</h6>
                <div id="progressContainer">
                    <div class="skeleton" style="height: 60px; margin-bottom: 1rem;"></div>
                    <div class="skeleton" style="height: 60px; margin-bottom: 1rem;"></div>
                </div>
            </div>
        </div>


    </div>
    <div class="col-lg-4">
        <div class="h-100">
            <!-- Leaderboard (Top Reviewees + Full Table Modal) -->
            <div class="card progress-card p-0" style="border:none; box-shadow:none; background: transparent;">
                <div class="lb-card">
                    <div class="lb-header">
                        <h5 class="lb-title">Top Reviewees</h5>
                        <?php if ($isGuestUser): ?>
                            <small class="lb-guest-privacy-note">
                                <i class="fas fa-user-shield me-1"></i>Names are hidden in guest preview.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="lb-top" id="lbTop3">
                        <div class="skeleton" style="height: 58px; margin-bottom: 0.6rem;"></div>
                        <div class="skeleton" style="height: 58px; margin-bottom: 0.6rem;"></div>
                        <div class="skeleton" style="height: 58px; margin-bottom: 0.6rem;"></div>
                    </div>

                    <div class="lb-footer">
                        <div class="lb-rank-text" id="lbMyRankText"><?php echo $isGuestUser ? 'Guest preview: rank hidden' : 'Your Rank: --'; ?></div>
                        <?php if ($isGuestUser): ?>
                            <button type="button" class="lb-btn guest-locked-action" title="Sign in to view the full leaderboard">
                                Full Leaderboard Locked
                            </button>
                        <?php else: ?>
                            <button type="button" class="lb-btn" data-bs-toggle="modal" data-bs-target="#leaderboardModal">
                                View Full Leaderboard
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>


<div class="row">
    <!-- Recent Activities -->
    <div class="col-lg-6">
        <div class="card progress-card">
            <div class="card-body">
                <h5 class="card-title mb-3">Recent Activities</h5>
                <p class="text-muted small mb-3">Your learning progress over the past few days</p>
                <div id="activitiesContainer">
                    <div class="skeleton" style="height: 80px; margin-bottom: 1rem;"></div>
                    <div class="skeleton" style="height: 80px; margin-bottom: 1rem;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="col-lg-6">
        <div class="card progress-card">
            <div class="card-body">
                <h5 class="card-title mb-3">Performance Metrics</h5>
                <p class="text-muted small mb-3">Your exam scores by category</p>
                <div id="performanceContainer">
                    <div class="skeleton" style="height: 70px; margin-bottom: 1rem;"></div>
                    <div class="skeleton" style="height: 70px; margin-bottom: 1rem;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="leaderboardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(2,6,23,0.08);">
                <h5 class="modal-title fw-bold">Leaderboard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Top 3 strip -->
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-start mb-3" id="lbModalTop3">
                    <div class="skeleton" style="height: 34px; width: 100%; max-width: 520px;"></div>
                </div>

                <div class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" class="form-control" id="lbSearch" placeholder="Search name..." />
                        </div>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2 justify-content-md-end">
                        <button class="btn btn-outline-secondary lb-pagination-btn" id="lbPrevBtn" type="button">Prev</button>
                        <button class="btn btn-outline-secondary lb-pagination-btn" id="lbNextBtn" type="button">Next</button>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table lb-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 90px;">Rank</th>
                                <th>Name</th>
                                <th style="width: 140px;">Points</th>
                            </tr>
                        </thead>
                        <tbody id="lbTbody">
                            <tr>
                                <td colspan="3">
                                    <div class="skeleton" style="height: 44px;"></div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <div class="skeleton" style="height: 44px;"></div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <div class="skeleton" style="height: 44px;"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer" style="border-top: 1px solid rgba(2,6,23,0.08); background: linear-gradient(135deg, #0b3b5c, #0f766e);">
                <div class="me-auto text-white fw-bold" id="lbModalMyRankText">Your Rank: --</div>
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal" style="border-radius: 10px; padding: 0.55rem 1.2rem;">Back</button>
            </div>
        </div>
    </div>
</div>

<script>
    const IS_GUEST_DASHBOARD = <?php echo $isGuestUser ? 'true' : 'false'; ?>;
    const LB_CURRENT_USER_ID = <?php echo json_encode($isGuestUser ? 0 : (int)($_SESSION['user_id'] ?? 0)); ?>;
    const LB_CURRENT_USER_NAME = <?php echo json_encode($isGuestUser ? '' : (string)($_SESSION['full_name'] ?? '')); ?>;
    
    function lbGet(obj, keys, fallback = null) {
        for (const k of keys) {
            if (obj && Object.prototype.hasOwnProperty.call(obj, k) && obj[k] !== null && obj[k] !== undefined) {
                return obj[k];
            }
        }
        return fallback;
    }

    function lbPickUsers(payload) {
        return (
            lbGet(payload?.leaderboard, ['users'], null) ||
            lbGet(payload, ['users', 'data', 'rows', 'items'], []) ||
            lbGet(payload?.data, ['users', 'rows', 'items'], []) || []
        );
    }

    function lbPickTotal(payload, usersLen) {
        return (
            lbGet(payload?.leaderboard, ['total'], null) ??
            lbGet(payload, ['total', 'total_users', 'totalUsers', 'count'], null) ??
            lbGet(payload?.pagination, ['total', 'count'], null) ??
            usersLen
        );
    }

   function lbPickName(u) {
        return lbGet(u, ['full_name', 'name', 'fullname', 'display_name', 'username'], 'Unknown');
    }

    function lbPickId(u) {
        return lbGet(u, ['id', 'user_id', 'uid'], null);
    }

    function lbPickPoints(u) {
        const pts = lbGet(u, ['points', 'score', 'total_points', 'total_score', 'pts'], 0);
        const n = Number(pts);
        return Number.isFinite(n) ? n : 0;
    }


    function lbPickProfilePic(u) {
        return lbGet(u, ['profile_pic'], null);
    }
    
    function lbResolveProfilePicUrl(profilePic) {
        if (!profilePic) return null;
        let pic = String(profilePic).trim();
        if (!pic) return null;
        if (/^https?:\/\//i.test(pic)) return pic;
        pic = pic.replace(/\\/g, "/");
        const idx = pic.indexOf("/uploads/");
        if (idx !== -1) return `${window.location.origin}${pic.slice(idx)}`;
        if (pic.startsWith("/")) return `${window.location.origin}${pic}`;
        if (pic.startsWith("uploads/")) return `${window.location.origin}/${pic}`;
        return `${window.location.origin}/${pic}`;
    }


    function lbAvatarHTML(name, profilePicPath, extraClasses = '') {
        const initials = lbInitials(name);
        const resolved = lbResolveProfilePicUrl(profilePicPath);
        if (!resolved) return `<div class="lb-avatar ${extraClasses}" aria-hidden="true">${initials}</div>`;
        return `
            <div class="lb-avatar ${extraClasses}" aria-hidden="true" data-initials="${initials}">
                <img src="${resolved}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:999px;display:block;"
                    onerror="this.closest('.lb-avatar')?.replaceChildren(document.createTextNode(this.closest('.lb-avatar').dataset.initials || ''))" />
            </div>
        `.trim();
    }

    function lbPrivateDisplayName(name, rank = null) {
        if (!IS_GUEST_DASHBOARD) return name;
        return rank ? `Reviewee #${rank}` : 'Reviewee';
    }

    function lbPrivateNameHTML(name, rank = null) {
        const displayName = lbPrivateDisplayName(name, rank);
        if (!IS_GUEST_DASHBOARD) return displayName;
        return `<span class="lb-name-private"><i class="fas fa-user-shield"></i>${displayName}</span>`;
    }

    function lbPrivateAvatarHTML(name, profilePicPath, extraClasses = '', rank = null) {
        if (!IS_GUEST_DASHBOARD) return lbAvatarHTML(name, profilePicPath, extraClasses);
        return `<div class="lb-avatar ${extraClasses} lb-avatar-private" aria-hidden="true"><i class="fas fa-user-lock"></i></div>`;
    }


    function lbInitials(name) {
        const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        const a = (parts[0] || '').charAt(0);
        const b = (parts[1] || '').charAt(0);
        return (a + b).toUpperCase() || 'U';
    }

    function lbHasUsableId(value) {
        return value !== null && value !== undefined && value !== '' && !Number.isNaN(Number(value));
    }
    
    function lbNormalizeName(name) {
        return String(name || '').trim().toLowerCase().replace(/\s+/g, ' ');
    }

    function lbIsCurrentUser(u) {
        if (IS_GUEST_DASHBOARD) return false;
        const rowId = lbPickId(u);
        const rowName = lbPickName(u);
        const hasCurrentUserId = lbHasUsableId(LB_CURRENT_USER_ID);
        const hasRowId = lbHasUsableId(rowId);
        if (hasCurrentUserId && hasRowId) return Number(rowId) === Number(LB_CURRENT_USER_ID);
        if (!hasCurrentUserId && !hasRowId) return lbNormalizeName(rowName) === lbNormalizeName(LB_CURRENT_USER_NAME);
        return false;
    }
    
    function lbFormatPoints(n) {
        try { return new Intl.NumberFormat().format(n); } catch { return String(n); }
    }

   async function lbFetchUsers({ page = 1, limit = 10, search = '' } = {}) {
        const url = `../partial/get_dashboard_data.php?mode=leaderboard&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`;
        const res = await fetch(url);
        return await res.json();
    }
    
    function computeDenseRanks(users) {
        const ranks = {};
        let lastPoints = null;
        let currentRank = 0;
        let displayRank = 0;
        const sorted = [...users].sort((a,b) => lbPickPoints(b) - lbPickPoints(a));
        sorted.forEach(u => {
            const pts = lbPickPoints(u);
            if (pts !== lastPoints) displayRank = currentRank + 1;
            ranks[lbPickId(u)] = displayRank;
            lastPoints = pts;
            currentRank++;
        });
        return ranks;
    }

     function lbRenderTable(users, { page, limit, total, precomputedRanks = {} }) {
        const tbody = document.getElementById('lbTbody');
        if (!tbody) return;
        if (!users || users.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3"><div class="empty-state" style="padding: 1.5rem 0.5rem;"><i class="fas fa-users"></i><p class="mb-0">No users found.</p></div></td></tr>`;
            return;
        }
    
        tbody.innerHTML = users.map(u => {
            const userId = lbPickId(u);
            const rank = precomputedRanks[userId] ?? '?';
            const name = lbPickName(u);
            const pts = lbPickPoints(u);
            const isMe = lbIsCurrentUser(u);
            return `
                <tr ${isMe ? 'style="outline: 2px solid rgba(23, 162, 184, 0.35);"' : ''}>
                    <td class="fw-bold">${rank}</td>
                    <td><div class="d-flex align-items-center gap-2">${lbPrivateAvatarHTML(name, lbPickProfilePic(u), "lb-avatar-sm", rank)}<span>${isMe ? '<span class="fw-bold">Mine</span>' : lbPrivateNameHTML(name, rank)}</span></div></td>
                    <td class="fw-bold">${lbFormatPoints(pts)} pts</td>
                </tr>
            `;
        }).join('');
    }
    
    function lbRenderTop3(users) {
        const el = document.getElementById('lbTop3');
        if (!el) return;
        if (!users || users.length === 0) {
            el.innerHTML = '';
            return;
        }
    
        el.innerHTML = users.slice(0, 3).map((u, idx) => {
            const rank = idx + 1;
            const name = lbPickName(u);
            const pts = lbPickPoints(u);
            const badgeClass = ['rank-1','rank-2','rank-3'][idx] || '';
            return `
                <div class="lb-row">
                    <div class="lb-left">
                        <div class="lb-badge ${badgeClass}">${rank}</div>
                        ${lbPrivateAvatarHTML(name, lbPickProfilePic(u), '', rank)}
                        <div class="lb-name">${lbPrivateNameHTML(name, rank)}</div>
                    </div>
                    <div class="lb-points">${lbFormatPoints(pts)} pts</div>
                </div>
            `;
        }).join('');
    }

    function lbRenderModalTop3(users) {
        const el = document.getElementById('lbModalTop3');
        if (!el) return;
        if (!users || users.length === 0) {
            el.innerHTML = '';
            return;
        }
        const top3 = users.slice(0, 3);
        el.innerHTML = top3.map((u, idx) => {
            const rank = idx + 1;
            const name = lbPickName(u);
            const pts = lbPickPoints(u);
            return `
                    <span class="lb-pill">
                        <span class="fw-bold">#${rank}</span>
                        <span>${lbPrivateNameHTML(name, rank)}</span>
                        <span class="text-muted">•</span>
                        <span>${lbFormatPoints(pts)} pts</span>
                    </span>
                `;
        }).join('');
    }

    function lbRenderTable(users, {
        page,
        limit,
        total
    }) {
        const tbody = document.getElementById('lbTbody');
        if (!tbody) return;

        if (!users || users.length === 0) {
            tbody.innerHTML = `
                    <tr>
                        <td colspan="3">
                            <div class="empty-state" style="padding: 1.5rem 0.5rem;">
                                <i class="fas fa-users"></i>
                                <p class="mb-0">No users found.</p>
                            </div>
                        </td>
                    </tr>
                `;
            return;
        }

        tbody.innerHTML = users.map((u, i) => {
            const rank = (page - 1) * limit + i + 1;
            const name = lbPickName(u);
            const pts = lbPickPoints(u);
            const isMe = lbIsCurrentUser(u);

            return `
                    <tr ${isMe ? 'style="outline: 2px solid rgba(23, 162, 184, 0.35);"' : ''}>
                        <td class="fw-bold">${rank}</td>
                        <td><div class="d-flex align-items-center gap-2">${lbPrivateAvatarHTML(name, lbPickProfilePic(u), "lb-avatar-sm", rank)}<span>${isMe ? '<span class="fw-bold">Mine</span>' : lbPrivateNameHTML(name, rank)}</span></div></td>
                        <td class="fw-bold">${lbFormatPoints(pts)} pts</td>
                    </tr>
                `;
        }).join('');
    }

    async function lbComputeMyRank(totalUsers, limit = 50) {
        if (!LB_CURRENT_USER_ID && !LB_CURRENT_USER_NAME) return {
            rank: null,
            total: totalUsers
        };
        const maxPages = 25; // keep it bounded
        let page = 1;
        while (page <= maxPages) {
            const payload = await lbFetchUsers({
                page,
                limit,
                search: ''
            });
            const users = lbPickUsers(payload);
            if (!users || users.length === 0) break;

            for (let i = 0; i < users.length; i++) {
                const u = users[i];
                if (lbIsCurrentUser(u)) {
                    return {
                        rank: (page - 1) * limit + i + 1,
                        total: lbPickTotal(payload, totalUsers)
                    };
                }
            }
            page += 1;
        }
        return {
            rank: null,
            total: totalUsers
        };
    }

      function lbSetRankTexts(rank, total) {
        const text = IS_GUEST_DASHBOARD
            ? `Guest preview: rank hidden`
            : (rank ? `Your Rank: #${rank} / ${total}` : `Your Rank: -- / ${total || '--'}`);
        const el1 = document.getElementById('lbMyRankText');
        const el2 = document.getElementById('lbModalMyRankText');
        if (el1) el1.textContent = text;
        if (el2) el2.textContent = text;
    }

    let LB_STATE = {
        page: 1,
        limit: 10,
        search: '',
        total: 0,
        top3: []
    };
    let lbSearchTimer = null;

    async function lbInitDashboardLeaderboard() {
        try {
            // Top 3 + total
            const topPayload = await lbFetchUsers({
                page: 1,
                limit: 3,
                search: ''
            });
            const topUsers = lbGet(topPayload?.leaderboard, ['top3'], null) || lbPickUsers(topPayload);
            const total = lbPickTotal(topPayload, topUsers.length);
            LB_STATE.total = total;
            LB_STATE.top3 = topUsers;

            lbRenderTop3(topUsers);
            lbRenderModalTop3(topUsers);

            if (IS_GUEST_DASHBOARD) {
                lbSetRankTexts(null, total);
            } else {
                const apiRankRaw = lbGet(topPayload?.leaderboard, ['my_rank'], null);
                const apiRank = Number(apiRankRaw);

                if (Number.isFinite(apiRank) && apiRank > 0) {
                    lbSetRankTexts(apiRank, total);
                } else {
                    const {
                        rank,
                        total: t
                    } = await lbComputeMyRank(total);
                    lbSetRankTexts(rank, t);
                }
            }

        } catch (e) {
            console.error('Leaderboard init error:', e);
            const top = document.getElementById('lbTop3');
            if (top) top.innerHTML = '<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><p>Failed to load leaderboard.</p></div>';
        }
    }

    // async function lbLoadModalPage() {
    //     const {
    //         page,
    //         limit,
    //         search
    //     } = LB_STATE;
    //     const tbody = document.getElementById('lbTbody');
    //     if (tbody) {
    //         tbody.innerHTML = `
    //                 <tr><td colspan="3"><div class="skeleton" style="height: 44px;"></div></td></tr>
    //                 <tr><td colspan="3"><div class="skeleton" style="height: 44px;"></div></td></tr>
    //                 <tr><td colspan="3"><div class="skeleton" style="height: 44px;"></div></td></tr>
    //             `;
    //     }
    //     const payload = await lbFetchUsers({
    //         page,
    //         limit,
    //         search
    //     });
    //     const users = lbPickUsers(payload);
    //     const total = lbPickTotal(payload, users.length);
    //     LB_STATE.total = total;
    //     lbRenderTable(users, {
    //         page,
    //         limit,
    //         total
    //     });

    //     // Button states
    //     const prev = document.getElementById('lbPrevBtn');
    //     const next = document.getElementById('lbNextBtn');
    //     const maxPage = Math.max(1, Math.ceil(total / limit));
    //     if (prev) prev.disabled = page <= 1;
    //     if (next) next.disabled = page >= maxPage;
    // }
    
    async function lbLoadModalPage() {
        const { page, limit, search } = LB_STATE;
        const tbody = document.getElementById('lbTbody');
        if (tbody) tbody.innerHTML = Array(3).fill('<tr><td colspan="3"><div class="skeleton" style="height:44px"></div></td></tr>').join('');
    
        const payload = await lbFetchUsers({ page, limit, search });
        // console.log(payload);
        const users = lbPickUsers(payload);
        const total = lbPickTotal(payload, users.length);
        LB_STATE.total = total;
    
        const precomputedRanks = computeDenseRanks(users);
        lbRenderTable(users, { page, limit, total, precomputedRanks });
    
        const apiRank = lbGet(payload?.leaderboard, ['my_rank'], precomputedRanks[LB_CURRENT_USER_ID]);
        console.log(apiRank);
        lbSetRankTexts(apiRank, total);
    
        // Pagination buttons
        const prev = document.getElementById('lbPrevBtn');
        const next = document.getElementById('lbNextBtn');
        const maxPage = Math.max(1, Math.ceil(total / limit));
        if (prev) prev.disabled = page <= 1;
        if (next) next.disabled = page >= maxPage;
    }
    

    function applyGuestDashboardBlurRestrictions() {
        if (!IS_GUEST_DASHBOARD) return;

        const targets = [
            {
                id: 'statsContainer',
                message: 'Guest preview only — sign in to view your personal stats, streaks, and completed topics.',
                small: false
            },
            {
                id: 'progressContainer',
                message: 'Progress analytics are locked for guests. Sign in to track your real study progress.',
                small: false
            },
            {
                id: 'activitiesContainer',
                message: 'Recent activity is personalized. Sign in to view your study and exam history.',
                small: true
            },
            {
                id: 'performanceContainer',
                message: 'Performance graphs are locked for guests. Sign in to view your real exam analytics.',
                small: true
            }
        ];

        targets.forEach(({ id, message, small }) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('guest-blur-target');
            if (small) el.classList.add('guest-blur-small');
            el.setAttribute('data-guest-message', message);
        });
    }

    function loadGuestDashboardPreview() {
        document.getElementById('overallCompletion').textContent = 'Preview';
        document.getElementById('studyStreak').textContent = '—';
        document.getElementById('daysActive').textContent = '—';
        document.getElementById('topicsCompleted').textContent = '—';

        const progressContainer = document.getElementById('progressContainer');
        if (progressContainer) {
            progressContainer.innerHTML = `
                <div class="guest-preview-note">
                    <i class="fas fa-eye me-2"></i>This is a dashboard preview. Sign in to save progress, streaks, and personal analytics.
                </div>
                ${[
                    ['Analytical Chemistry', 72, 'microscope', 'primary'],
                    ['Organic Chemistry', 58, 'leaf', 'success'],
                    ['Physical Chemistry', 44, 'calculator', 'warning'],
                    ['Inorganic Chemistry', 63, 'atom', 'info'],
                    ['BioChemistry', 51, 'dna', 'danger']
                ].map(([category, percentage, icon, color]) => `
                    <div class="progress-item">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-${icon} text-${color} me-2"></i>${category}</span>
                            <strong>${percentage}%</strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-${color}" style="width: ${percentage}%"></div>
                        </div>
                        <small class="text-muted">Sample category progress preview</small>
                    </div>
                `).join('')}
            `;
        }

        const activitiesContainer = document.getElementById('activitiesContainer');
        if (activitiesContainer) {
            activitiesContainer.innerHTML = `
                <div class="guest-preview-note">
                    <i class="fas fa-lock me-2"></i>Recent activity is personalized after sign in.
                </div>
                <div class="activity-item">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-book-open text-primary me-3" style="font-size:1.2rem;"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Study material progress</h6>
                            <p class="text-muted small mb-1">Completed files, videos, and PDFs appear here.</p>
                            <small class="text-primary fw-bold">Preview only</small>
                        </div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-flask text-success me-3" style="font-size:1.2rem;"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Exam attempts</h6>
                            <p class="text-muted small mb-1">Post-test results and attempts appear here.</p>
                            <small class="text-success fw-bold">Preview only</small>
                        </div>
                    </div>
                </div>
            `;
        }

        const performanceContainer = document.getElementById('performanceContainer');
        if (performanceContainer) {
            performanceContainer.innerHTML = `
                <div class="guest-preview-note">
                    <i class="fas fa-chart-column me-2"></i>Sample performance chart. Your real scores appear after sign in.
                </div>
                <div class="guest-chart-bars">
                    ${[
                        ['Analytical', 78],
                        ['Organic', 66],
                        ['Physical', 49],
                        ['Inorganic', 61],
                        ['BioChem', 54]
                    ].map(([label, value]) => `
                        <div class="guest-chart-bar">
                            <div class="guest-chart-fill" style="height:${value}%"></div>
                            <div class="guest-chart-label" title="${label}">${label}</div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        setTimeout(() => {
            document.querySelectorAll('.progress-bar').forEach((bar, i) => {
                const w = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => bar.style.width = w, i * 100);
            });
        }, 200);

        applyGuestDashboardBlurRestrictions();
    }

    async function loadDashboardData() {
        if (IS_GUEST_DASHBOARD) {
            loadGuestDashboardPreview();
            return;
        }

        try {
            const response = await fetch('../partial/get_dashboard_data.php');
            const data = await response.json();

            if (!data.success) {
                console.error('Error:', data.error);
                return;
            }

            // Update stats
            document.getElementById('overallCompletion').textContent = data.stats.overall_completion + '%';
            document.getElementById('studyStreak').textContent = data.stats.study_streak + ' days';
            document.getElementById('daysActive').textContent = data.stats.days_active;
            document.getElementById('topicsCompleted').textContent = data.stats.topics_completed;

            const categoryIcons = {
                'Analytical Chemistry': 'microscope',
                'Organic Chemistry': 'leaf',
                'Physical Chemistry': 'calculator',
                'Inorganic Chemistry': 'atom',
                'BioChemistry': 'dna'
            };
            const categoryColors = {
                'Analytical Chemistry': 'primary',
                'Organic Chemistry': 'success',
                'Physical Chemistry': 'warning',
                'Inorganic Chemistry': 'info',
                'BioChemistry': 'danger'
            };

            // Progress by category
            const progressContainer = document.getElementById('progressContainer');
            if (data.progress.length === 0) {
                progressContainer.innerHTML = '<div class="empty-state"><i class="fas fa-chart-line"></i><p>No progress data yet. Start studying!</p></div>';
            } else {
                progressContainer.innerHTML = data.progress.map(prog => {
                    const category = prog.category;
                    const icon = categoryIcons[category] || 'flask';
                    const color = categoryColors[category] || 'primary';

                    return `
                            <div class="progress-item">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-${icon} text-${color} me-2"></i>${category}</span>
                                    <strong>${prog.percentage}%</strong>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-${color}" style="width: ${prog.percentage}%"></div>
                                </div>
                                <small class="text-muted">${prog.completed}/${prog.total} topics</small>
                            </div>
                        `;
                }).join('');
            }

            // Recent Activities
            const activitiesContainer = document.getElementById('activitiesContainer');
            if (data.recent_activities.length === 0) {
                activitiesContainer.innerHTML = '<div class="empty-state"><i class="fas fa-history"></i><p>No recent activity</p></div>';
            } else {
                activitiesContainer.innerHTML = data.recent_activities.map(a => `
                        <div class="activity-item">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-${a.icon} text-${a.color} me-3" style="font-size:1.2rem;"></i>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${a.title}</h6>
                                    <p class="text-muted small mb-1">${a.description}</p>
                                    <small class="text-${a.color} fw-bold">${a.status}</small>
                                </div>
                                <small class="text-muted">${a.time_ago}</small>
                            </div>
                        </div>
                    `).join('');
            }

            const performanceContainer = document.getElementById('performanceContainer');
            if (data.performance.length === 0) {
                performanceContainer.innerHTML = '<div class="empty-state"><i class="fas fa-chart-bar"></i><p>No exams taken yet</p></div>';
            } else {
                performanceContainer.innerHTML = data.performance.map(p => {
                    const category = p.category;
                    const icon = categoryIcons[category] || 'flask';
                    const color = categoryColors[category] || 'primary';

                    return `
                            <div class="performance-item">
                                <div class="subject-icon ${category.toLowerCase().replace(/\s+/g, '-')}">
                                    <i class="fas fa-${icon}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>${category}</strong>
                                        <span class="fw-bold">${p.avg_score}%</span>
                                    </div>
                                    <div class="progress mt-2" style="height:6px;">
                                        <div class="progress-bar bg-${color}" style="width:${p.avg_score}%"></div>
                                    </div>
                                    <small class="text-muted">${p.total_exams} exam${p.total_exams!==1?'s':''} • ${p.total_correct}/${p.total_answered} correct</small>
                                </div>
                            </div>
                        `;
                }).join('');
            }

            setTimeout(() => {
                document.querySelectorAll('.progress-bar').forEach((bar, i) => {
                    const w = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => bar.style.width = w, i * 100);
                });
            }, 200);

        } catch (err) {
            console.error('Fetch error:', err);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadDashboardData();
        lbInitDashboardLeaderboard();
        if (IS_GUEST_DASHBOARD) {
            setTimeout(applyGuestDashboardBlurRestrictions, 350);
        }
    });

    // Modal listeners
    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('leaderboardModal');
        if (modalEl && IS_GUEST_DASHBOARD) {
            modalEl.addEventListener('show.bs.modal', (e) => e.preventDefault());
        }
        if (modalEl && !IS_GUEST_DASHBOARD) {
            modalEl.addEventListener('shown.bs.modal', () => {
                lbRenderModalTop3(LB_STATE.top3);
                LB_STATE.page = 1;
                lbLoadModalPage().catch(console.error);
            });
        }

        if (modalEl) modalEl.addEventListener('hidden.bs.modal', () => {
            // For Safety cleanup: sometimes backdrops stick if a JS error occurs while loading leaderboard
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.body.style.removeProperty('overflow');
        });

        const prev = document.getElementById('lbPrevBtn');
        const next = document.getElementById('lbNextBtn');
        if (prev) prev.addEventListener('click', () => {
            if (LB_STATE.page > 1) {
                LB_STATE.page -= 1;
                lbLoadModalPage().catch(console.error);
            }
        });
        if (next) next.addEventListener('click', () => {
            LB_STATE.page += 1;
            lbLoadModalPage().catch(console.error);
        });

        const searchInput = document.getElementById('lbSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const val = e.target.value || '';
                LB_STATE.search = val;
                LB_STATE.page = 1;
                clearTimeout(lbSearchTimer);
                lbSearchTimer = setTimeout(() => {
                    lbLoadModalPage().catch(console.error);
                }, 250);
            });
        }
    });

    // Scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.style.opacity = '1';
                e.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.1
    });

    document.querySelectorAll('.card, .activity-item, .performance-item').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
</script>