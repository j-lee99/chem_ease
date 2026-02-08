<?php
// partial/get_material.php
require_once 'db_conn.php';
session_start();
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode([]);
    exit;
}

// $stmt = $conn->prepare("SELECT pdf_path,video_path FROM study_materials WHERE id=?");
// $stmt = $conn->prepare("SELECT pdf_path,video_path FROM study_materials WHERE id=?");
// $stmt->bind_param('i',$id);
// $stmt->execute();
// $res = $stmt->get_result()->fetch_assoc();
// $stmt->close();

// echo json_encode([
//     'pdf'   => $res['pdf_path'] ?? null,
//     'video' => $res['video_path'] ?? null
// ]);

$stmt = $conn->prepare("SELECT pdf_path, video_path FROM study_materials WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = [
        'pdf'   => $row['pdf_path'],
        'video' => $row['video_path'],
    ];
}

$stmt->close();

echo json_encode([
    'data' => $rows
]);
