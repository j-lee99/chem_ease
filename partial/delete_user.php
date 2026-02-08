<?php
// partial/delete_user.php
require_once 'db_conn.php';
session_start();

 

$user_id = (int)$_POST['user_id'];
if ($user_id <= 0) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}
if ($user_id == 0) {
    echo json_encode(['error' => 'Cannot delete admin']);
    exit;
}

// Soft delete using boolean
$sql = "UPDATE users SET is_deleted = 1 WHERE id = ? AND is_deleted = 0";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
$success = mysqli_stmt_execute($stmt);

echo json_encode(['success' => $success && mysqli_affected_rows($conn) > 0]);
?>