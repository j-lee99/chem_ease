<?php
// partial/get_materials_paginated.php
require_once 'db_conn.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$all    = (int)($_GET['all'] ?? 0);

$where  = '';
$params = [];
$types  = '';

if ($search !== '') {
    $where = "WHERE m.title LIKE ? OR m.description LIKE ? OR m.category LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
    $types  = 'sss';
}

// Base query
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
        ORDER BY m.created_at DESC";

// Only paginate when all != 1
if ($all !== 1) {
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types   .= 'ii';
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Group by category
$grouped = [];
$materialsCount = 0;

while ($row = $result->fetch_assoc()) {
    $materialsCount++;
    $category = trim($row['category']) ?: 'Uncategorized';
    $grouped[$category][] = $row;
}

// Total counts
if ($all == 1) {
    $total = $materialsCount;
    $totalPages = 1;
    $hasMore = false;
    $currentPage = 1;
} else {
    $countSql = "SELECT COUNT(DISTINCT m.id) AS total FROM study_materials m $where";
    $countStmt = $conn->prepare($countSql);

    if ($search !== '') {
        // Only bind the 3 search params (not limit/offset)
        $countStmt->bind_param('sss', ...array_slice($params, 0, 3));
    }

    $countStmt->execute();
    $total = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $totalPages = max(1, (int)ceil($total / $limit));
    $hasMore = $page < $totalPages;
    $currentPage = $page;
}

echo json_encode([
    'folders' => $grouped,
    'current_page' => $currentPage,
    'total_pages' => $totalPages,
    'total_items' => $total,
    'has_more' => $hasMore
]);
