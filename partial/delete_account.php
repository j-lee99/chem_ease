<?php
session_start();
header('Content-Type: application/json');
require_once 'db_conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Soft delete
$stmt = $conn->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
$stmt->bind_param("i", $user_id);

echo json_encode(
    $stmt->execute()
    ? ['success' => true, 'message' => 'Account deleted. Redirecting...']
    : ['success' => false, 'message' => 'Deletion failed']
);
?>