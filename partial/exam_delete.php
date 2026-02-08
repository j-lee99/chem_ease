<?php
session_start();
require_once 'db_conn.php';
header('Content-Type: application/json');

$examId = (int)($_GET['id'] ?? 0);
if($examId <= 0){
    echo json_encode(['success'=>false,'msg'=>'Invalid exam ID']);
    exit;
}

$conn->begin_transaction();
try{
     
    $conn->query("DELETE FROM exam_answers WHERE question_id IN (SELECT id FROM exam_questions WHERE exam_id=$examId)");
    $conn->query("DELETE FROM exam_questions WHERE exam_id=$examId");
    $conn->query("DELETE FROM user_exam_attempts WHERE exam_id=$examId");
    
     
    $stmt = $conn->prepare("DELETE FROM exams WHERE id=?");
    $stmt->bind_param('i',$examId);
    $stmt->execute();
    
    $conn->commit();
    echo json_encode(['success'=>true]);
}catch(Exception $e){
    $conn->rollback();
    echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
}
?>