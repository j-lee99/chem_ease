<?php
// save_progress.php
require_once 'db_conn.php';
session_start();
if(!isset($_SESSION['user_id'])) exit;
$userId = $_SESSION['user_id'];
$fid = (int)($_POST['file_id'] ?? 0);
$prog = min(100,max(0,(int)($_POST['progress'] ?? 0)));
if(!$fid) exit;

$stmt = $conn->prepare("
    INSERT INTO user_progress (user_id, file_id, progress)
    VALUES (?,?,?)
    ON DUPLICATE KEY UPDATE progress=VALUES(progress)
");
$stmt->bind_param('iii',$userId,$fid,$prog);
$stmt->execute();
$stmt->close();
?>