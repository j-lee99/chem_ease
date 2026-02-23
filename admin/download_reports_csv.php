<?php
session_start();
require_once '../partial/db_conn.php';

$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || $role !== 'super_admin') {
  http_response_code(403);
  echo 'Forbidden';
  exit;
}

function table_exists(mysqli $conn, string $table): bool {
  $t = $conn->real_escape_string($table);
  $res = $conn->query("SHOW TABLES LIKE '{$t}'");
  if (!$res) return false;
  $ok = ($res->num_rows > 0);
  $res->free();
  return $ok;
}
function col_exists(mysqli $conn, string $table, string $col): bool {
  $t = $conn->real_escape_string($table);
  $c = $conn->real_escape_string($col);
  $res = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
  if (!$res) return false;
  $ok = ($res->num_rows > 0);
  $res->free();
  return $ok;
}

$rangeDays = 30;
if (isset($_GET['days'])) {
  $d = (int)$_GET['days'];
  if ($d >= 7 && $d <= 365) $rangeDays = $d;
}

// detect progress table
$progressTable = null;
$progressCandidates = ['user_progress','user_material_progress','user_study_progress','material_progress','progress'];
foreach ($progressCandidates as $cand) {
  if (table_exists($conn,$cand)) { $progressTable = $cand; break; }
}
$hasProgress = false;
$progUserCol=null; $progPctCol=null; $progUpdCol=null;
if ($progressTable) {
  foreach (['user_id','uid','student_id'] as $c) if (col_exists($conn,$progressTable,$c)) { $progUserCol=$c; break; }
  foreach (['progress','percentage','percent','progress_percent','progress_pct'] as $c) if (col_exists($conn,$progressTable,$c)) { $progPctCol=$c; break; }
  foreach (['updated_at','last_updated','modified_at'] as $c) if (col_exists($conn,$progressTable,$c)) { $progUpdCol=$c; break; }
  $hasProgress = (bool)($progUserCol && $progPctCol);
}

$hasAttempts = table_exists($conn,'user_exam_attempts');

$cols = ['u.id'];
if (col_exists($conn,'users','full_name')) $cols[]='u.full_name';
if (col_exists($conn,'users','email')) $cols[]='u.email';
if (col_exists($conn,'users','role')) $cols[]='u.role';
if (col_exists($conn,'users','created_at')) $cols[]='u.created_at';

$selectCols = implode(', ', $cols);

$attemptSelect = '';
$attemptJoin = '';
if ($hasAttempts) {
  $attemptSelect = ",
    COALESCE(t_total.total_attempts,0) total_attempts,
    COALESCE(t_rng.attempts_rng,0) attempts_range,
    COALESCE(t_avg.avg_score,0) avg_score,
    t_last.last_attempt_at last_attempt_at
  ";
  $attemptJoin = "
    LEFT JOIN (SELECT user_id, COUNT(*) total_attempts FROM user_exam_attempts WHERE finished_at IS NOT NULL GROUP BY user_id) t_total ON t_total.user_id=u.id
    LEFT JOIN (SELECT user_id, COUNT(*) attempts_rng FROM user_exam_attempts WHERE finished_at IS NOT NULL AND finished_at >= (NOW() - INTERVAL {$rangeDays} DAY) GROUP BY user_id) t_rng ON t_rng.user_id=u.id
    LEFT JOIN (SELECT user_id, AVG(score) avg_score FROM user_exam_attempts WHERE finished_at IS NOT NULL AND score IS NOT NULL GROUP BY user_id) t_avg ON t_avg.user_id=u.id
    LEFT JOIN (SELECT user_id, MAX(finished_at) last_attempt_at FROM user_exam_attempts WHERE finished_at IS NOT NULL GROUP BY user_id) t_last ON t_last.user_id=u.id
  ";
}

$progSelect = '';
$progJoin = '';
if ($hasProgress) {
  $progSelect = ",
    COALESCE(p_avg.avg_progress,0) avg_progress,
    p_last.last_progress_at last_progress_at
  ";
  $progJoin = "
    LEFT JOIN (SELECT {$progUserCol} user_id, AVG({$progPctCol}) avg_progress FROM {$progressTable} GROUP BY {$progUserCol}) p_avg ON p_avg.user_id=u.id
  ";
  if ($progUpdCol) {
    $progJoin .= " LEFT JOIN (SELECT {$progUserCol} user_id, MAX({$progUpdCol}) last_progress_at FROM {$progressTable} GROUP BY {$progUserCol}) p_last ON p_last.user_id=u.id ";
  } else {
    $progJoin .= " LEFT JOIN (SELECT NULL user_id, NULL last_progress_at) p_last ON 1=0 ";
  }
}

$sql = "
  SELECT {$selectCols}
  {$attemptSelect}
  {$progSelect}
  FROM users u
  {$attemptJoin}
  {$progJoin}
  ORDER BY u.id ASC
";
$res = $conn->query($sql);
if (!$res) {
  http_response_code(500);
  echo 'Query failed: ' . $conn->error;
  exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="chemease_users_activity_report.csv"');

$out = fopen('php://output', 'w');

$header = ['id'];
if (in_array('u.full_name',$cols)) $header[]='full_name';
if (in_array('u.email',$cols)) $header[]='email';
if (in_array('u.role',$cols)) $header[]='role';
if (in_array('u.created_at',$cols)) $header[]='created_at';

if ($hasAttempts) {
  $header[]='total_attempts';
  $header[]="attempts_{$rangeDays}d";
  $header[]='avg_score';
  $header[]='last_attempt_at';
}
if ($hasProgress) {
  $header[]='avg_progress';
  $header[]='last_progress_at';
}
fputcsv($out, $header);

while ($row = $res->fetch_assoc()) {
  $line = [];
  $line[] = $row['id'] ?? '';
  if (isset($row['full_name'])) $line[] = $row['full_name'];
  if (isset($row['email'])) $line[] = $row['email'];
  if (isset($row['role'])) $line[] = $row['role'];
  if (isset($row['created_at'])) $line[] = $row['created_at'];

  if ($hasAttempts) {
    $line[] = $row['total_attempts'] ?? 0;
    $line[] = $row['attempts_range'] ?? 0;
    $line[] = is_null($row['avg_score'] ?? null) ? '' : round((float)$row['avg_score'], 2);
    $line[] = $row['last_attempt_at'] ?? '';
  }
  if ($hasProgress) {
    $line[] = is_null($row['avg_progress'] ?? null) ? '' : round((float)$row['avg_progress'], 2);
    $line[] = $row['last_progress_at'] ?? '';
  }

  fputcsv($out, $line);
}
fclose($out);
$res->free();
