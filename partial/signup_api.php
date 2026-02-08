<?php
header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

const ROLE_CODES = [
    'student' => 'STU',
    'admin'   => 'ADM',
    'teacher' => 'TCH'
];

function generateUserUUID(mysqli $conn, string $roleCode): string
{
    $year = date('Y');

    // Lock for race condition protection
    $conn->query("LOCK TABLES users WRITE");

    $stmt = $conn->prepare("
        SELECT COUNT(*) + 1 AS seq
        FROM users
        WHERE u_uid LIKE CONCAT(?, '-', ?, '-%')
    ");
    $stmt->bind_param("ss", $year, $roleCode);
    $stmt->execute();
    $stmt->bind_result($seq);
    $stmt->fetch();
    $stmt->close();

    $conn->query("UNLOCK TABLES");

    return sprintf('%s-%s-%04d', $year, $roleCode, $seq);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_once 'db_conn.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ---------- Input ---------- */

$fullName   = trim($_POST['fullName']   ?? '');
$email      = trim($_POST['email']      ?? '');
$mobile     = trim($_POST['mobile']     ?? '');
$birthday   = trim($_POST['birthday']   ?? '');
$address    = trim($_POST['address']    ?? '');
$password   = $_POST['password']        ?? '';
$confirm    = $_POST['confirmPassword'] ?? '';
$terms      = filter_var($_POST['terms'] ?? false, FILTER_VALIDATE_BOOLEAN);

/* ---------- Validation ---------- */

if (!$fullName || !$email || !$mobile || !$birthday || !$address || !$password || !$confirm) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}

/* PH mobile validation */
if (!preg_match('/^(09|\+639)\d{9}$/', $mobile)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Philippine mobile number']);
    exit;
}

/* Age validation (13+) */
$birthDate = new DateTime($birthday);
$today     = new DateTime();
$age       = $today->diff($birthDate)->y;

if ($age < 13) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'You must be at least 13 years old to register']);
    exit;
}

if ($password !== $confirm) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
    exit;
}

if (!$terms) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Please accept the terms']);
    exit;
}

/* ---------- Duplicate Checks ---------- */

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
    $stmt->close();
    exit;
}
$stmt->close();

$stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
$stmt->bind_param("s", $mobile);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Mobile number already registered']);
    $stmt->close();
    exit;
}
$stmt->close();

/* ---------- Insert ---------- */

$token   = bin2hex(random_bytes(32));
$hashed  = password_hash($password, PASSWORD_DEFAULT);
$roleCode = ROLE_CODES['student']; // default role
$userUUID = generateUserUUID($conn, $roleCode);


$stmt = $conn->prepare("
    INSERT INTO users
    (u_uid, full_name, email, mobile, birthday, address, password, verification_token, is_verified)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
");

$stmt->bind_param(
    "ssssssss",
    $userUUID,
    $fullName,
    $email,
    $mobile,
    $birthday,
    $address,
    $hashed,
    $token
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Account creation failed']);
    $stmt->close();
    exit;
}

$stmt->close();

/* ---------- Email Verification ---------- */

$verifyUrl = "https://chemease.site/verify.php?token=" . urlencode($token);

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'chemease2025@gmail.com';
    $mail->Password   = 'qros mxzh oftq uhzz';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('chemease2025@gmail.com', 'ChemEase');
    $mail->addAddress($email, $fullName);

    $mail->isHTML(true);
    $mail->Subject = 'ChemEase - Verify Your Email';
    $mail->Body    = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Verify Your ChemEase Account</title>
    </head>
    <body style='font-family: Arial, Helvetica, sans-serif; background-color: #f4f7fa; margin:0; padding:0;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f7fa; padding:40px 0;'>
            <tr>
                <td align='center'>
                    <table width='100%' cellpadding='0' cellspacing='0' style='max-width:580px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.1);'>
                        <!-- Header -->
                        <tr>
                            <td style='background: linear-gradient(135deg, #17a2b8, #0dcaf0); padding:40px 30px; text-align:center;'>
                                <h1 style='color:#ffffff; margin:0; font-size:32px;'>Welcome to ChemEase!</h1>
                            </td>
                        </tr>
                        <!-- Content -->
                        <tr>
                            <td style='padding:40px 50px;'>
                                <h2 style='color:#2c3e50; margin-top:0;'>Hi $fullName,</h2>
                                <p style='font-size:16px; color:#555; line-height:1.6;'>
                                    Thank you for signing up! We're excited to have you join our community of chemistry learners.
                                </p>
                                <p style='font-size:16px; color:#555; line-height:1.6;'>
                                    Please verify your email address by clicking the button below:
                                </p>
                                
                                <div style='text-align:center; margin:40px 0;'>
                                    <a href='$verifyUrl' 
                                       style='background:#17a2b8; color:white; padding:16px 40px; 
                                              text-decoration:none; border-radius:50px; font-size:18px; 
                                              font-weight:bold; display:inline-block; box-shadow:0 4px 15px rgba(23,162,184,0.3);'>
                                        Verify Email Address
                                    </a>
                                </div>
                                
                                <p style='font-size:15px; color:#666;'>
                                    Or copy and paste this link in your browser:<br>
                                    <a href='$verifyUrl' style='color:#17a2b8; word-break:break-all;'>$verifyUrl</a>
                                </p>
                                
                                <p style='font-size:14px; color:#888; margin-top:40px;'>
                                    This link will expire in 24 hours for security reasons.
                                </p>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style='background:#f8f9fa; padding:30px; text-align:center; font-size:14px; color:#666;'>
                                <p>Questions? Contact us at <a href='mailto:chemease2025@gmail.com' style='color:#17a2b8;'>support@chemease.site</a></p>
                                <p>© " . date("Y") . " ChemEase - Chemistry Reviewer</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>";

    $mail->send();
    $msg = "Account created! Please check your email to verify your account.";
} catch (Exception $e) {
    error_log('Email error: ' . $mail->ErrorInfo);
    $msg = "Account created successfully! Email sending failed.";
}

echo json_encode(['status' => 'success', 'message' => $msg]);
$conn->close();
