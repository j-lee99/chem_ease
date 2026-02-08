<?php
// get_material_from_file.php
require_once 'db_conn.php';
header('Content-Type: application/json');
$fid = (int)($_GET['fid'] ?? 0);
if(!$fid) { echo json_encode(['material_id'=>0]); exit; }
$row = $conn->query("SELECT material_id FROM study_material_files WHERE id=$fid")->fetch_assoc();
echo json_encode(['material_id'=>$row['material_id']??0]);
?>