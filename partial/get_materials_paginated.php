<?php
// partial/get_materials_paginated.php
require_once 'db_conn.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

$where = '';
$params = [];
$types = '';

if ($search !== '') {
    $where = "WHERE m.title LIKE ? OR m.description LIKE ? OR m.category LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
    $types = 'sss';
}

$sql = "SELECT 
            m.id,
            m.title,
            m.description,
            m.category,
            m.created_at,
            COUNT(f.id) AS file_count
        FROM study_materials m
        LEFT JOIN study_material_files f ON f.material_id = m.id
        $where
        GROUP BY m.id
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$materials = [];
while ($row = $result->fetch_assoc()) {
    $materials[] = $row;
}

// Get total count for pagination
$countSql = "SELECT COUNT(DISTINCT m.id) AS total FROM study_materials m $where";
$countStmt = $conn->prepare($countSql);
if (!empty($params) && $search !== '') {
    $countStmt->bind_param('sss', ...array_slice($params, 0, 3));
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, ceil($total / $limit));

echo json_encode([
    'materials' => $materials,
    'current_page' => $page,
    'total_pages' => $totalPages,
    'total_items' => (int)$total,
    'has_more' => $page < $totalPages
]);
?>