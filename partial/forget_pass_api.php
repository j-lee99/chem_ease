<?php
// NO space, NO blank line BEFORE this line!

ob_start(); // ← Start output buffering to catch any accidental output

header('Content-Type: application/json; charset=utf-8');

// Turn off error display – we want clean JSON even if something fails
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL); // but still log them

require_once 'db_conn.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

header('Cache-Control: no-cache, must-revalidate');

$output = [
    'status'  => 'error',
    'message' => 'Invalid request'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    goto finish;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'send_otp':
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $output['message'] = 'Invalid email address';
            goto finish;
        }

        $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE email = ? AND is_deleted = 0 LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $output['message'] = 'No account found with this email';
            goto finish;
        }

        $user = $result->fetch_assoc();
        $fullName = $user['full_name'];

        $otp = sprintf("%06d", mt_rand(0, 999999));

        $_SESSION['reset_otp']      = $otp;
        $_SESSION['reset_email']    = $email;
        $_SESSION['reset_otp_time'] = time();

        $mail = new PHPMailer(true);

        // Server settings (exactly as you wanted)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'chemease2025@gmail.com';
        $mail->Password = 'qros mxzh oftq uhzz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('chemease2025@gmail.com', 'ChemEase');
        $mail->addAddress($email, $fullName);

        try {
            $mail->isHTML(true);
            $mail->Subject = 'ChemEase Password Reset - OTP';
            $mail->Body    = '
                <div style="font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 24px; border: 1px solid #e0e0e0; border-radius: 12px;">
                    <h2 style="color: #0d6efd; margin-bottom: 24px;">Password Reset Request</h2>
                    <p>Hello ' . htmlspecialchars($fullName) . ',</p>
                    <p>We received a request to reset your ChemEase password.</p>
                    <p style="font-size: 1.4rem; font-weight: bold; letter-spacing: 6px; margin: 32px 0; text-align: center;">
                        ' . $otp . '
                    </p>
                    <p>This code was requested from your account.</p>
                    <p style="color: #555; font-size: 0.92rem;">
                        If you did not request a password reset, please ignore this email or contact support.
                    </p>
                    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">
                    <p style="color: #777; font-size: 0.9rem; text-align: center;">
                        ChemEase Team
                    </p>
                </div>';
            $mail->AltBody = "Your ChemEase password reset OTP is: $otp\n\nThis code was requested from your account.\nIf you didn't request this, please ignore this email.";

            $mail->send();

            $output = [
                'status'  => 'success',
                'message' => 'OTP sent successfully'
            ];
        } catch (Exception $e) {
            $output['message'] = 'Email could not be sent. ' . $mail->ErrorInfo;
        }
        break;

    case 'verify_otp':
        $email = trim($_POST['email'] ?? '');
        $otp   = trim($_POST['otp']   ?? '');

        if (empty($email) || empty($otp) || strlen($otp) !== 6 || !ctype_digit($otp)) {
            $output['message'] = 'Invalid request data';
            goto finish;
        }

        if (!isset($_SESSION['reset_otp']) ||
            !isset($_SESSION['reset_email']) ||
            $_SESSION['reset_email'] !== $email) {
            $output['message'] = 'Session expired or invalid request. Please request a new OTP.';
            goto finish;
        }

        if ($_SESSION['reset_otp'] !== $otp) {
            $output['message'] = 'Incorrect OTP';
            goto finish;
        }

        $_SESSION['reset_verified'] = true;

        $output = [
            'status'  => 'success',
            'message' => 'OTP verified'
        ];
        break;

    case 'reset_password':
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';

        if (empty($email) || empty($password)) {
            $output['message'] = 'Missing required fields';
            goto finish;
        }

        if (!isset($_SESSION['reset_verified']) ||
            !isset($_SESSION['reset_email']) ||
            $_SESSION['reset_email'] !== $email) {
            $output['message'] = 'Verification required. Please verify OTP first.';
            goto finish;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND is_deleted = 0");
        $stmt->bind_param("ss", $hashed_password, $email);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_verified']);
            unset($_SESSION['reset_otp_time']);

            $output = [
                'status'  => 'success',
                'message' => 'Password updated successfully'
            ];
        } else {
            $output['message'] = 'Update failed or no matching account';
        }
        break;

    default:
        $output['message'] = 'Unknown action';
}

finish:

// VERY IMPORTANT: discard any accidental output and send ONLY JSON
ob_end_clean();

echo json_encode($output);
exit;