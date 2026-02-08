<?php
// get_one_file.php
require_once 'db_conn.php';
header('Content-Type: application/json');
$fid = (int)($_GET['fid'] ?? 0);
if(!$fid) { echo json_encode([]); exit; }

$row = $conn->query("SELECT type, path FROM study_material_files WHERE id=$fid")->fetch_assoc();
if(!$row) { echo json_encode([]); exit; }
echo json_encode(['type'=>$row['type'],'path'=>$row['path']]);
?>