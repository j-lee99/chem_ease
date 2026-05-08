<?php
session_start();
require_once 'db_conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

$maintenanceMode = isset($_POST['maintenance_mode']) ? (int)$_POST['maintenance_mode'] : 0;
$maintenanceMessage = trim($_POST['maintenance_message'] ?? '');
$siteBannerEnabled = isset($_POST['site_banner_enabled']) ? (int)$_POST['site_banner_enabled'] : 0;
$siteBannerMessage = trim($_POST['site_banner_message'] ?? '');

$stmt = $conn->prepare("
    UPDATE system_settings
    SET
        maintenance_mode = ?,
        maintenance_message = ?,
        site_banner_enabled = ?,
        site_banner_message = ?
    WHERE id = 1
");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare query.'
    ]);
    exit;
}

$stmt->bind_param(
    "isis",
    $maintenanceMode,
    $maintenanceMessage,
    $siteBannerEnabled,
    $siteBannerMessage
);

$success = $stmt->execute();

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Settings updated successfully.' : 'Failed to update settings.'
]);

$stmt->close();
$conn->close();