<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
session_start();

 
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = $_POST['category'] ?? '';
$youtubeText = trim($_POST['youtube_urls'] ?? '');

if (!$title || !$category) {
    echo json_encode(['status' => 'error', 'message' => 'Title and category required']);
    exit;
}

// Insert new material title
$stmt = $conn->prepare("INSERT INTO study_materials (title, description, category) VALUES (?,?,?)");
$stmt->bind_param('sss', $title, $description, $category);
$stmt->execute();
$materialId = $stmt->insert_id;
$stmt->close();

// Upload directory
$uploadDir = '../uploads/materials/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$inserted = 0;

// Handle PDF uploads – keep original file name
if (isset($_FILES['pdfs']) && is_array($_FILES['pdfs']['name'])) {
    for ($i = 0; $i < count($_FILES['pdfs']['name']); $i++) {
        if ($_FILES['pdfs']['error'][$i] !== UPLOAD_ERR_OK) continue;

        $originalName = $_FILES['pdfs']['name'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($ext !== 'pdf') continue;

        // Keep original name, add suffix only if file already exists
        $safeName = preg_replace('/[^A-Za-z0-9\_\-\.]/', '_', $originalName);
        $dest = $uploadDir . $safeName;
        $counter = 1;

        // If file exists, append _1, _2, etc.
        while (file_exists($dest)) {
            $info = pathinfo($safeName);
            $newName = $info['filename'] . '_' . $counter . '.' . $info['extension'];
            $dest = $uploadDir . $newName;
            $counter++;
        }

        if (move_uploaded_file($_FILES['pdfs']['tmp_name'][$i], $dest)) {
            $path = 'uploads/materials/' . basename($dest);
            $stmt = $conn->prepare("INSERT INTO study_material_files (material_id, type, path) VALUES (?, 'pdf', ?)");
            $stmt->bind_param('is', $materialId, $path);
            $stmt->execute();
            $stmt->close();
            $inserted++;
        }
    }
}

// Handle YouTube URLs (unchanged)
if ($youtubeText !== '') {
    $lines = array_filter(array_map('trim', preg_split('/[\r\n]+/', $youtubeText)));
    foreach ($lines as $url) {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        if (preg_match('/(youtube\.com|youtu\.be)/i', $url)) {
            $stmt = $conn->prepare("INSERT INTO study_material_files (material_id, type, path) VALUES (?, 'youtube', ?)");
            $stmt->bind_param('is', $materialId, $url);
            $stmt->execute();
            $stmt->close();
            $inserted++;
        }
    }
}

echo json_encode(
    $inserted > 0
    ? ['status' => 'success', 'message' => "Material added successfully ($inserted item(s))"]
    : ['status' => 'error', 'message' => 'No PDFs or valid YouTube links were added']
);
?>