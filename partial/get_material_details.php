<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] === 'user') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

$stmt = $conn->prepare("SELECT title, description, category FROM study_materials WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$material = $result->fetch_assoc();
$stmt->close();

if (!$material) {
    echo json_encode(['status' => 'error', 'message' => 'Material not found']);
    exit;
}

$pdfs = [];
$stmt = $conn->prepare("SELECT id, path FROM study_material_files WHERE material_id = ? AND type = 'pdf'");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pdfs[] = $row;
}
$stmt->close();

$videos = [];
$stmt = $conn->prepare("SELECT id, path FROM study_material_files WHERE material_id = ? AND type = 'youtube'");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $videos[] = $row;
}
$stmt->close();

echo json_encode([
    'status' => 'success',
    'title' => $material['title'],
    'description' => $material['description'],
    'category' => $material['category'],
    'pdfs' => $pdfs,
    'videos' => $videos
]);
?>