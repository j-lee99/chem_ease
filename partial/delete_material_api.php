<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['status'=>'error','message'=>'Invalid ID']);
    exit;
}

 
$res = $conn->query("SELECT path FROM study_material_files WHERE material_id = $id AND type = 'pdf'");
while ($row = $res->fetch_assoc()) {
    $file = '../' . $row['path'];
    if (file_exists($file)) @unlink($file);
}

 
$conn->query("DELETE FROM study_material_files WHERE material_id = $id");
$conn->query("DELETE FROM study_materials WHERE id = $id");

echo json_encode(['status'=>'success']);
?>