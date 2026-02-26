<?php
// get_progress.php  ← IMPORTANT CHANGE HERE
// require_once 'db_conn.php';
// session_start();
// header('Content-Type: application/json');
// if(!isset($_SESSION['user_id'])) exit;
// $userId = $_SESSION['user_id'];
// $mid = (int)($_GET['material_id'] ?? 0);
// if(!$mid) exit;

// $res = $conn->query("
//     SELECT 
//         f.id AS file_id, 
//         f.type, 
//         f.title,
//         f.path,
//         COALESCE(p.progress,0) AS progress
//     FROM study_material_files f
//     LEFT JOIN user_progress p ON p.file_id=f.id AND p.user_id=$userId
//     WHERE f.material_id=$mid
//     ORDER BY f.id
// ");
// $out = [];
// while($row = $res->fetch_assoc()) $out[] = $row;
// echo json_encode($out);

require_once 'db_conn.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['data' => []]);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$category = trim($_GET['category'] ?? '');

$sqlWithCategory = "
    SELECT 
        m.id       AS material_id,
        m.title    AS material_title,
        m.category AS material_category,
        f.id       AS file_id,
        f.type,
        f.title    AS file_title,
        f.path,
        COALESCE(p.progress, 0) AS progress
    FROM study_materials m
    JOIN study_material_files f 
        ON f.material_id = m.id
    LEFT JOIN user_progress p 
        ON p.file_id = f.id 
       AND p.user_id = ?
    " . ($category !== '' ? " WHERE m.category = ? " : "") . "
    ORDER BY m.id, f.id
";

$stmt = $conn->prepare($sqlWithCategory);

if ($stmt) {
    if ($category !== '') {
        $stmt->bind_param('is', $userId, $category);
    } else {
        $stmt->bind_param('i', $userId);
    }
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $sqlFallback = "
        SELECT 
            m.id   AS material_id,
            m.title AS material_title,
            f.id   AS file_id,
            f.type,
            f.title AS file_title,
            f.path,
            COALESCE(p.progress, 0) AS progress
        FROM study_materials m
        JOIN study_material_files f 
            ON f.material_id = m.id
        LEFT JOIN user_progress p 
            ON p.file_id = f.id 
           AND p.user_id = ?
        ORDER BY m.id, f.id
    ";
    $stmt = $conn->prepare($sqlFallback);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
}

$materials = [];

while ($row = $res->fetch_assoc()) {
    $mid = (int) $row['material_id'];

    if (!isset($materials[$mid])) {
        $materials[$mid] = [
            'id'       => $mid,
            'title'    => $row['material_title'],
            'category' => $row['material_category'] ?? null,
            'files'    => []
        ];
    }

    $materials[$mid]['files'][] = [
        'id'       => (int) $row['file_id'],
        'type'     => $row['type'],
        'title'    => $row['file_title'],
        'path'     => $row['path'],
        'progress' => (int) $row['progress'],
    ];
}

$stmt->close();

echo json_encode([
    'data' => array_values($materials)
]);

?>
