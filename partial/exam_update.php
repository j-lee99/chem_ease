<?php
session_start();
require_once 'db_conn.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$examId = (int)($data['exam_id'] ?? 0);

if($examId <= 0){
    echo json_encode(['success'=>false,'msg'=>'Invalid exam ID']);
    exit;
}

$title = trim($data['title'] ?? '');
$topic = trim($data['topic'] ?? '');
$desc  = trim($data['description'] ?? '');
$cat   = $data['category'] ?? '';
$diff  = $data['difficulty'] ?? '';
$ques  = (int)($data['total_questions'] ?? 0);
$dur   = (int)($data['duration_minutes'] ?? 0);
$pass  = (int)($data['passing_score'] ?? 0);
$questions = $data['questions'] ?? [];

if(!$title || !$cat || !$diff || $ques<1 || $dur<1 || $pass<0 || $pass>100 || count($questions)!=$ques){
    echo json_encode(['success'=>false,'msg'=>'Invalid exam data']);
    exit;
}

// Verify exam exists and belongs to admin (optional security)
$check = $conn->prepare("SELECT id FROM exams WHERE id=?");
$check->bind_param('i',$examId);
$check->execute();
if($check->get_result()->num_rows === 0){
    echo json_encode(['success'=>false,'msg'=>'Exam not found']);
    exit;
}

$conn->begin_transaction();
try{
    // Update exam info
    $stmt = $conn->prepare("UPDATE exams SET title=?,topic=?,description=?,category=?,difficulty=?,total_questions=?,duration_minutes=?,passing_score=? WHERE id=?");
    $stmt->bind_param('sssssiiii',$title,$topic,$desc,$cat,$diff,$ques,$dur,$pass,$examId);
    $stmt->execute();

    // Delete old questions and answers
    $conn->query("DELETE FROM exam_answers WHERE question_id IN (SELECT id FROM exam_questions WHERE exam_id=$examId)");
    $conn->query("DELETE FROM exam_questions WHERE exam_id=$examId");

    // Insert new questions
    foreach($questions as $q){
        $qtext = trim($q['text'] ?? '');
        $type  = $q['type'] ?? 'multiple';
        $imagePath = $q['image_path'] ?? null;
        $attachmentPath = $q['attachment_path'] ?? null;
        
        if(empty($qtext)){
            throw new Exception("Question text cannot be empty");
        }
        
        $stmtQ = $conn->prepare("INSERT INTO exam_questions (exam_id,question_text,type,image_path,attachment_path) VALUES (?,?,?,?,?)");
        $stmtQ->bind_param('issss',$examId,$qtext,$type,$imagePath,$attachmentPath);
        $stmtQ->execute();
        $qid = $conn->insert_id;

        $choiceOrder = 1;
        $hasCorrect = false;
        foreach($q['choices'] as $c){
            $atext = trim($c['text'] ?? '');
            $correct = !empty($c['correct']) ? 1 : 0;

            // SKIP completely empty choices instead of error
            if($atext === '') {
                continue;
            }

            if($correct) $hasCorrect = true;

            $stmtA = $conn->prepare("INSERT INTO exam_answers (question_id,answer_text,is_correct,order_index) VALUES (?,?,?,?)");
            $stmtA->bind_param('isii',$qid,$atext,$correct,$choiceOrder);
            $stmtA->execute();
            $choiceOrder++;
        }

        // Ensure at least one correct answer
        if (!$hasCorrect && $type !== 'truefalse') {
            throw new Exception("Question ID $qid must have at least one correct answer");
        }
    }

    $conn->commit();
    echo json_encode(['success'=>true,'exam_id'=>$examId]);
}catch(Exception $e){
    $conn->rollback();
    echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
}
?>