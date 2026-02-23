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

$total_users = 0;
$total_attempts = 0;
$avg_score = 0;
$active_users = 0;

$res = $conn->query("SELECT COUNT(*) c FROM users WHERE role='user'");
if (!$res) $res = $conn->query("SELECT COUNT(*) c FROM users");
if ($res) { $total_users = (int)($res->fetch_assoc()['c'] ?? 0); $res->free(); }

if (table_exists($conn, 'user_exam_attempts')) {
  $res = $conn->query("SELECT COUNT(*) c FROM user_exam_attempts WHERE finished_at IS NOT NULL");
  if ($res) { $total_attempts = (int)($res->fetch_assoc()['c'] ?? 0); $res->free(); }

  $res = $conn->query("SELECT COALESCE(AVG(score),0) a FROM user_exam_attempts WHERE finished_at IS NOT NULL AND score IS NOT NULL");
  if ($res) { $avg_score = (int)round((float)($res->fetch_assoc()['a'] ?? 0)); $res->free(); }

  $res = $conn->query("SELECT COUNT(DISTINCT user_id) c FROM user_exam_attempts WHERE finished_at IS NOT NULL AND finished_at >= (NOW() - INTERVAL {$rangeDays} DAY)");
  if ($res) { $active_users = (int)($res->fetch_assoc()['c'] ?? 0); $res->free(); }
}

$title = 'ChemEase - Reports Summary';
$generated = date('Y-m-d H:i:s');

$lines = [
  $title,
  "Generated: {$generated}",
  "Range: Last {$rangeDays} days",
  "",
  "Total Users: " . number_format($total_users),
  "Active Users ({$rangeDays}d): " . number_format($active_users),
  "Total Attempts: " . number_format($total_attempts),
  "Average Score: {$avg_score}%",
  "",
  "Download the CSV for the full users + activity breakdown.",
];

$y = 760;
$content = "BT\n/F1 16 Tf\n72 {$y} Td\n(" . pdf_escape($lines[0]) . ") Tj\n";
$content .= "/F1 11 Tf\n0 -22 Td\n(" . pdf_escape($lines[1]) . ") Tj\n";
$content .= "0 -16 Td\n(" . pdf_escape($lines[2]) . ") Tj\n";
for ($i=3; $i<count($lines); $i++) {
  $content .= "0 -16 Td\n(" . pdf_escape($lines[$i]) . ") Tj\n";
}
$content .= "ET\n";

$objects = [];
$objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
$objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
$objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
$objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
$objects[] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream\nendobj\n";

$pdfOut = "%PDF-1.4\n";
$offsets = [0];
foreach ($objects as $obj) { $offsets[] = strlen($pdfOut); $pdfOut .= $obj; }
$xrefPos = strlen($pdfOut);
$pdfOut .= "xref\n0 " . (count($objects)+1) . "\n";
$pdfOut .= "0000000000 65535 f \n";
for ($i=1; $i<=count($objects); $i++) {
  $pdfOut .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
}
$pdfOut .= "trailer\n<< /Size " . (count($objects)+1) . " /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="chemease_reports_summary.pdf"');
header('Content-Length: ' . strlen($pdfOut));
echo $pdfOut;
