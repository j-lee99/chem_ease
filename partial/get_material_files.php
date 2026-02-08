<?php
// get_material_files.php
require_once 'db_conn.php';
header('Content-Type: application/json');
$id = (int)($_GET['id'] ?? 0);
if(!$id) { echo json_encode(['pdfs'=>[],'videos'=>[]]); exit; }

$res = $conn->query("SELECT id, type, path FROM study_material_files WHERE material_id=$id");
$pdfs = $videos = [];
while($row = $res->fetch_assoc()){
    if($row['type']==='pdf') $pdfs[] = $row;
    else $videos[] = $row;
}
echo json_encode(['pdfs'=>$pdfs,'videos'=>$videos]);
?>