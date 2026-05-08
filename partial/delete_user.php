<?php
// partial/delete_user.php
require_once 'db_conn.php';
session_start();

header('Content-Type: application/json');

$role = $_SESSION['role'] ?? '';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

if (!in_array($role, ['admin', 'super_admin'], true)) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = (int) ($_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

// if ($user_id === $currentUserId) {
//     echo json_encode(['error' => 'You cannot delete your own account']);
//     exit;
// }

// Get target user first
$checkSql = "SELECT id, role, email, mobile, is_deleted FROM users WHERE id = ? LIMIT 1";
$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    echo json_encode(['error' => 'Failed to prepare user check query']);
    exit;
}

mysqli_stmt_bind_param($checkStmt, 'i', $user_id);
mysqli_stmt_execute($checkStmt);
$result = mysqli_stmt_get_result($checkStmt);
$targetUser = mysqli_fetch_assoc($result);
mysqli_stmt_close($checkStmt);

if (!$targetUser) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

if ((int) $targetUser['is_deleted'] === 1) {
    echo json_encode(['error' => 'User is already deleted']);
    exit;
}

// Prevent deleting super admin unless current user is also super admin
if (($targetUser['role'] ?? '') === 'super_admin' && $role !== 'super_admin') {
    echo json_encode(['error' => 'Only super admin can delete a super admin account']);
    exit;
}

// Optional stricter rule: never allow deleting super admin accounts
if (($targetUser['role'] ?? '') === 'super_admin') {
    echo json_encode(['error' => 'Super admin accounts cannot be deleted']);
    exit;
}

$originalEmail = (string) ($targetUser['email'] ?? '');
$originalMobile = (string) ($targetUser['mobile'] ?? '');
$timestamp = time();

// Free up unique fields so deleted accounts do not block reuse
$deletedEmail = $originalEmail !== ''
    ? $originalEmail . '.deleted.' . $timestamp . '.' . $user_id
    : null;

$deletedMobile = $originalMobile !== ''
    ? $originalMobile . '.deleted.' . $timestamp . '.' . $user_id
    : null;

$sql = "
    UPDATE users
    SET
        is_deleted = 1,
        email = ?,
        mobile = ?
    WHERE id = ? AND is_deleted = 0
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode(['error' => 'Failed to prepare delete query']);
    exit;
}

mysqli_stmt_bind_param($stmt, 'ssi', $deletedEmail, $deletedMobile, $user_id);
$success = mysqli_stmt_execute($stmt);
$affected = mysqli_affected_rows($conn);

mysqli_stmt_close($stmt);

echo json_encode([
    'success' => $success && $affected > 0
]);
?>