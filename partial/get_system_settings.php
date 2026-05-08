<?php
session_start();
require_once 'db_conn.php';

header('Content-Type: application/json');

$role = $_SESSION['role'] ?? '';

if (!isset($_SESSION['user_id']) || $role !== 'super_admin') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized.'
    ]);
    exit;
}

$result = $conn->query("SELECT * FROM system_settings WHERE id = 1 LIMIT 1");

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load system settings.'
    ]);
    exit;
}

$settings = $result->fetch_assoc();

echo json_encode([
    'status' => 'success',
    'data' => $settings ?: [
        'maintenance_mode' => 0,
        'maintenance_message' => '',
        'site_banner_enabled' => 0,
        'site_banner_message' => ''
    ]
]);

$conn->close();