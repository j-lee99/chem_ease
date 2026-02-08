<?php
// change_password.php
session_start();
header('Content-Type: application/json');
require_once 'db_conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ? AND is_deleted = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!password_verify($current, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}

$hashed = password_hash($new, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hashed, $user_id);

echo json_encode(
    $stmt->execute()
    ? ['success' => true, 'message' => 'Password changed successfully']
    : ['success' => false, 'message' => 'Failed to update password']
);
?>