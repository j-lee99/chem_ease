<?php
// partial/get_users.php
require_once 'db_conn.php';
session_start();

// Only allow admins (role-based, no hardcoded user_id == 0)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

// Exclude soft-deleted users AND admins
$where = "WHERE is_deleted = 0 AND role != 'admin'";
$params = [];
$types = '';

if ($search) {
    $where .= " AND (full_name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

// Count total (non-admin, non-deleted users)
$countSql = "SELECT COUNT(*) FROM users $where";
$countStmt = $conn->prepare($countSql);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_row()[0];
$countStmt->close();

// Fetch users
$sql = "SELECT id, full_name, email, created_at, profile_image
        FROM users
        $where
        ORDER BY id DESC
        LIMIT ? OFFSET ?";
$bindParams = $params;
$bindParams[] = $limit;
$bindParams[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$bindParams);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

echo json_encode([
    'users' => $users,
    'total' => (int)$total,
    'page'  => $page,
    'limit' => $limit
]);

$conn->close();
?>