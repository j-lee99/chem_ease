<?php
session_start();
require_once 'db_conn.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $uploadDir = '../uploads/';

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '', basename($file['name']));
    $targetPath = $uploadDir . $fileName;
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'msg' => 'Invalid file type']);
        exit;
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => true,
            'path' => 'uploads/' . $fileName,
            'filename' => $fileName
        ]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Upload failed']);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'No file uploaded']);
}
