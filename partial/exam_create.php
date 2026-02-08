<?php
session_start();
require_once 'db_conn.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$title = trim($data['title'] ?? '');
$topic = trim($data['topic'] ?? '');
$desc  = trim($data['description'] ?? '');
$cat   = $data['category'] ?? '';
$diff  = $data['difficulty'] ?? '';
$ques  = (int)($data['total_questions'] ?? 0);
$dur   = (int)($data['duration_minutes'] ?? 0);
$pass  = (int)($data['passing_score'] ?? 0);
$questions = $data['questions'] ?? [];

if(!$title || !$cat || !$diff || $ques<1 || $dur<1 || $pass<0 || count($questions)!=$ques){
    echo json_encode(['success'=>false,'msg'=>'Invalid data']);
    exit;
}

$conn->begin_transaction();
try{
    $stmt = $conn->prepare("INSERT INTO exams (title,topic,description,category,difficulty,total_questions,duration_minutes,passing_score) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param('sssssiii',$title,$topic,$desc,$cat,$diff,$ques,$dur,$pass);
    $stmt->execute();
    $examId = $conn->insert_id;

    $questionOrder = 1;
    foreach($questions as $q){
        $qtext = trim($q['text']);
        $type  = $q['type'];
        $imagePath = $q['image_path'] ?? null;
        $attachmentPath = $q['attachment_path'] ?? null;
        
        if(empty($qtext)) throw new Exception("Empty question text");
        
        $stmtQ = $conn->prepare("INSERT INTO exam_questions (exam_id,question_text,type,image_path,attachment_path) VALUES (?,?,?,?,?)");
        $stmtQ->bind_param('issss',$examId,$qtext,$type,$imagePath,$attachmentPath);
        $stmtQ->execute();
        $qid = $conn->insert_id;

        $choiceOrder = 1;
        foreach($q['choices'] as $c){
            $atext = trim($c['text']);
            $correct = (int)($c['correct'] ?? 0);
            
            if(empty($atext)) throw new Exception("Empty choice text for question {$qid}");
            
            $stmtA = $conn->prepare("INSERT INTO exam_answers (question_id,answer_text,is_correct,order_index) VALUES (?,?,?,?)");
            $stmtA->bind_param('isii',$qid,$atext,$correct,$choiceOrder);
            $stmtA->execute();
            $choiceOrder++;
        }
        $questionOrder++;
    }
    $conn->commit();
    echo json_encode(['success'=>true,'exam_id'=>$examId]);
}catch(Exception $e){
    $conn->rollback();
    echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
}
?>