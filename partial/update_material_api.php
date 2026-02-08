<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$material_id = (int)($_POST['material_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = $_POST['category'] ?? '';

if ($material_id <= 0 || !$title || !$category) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing required fields']);
    exit;
}

// Update main material info
$stmt = $conn->prepare("UPDATE study_materials SET title = ?, description = ?, category = ? WHERE id = ?");
$stmt->bind_param('sssi', $title, $description, $category, $material_id);
$stmt->execute();
$stmt->close();

// Upload directory
$uploadDir = '../uploads/materials/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Handle new PDF uploads
$inserted = 0;
if (isset($_FILES['new_pdfs']) && is_array($_FILES['new_pdfs']['name'])) {
    for ($i = 0; $i < count($_FILES['new_pdfs']['name']); $i++) {
        if ($_FILES['new_pdfs']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $originalName = $_FILES['new_pdfs']['name'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') continue;

        $safeName = preg_replace('/[^A-Za-z0-9\_\-\.]/', '_', $originalName);
        $dest = $uploadDir . $safeName;
        $counter = 1;
        while (file_exists($dest)) {
            $info = pathinfo($safeName);
            $newName = $info['filename'] . '_' . $counter . '.' . $info['extension'];
            $dest = $uploadDir . $newName;
            $counter++;
        }

        if (move_uploaded_file($_FILES['new_pdfs']['tmp_name'][$i], $dest)) {
            $path = 'uploads/materials/' . basename($dest);
            $stmt = $conn->prepare("INSERT INTO study_material_files (material_id, type, path) VALUES (?, 'pdf', ?)");
            $stmt->bind_param('is', $material_id, $path);
            $stmt->execute();
            $stmt->close();
            $inserted++;
        }
    }
}

// Handle new YouTube URLs
$newYoutube = trim($_POST['new_youtube_urls'] ?? '');
if ($newYoutube !== '') {
    $lines = array_filter(array_map('trim', preg_split('/[\r\n]+/', $newYoutube)));
    foreach ($lines as $url) {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        if (preg_match('/(youtube\.com|youtu\.be)/i', $url)) {
            $stmt = $conn->prepare("INSERT INTO study_material_files (material_id, type, path) VALUES (?, 'youtube', ?)");
            $stmt->bind_param('is', $material_id, $url);
            $stmt->execute();
            $stmt->close();
            $inserted++;
        }
    }
}

// Handle file renames + deletions
$updated = 0;
$deleted = 0;

// Rename PDFs
if (isset($_POST['pdf_names']) && is_array($_POST['pdf_names'])) {
    foreach ($_POST['pdf_names'] as $file_id => $newName) {
        $file_id = (int)$file_id;
        $newName = trim($newName);
        if (!$newName) continue;

        // Get current file info
        $stmt = $conn->prepare("SELECT path FROM study_material_files WHERE id = ? AND material_id = ? AND type = 'pdf'");
        $stmt->bind_param('ii', $file_id, $material_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $oldPath = $row['path'];
            $oldFullPath = '../' . $oldPath;
            $oldFileName = basename($oldFullPath);
            $dir = dirname($oldFullPath);

            $ext = pathinfo($oldFileName, PATHINFO_EXTENSION);
            $safeNewName = preg_replace('/[^A-Za-z0-9\_\-\.]/', '_', $newName);
            if (pathinfo($safeNewName, PATHINFO_EXTENSION) !== $ext) {
                $safeNewName .= '.' . $ext;
            }

            $newFullPath = $dir . '/' . $safeNewName;
            $counter = 1;
            while (file_exists($newFullPath) && $newFullPath !== $oldFullPath) {
                $info = pathinfo($safeNewName);
                $tryName = $info['filename'] . '_' . $counter . '.' . $info['extension'];
                $newFullPath = $dir . '/' . $tryName;
                $counter++;
            }

            if (rename($oldFullPath, $newFullPath)) {
                $newDbPath = 'uploads/materials/' . basename($newFullPath);
                $stmt = $conn->prepare("UPDATE study_material_files SET path = ? WHERE id = ?");
                $stmt->bind_param('si', $newDbPath, $file_id);
                $stmt->execute();
                $updated++;
            }
        }
        $stmt->close();
    }
}

// Rename YouTube URLs
if (isset($_POST['youtube_urls']) && is_array($_POST['youtube_urls'])) {
    foreach ($_POST['youtube_urls'] as $file_id => $newUrl) {
        $file_id = (int)$file_id;
        $newUrl = trim(filter_var($newUrl, FILTER_SANITIZE_URL));
        if (!$newUrl || !preg_match('/(youtube\.com|youtu\.be)/i', $newUrl)) continue;

        $stmt = $conn->prepare("UPDATE study_material_files SET path = ? WHERE id = ? AND type = 'youtube'");
        $stmt->bind_param('si', $newUrl, $file_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $updated++;
        }
        $stmt->close();
    }
}

// Delete files (physical + DB)
if (isset($_POST['delete_pdf']) && is_array($_POST['delete_pdf'])) {
    foreach ($_POST['delete_pdf'] as $file_id) {
        $file_id = (int)$file_id;
        $stmt = $conn->prepare("SELECT path FROM study_material_files WHERE id = ? AND material_id = ? AND type = 'pdf'");
        $stmt->bind_param('ii', $file_id, $material_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $fullPath = '../' . $row['path'];
            if (file_exists($fullPath)) @unlink($fullPath);
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM study_material_files WHERE id = ?");
        $stmt->bind_param('i', $file_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $deleted++;
        }
        $stmt->close();
    }
}

if (isset($_POST['delete_youtube']) && is_array($_POST['delete_youtube'])) {
    foreach ($_POST['delete_youtube'] as $file_id) {
        $file_id = (int)$file_id;
        $stmt = $conn->prepare("DELETE FROM study_material_files WHERE id = ? AND type = 'youtube'");
        $stmt->bind_param('i', $file_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $deleted++;
        }
        $stmt->close();
    }
}

echo json_encode([
    'status' => 'success',
    'message' => "Changes saved successfully. Updated: $updated, Deleted: $deleted, New items: $inserted"
]);
?>