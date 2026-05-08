<?php
session_start();
require_once '../partial/db_conn.php';

$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || $role !== 'super_admin') {
  http_response_code(403);
  echo 'Forbidden';
  exit;
}

$rangeDays = 30;
if (isset($_GET['days'])) {
  $d = (int)$_GET['days'];
  if ($d >= 7 && $d <= 365) $rangeDays = $d;
}

function table_exists(mysqli $conn, string $table): bool {
  $t = $conn->real_escape_string($table);
  $res = $conn->query("SHOW TABLES LIKE '{$t}'");
  if (!$res) return false;
  $ok = ($res->num_rows > 0);
  $res->free();
  return $ok;
}

function pdf_escape(string $s): string {
  return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $s);
}

function fetch_scalar(mysqli $conn, string $sql, $default = 0) {
  $res = $conn->query($sql);
  if (!$res) return $default;
  $row = $res->fetch_row();
  return $row[0] ?? $default;
}

function safe_percent($part, $whole) {
  if ($whole <= 0) return 0;
  return round(($part / $whole) * 100);
}

/* -----------------------------
   Metrics
------------------------------*/
$total_users = fetch_scalar($conn, "
  SELECT COUNT(*) FROM users WHERE role='user'
", fetch_scalar($conn, "SELECT COUNT(*) FROM users"));

$new_users = fetch_scalar($conn, "
  SELECT COUNT(*) FROM users 
  WHERE role='user' 
  AND created_at >= (NOW() - INTERVAL {$rangeDays} DAY)
");

$total_attempts = 0;
$avg_score = 0;
$active_users = 0;
$passed = 0;
$failed = 0;
$pass_rate = 0;

if (table_exists($conn, 'user_exam_attempts')) {

  $total_attempts = fetch_scalar($conn, "
    SELECT COUNT(*) FROM user_exam_attempts 
    WHERE finished_at IS NOT NULL
  ");

  $avg_score = round(fetch_scalar($conn, "
    SELECT AVG(score) FROM user_exam_attempts 
    WHERE finished_at IS NOT NULL
  "));

  $active_users = fetch_scalar($conn, "
    SELECT COUNT(DISTINCT user_id) 
    FROM user_exam_attempts 
    WHERE finished_at IS NOT NULL
    AND finished_at >= (NOW() - INTERVAL {$rangeDays} DAY)
  ");

  // Pass / fail
  if (table_exists($conn, 'exams')) {
    $passed = fetch_scalar($conn, "
      SELECT COUNT(*)
      FROM user_exam_attempts uea
      JOIN exams e ON e.id = uea.exam_id
      WHERE uea.score >= e.passing_score
    ");
  } else {
    $passed = fetch_scalar($conn, "
      SELECT COUNT(*) FROM user_exam_attempts
      WHERE score >= 75
    ");
  }

  $failed = max(0, $total_attempts - $passed);
  $pass_rate = safe_percent($passed, $total_attempts);
}

/* -----------------------------
   PDF content
------------------------------*/
$title = 'ChemEase - Reports Summary';
$generated = date('Y-m-d H:i:s');

$lines = [
  $title,
  "Generated: {$generated}",
  "Range: Last {$rangeDays} days",
  "",
  "USERS",
  "Total Users: " . number_format($total_users),
  "New Users ({$rangeDays}d): " . number_format($new_users),
  "Active Users: " . number_format($active_users),
  "",
  "EXAMS",
  "Total Attempts: " . number_format($total_attempts),
  "Average Score: {$avg_score}%",
  "Passed Attempts: " . number_format($passed),
  "Failed Attempts: " . number_format($failed),
  "Pass Rate: {$pass_rate}%",
  "",
  "Tip: Use the XLSX export for detailed analytics.",
];

/* -----------------------------
   Render PDF
------------------------------*/
$y = 760;
$content = "BT\n/F1 16 Tf\n72 {$y} Td\n(" . pdf_escape($lines[0]) . ") Tj\n";
$content .= "/F1 11 Tf\n0 -22 Td\n(" . pdf_escape($lines[1]) . ") Tj\n";
$content .= "0 -16 Td\n(" . pdf_escape($lines[2]) . ") Tj\n";

for ($i=3; $i<count($lines); $i++) {
  $content .= "0 -16 Td\n(" . pdf_escape($lines[$i]) . ") Tj\n";
}
$content .= "ET\n";

/* -----------------------------
   PDF structure
------------------------------*/
$objects = [];
$objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
$objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
$objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
$objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
$objects[] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream\nendobj\n";

$pdfOut = "%PDF-1.4\n";
$offsets = [0];
foreach ($objects as $obj) {
  $offsets[] = strlen($pdfOut);
  $pdfOut .= $obj;
}

$xrefPos = strlen($pdfOut);
$pdfOut .= "xref\n0 " . (count($objects)+1) . "\n";
$pdfOut .= "0000000000 65535 f \n";

for ($i=1; $i<=count($objects); $i++) {
  $pdfOut .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
}

$pdfOut .= "trailer\n<< /Size " . (count($objects)+1) . " /Root 1 0 R >>\n";
$pdfOut .= "startxref\n{$xrefPos}\n%%EOF";

/* -----------------------------
   Output
------------------------------*/
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="chemease_reports_summary.pdf"');
header('Content-Length: ' . strlen($pdfOut));

echo $pdfOut;