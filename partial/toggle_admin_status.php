<?php
session_start();
header('Content-Type: application/json');
require_once 'db_conn.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized.'
    ]);
    exit;
}

$userId = (int)($_POST['user_id'] ?? 0);
$isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : null;

if (!$userId || !in_array($isActive, [0, 1], true)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request.'
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role IN ('admin', 'super_admin')");
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare query.'
    ]);
    exit;
}

$stmt->bind_param("ii", $isActive, $userId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update admin status.'
    ]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'message' => $isActive ? 'Admin account activated.' : 'Admin account deactivated.'
]);