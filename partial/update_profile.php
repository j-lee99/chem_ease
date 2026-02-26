<?php
session_start();
require_once 'db_conn.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Ensure upload directory exists
$uploadDir = '../uploads/profile/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Helper: Check allowed email domains
function isAllowedEmailDomain($email) {
    $allowed = ['gmail.com', 'bicol-u.edu.ph'];
    $domain = strtolower(trim(substr(strrchr($email, "@"), 1)));
    return in_array($domain, $allowed);
}

// Helper: Send email with PHPMailer
function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'chemease2025@gmail.com';
        $mail->Password = 'qros mxzh oftq uhzz'; // ← Use App Password in production!
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('chemease2025@gmail.com', 'ChemEase');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT full_name, email, profile_image, password, mobile, birthday, address FROM users WHERE id = ? AND is_deleted = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid request'];

switch ($action) {
    case 'prepare_profile_update':
        $full_name = trim($_POST['full_name'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $mobile   = trim($_POST['mobile'] ?? '');
        $birthday = trim($_POST['birthday'] ?? '');
        $address  = trim($_POST['address'] ?? '');

        if (empty($full_name) || empty($new_email)) {
            $response['message'] = 'Name and email required';
            break;
        }

        if (!isAllowedEmailDomain($new_email)) {
            $response['message'] = 'Only @gmail.com or @bicol-u.edu.ph allowed';
            break;
        }

        // Email validation
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND is_deleted = 0");
        $stmt->bind_param("si", $new_email, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $response['message'] = 'Email already in use';
            $stmt->close();
            break;
        }
        $stmt->close();

        $name_changed = strcasecmp($full_name, $current['full_name']) !== 0;
        if ($name_changed) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE full_name = ? AND id != ? AND is_deleted = 0");
            $stmt->bind_param("si", $full_name, $user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $response['message'] = 'Full name already taken';
                $stmt->close();
                break;
            }
            $stmt->close();
        }

        $profile_image = $current['profile_image'];
        $image_updated = false;

        // Handle uploaded image
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif'])) {
                $response['message'] = 'Invalid image type';
                break;
            }
            if ($file['size'] > 5242880) { // 5MB
                $response['message'] = 'Image too large (max 5MB)';
                break;
            }
            $newName = 'profile_' . $user_id . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                if ($profile_image && file_exists('../' . $profile_image)) {
                    @unlink('../' . $profile_image);
                }
                $profile_image = 'uploads/profile/' . $newName;
                $image_updated = true;
            } else {
                $response['message'] = 'Image upload failed';
                break;
            }
        }

        $email_changed = ($new_email !== $current['email']);

        if (!$email_changed) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, profile_image = ?, mobile = ?, birthday = ?, address = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $full_name, $profile_image, $mobile, $birthday, $address, $user_id);
            if ($stmt->execute()) {
                $_SESSION['full_name'] = $full_name;
                $response = [
                    'success' => true,
                    'message' => 'Profile updated',
                    'updated_name' => $full_name,
                    'updated_email' => $new_email,
                    'updated_image' => $image_updated ? '../' . $profile_image . '?t=' . time() : null
                ];
            } else {
                $response['message'] = 'Database update failed';
            }
            $stmt->close();
        } else {
            // Email changed → store pending data + send OTP
            $_SESSION['pending_profile_update'] = [
                'full_name'     => $full_name,
                'email'         => $new_email,
                'profile_image' => $profile_image,
                'mobile'        => $mobile,
                'birthday'      => $birthday,
                'address'       => $address,
                'otp'           => sprintf("%06d", mt_rand(0, 999999)),
                'otp_time'      => time(),
                'original_email'=> $current['email']
            ];

            $otp = $_SESSION['pending_profile_update']['otp'];
            $subject = "ChemEase Email Change Verification";
            $body = "
            <h2>Email Verification</h2>
            <p>You requested to change your email to: <strong>$new_email</strong></p>
            <p style='font-size:32px; font-weight:bold; letter-spacing:6px; padding:16px; background:#e8f4f8; text-align:center; border-radius:8px;'>
                $otp
            </p>
            <p>This code expires in 15 minutes.</p>
            <p>If this wasn't you, ignore this email or contact support.</p>
            <small>ChemEase Team</small>";

            if (sendEmail($new_email, $subject, $body)) {
                $response = [
                    'success' => true,
                    'message' => 'Verification code sent to your new email',
                    'email_change_pending' => true
                ];
            } else {
                unset($_SESSION['pending_profile_update']);
                $response['message'] = 'Failed to send verification email. Please try again.';
            }
        }
        break;

    // ────────────────────────────────────────────────────────────────
    //  CONFIRM EMAIL CHANGE WITH OTP
    // ────────────────────────────────────────────────────────────────
    case 'confirm_email_change':
        $otp_input = trim($_POST['otp'] ?? '');
        if (empty($otp_input) || !isset($_SESSION['pending_profile_update'])) {
            $response['message'] = 'No pending change or invalid request';
            break;
        }

        $pending = $_SESSION['pending_profile_update'];

        $pending_mobile = $pending['mobile'] ?? '';
        $pending_birthday = $pending['birthday'] ?? '';
        $pending_address = $pending['address'] ?? '';

        if (time() - $pending['otp_time'] > 900) { // 15 min
            unset($_SESSION['pending_profile_update']);
            $response['message'] = 'Verification code expired. Please try again.';
            break;
        }

        if ($otp_input !== $pending['otp']) {
            $response['message'] = 'Invalid verification code';
            break;
        }

        // Update profile with pending data
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, profile_image = ?, mobile = ?, birthday = ?, address = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $pending['full_name'], $pending['email'], $pending['profile_image'], $pending_mobile, $pending_birthday, $pending_address, $user_id);
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $pending['full_name'];
            $response = [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];
            unset($_SESSION['pending_profile_update']);
        } else {
            $response['message'] = 'Failed to save changes';
        }
        $stmt->close();
        break;

    // ────────────────────────────────────────────────────────────────
    //  CHANGE PASSWORD WITH OTP VERIFICATION
    // ────────────────────────────────────────────────────────────────
    case 'prepare_password_change':
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass     = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $response['message'] = 'All password fields are required';
            break;
        }

        if (strlen($new_pass) < 8) {
            $response['message'] = 'New password must be at least 8 characters long';
            break;
        }

        if ($new_pass !== $confirm_pass) {
            $response['message'] = 'New password and confirmation do not match';
            break;
        }

        if (!password_verify($current_pass, $current['password'])) {
            $response['message'] = 'Current password is incorrect';
            break;
        }

        // Store pending password change + generate OTP
        $_SESSION['pending_password_change'] = [
            'new_password_hash' => password_hash($new_pass, PASSWORD_DEFAULT),
            'otp'               => sprintf("%06d", mt_rand(0, 999999)),
            'otp_time'          => time()
        ];

        $otp = $_SESSION['pending_password_change']['otp'];
        $subject = "ChemEase Password Change Verification";
        $body = "
        <h2>Password Change Request</h2>
        <p>You requested to change your password.</p>
        <p style='font-size:32px; font-weight:bold; letter-spacing:6px; padding:16px; background:#e8f4f8; text-align:center; border-radius:8px;'>
            $otp
        </p>
        <p>This code expires in 15 minutes.</p>
        <p>If you did not request this change, ignore this email or contact support immediately.</p>
        <small>ChemEase Team</small>";

        if (sendEmail($current['email'], $subject, $body)) {
            $response = [
                'success' => true,
                'message' => 'Verification code sent to your email',
                'password_change_pending' => true
            ];
        } else {
            unset($_SESSION['pending_password_change']);
            $response['message'] = 'Failed to send verification email. Please try again later.';
        }
        break;

    case 'confirm_password_change':
        $otp_input = trim($_POST['otp'] ?? '');

        if (empty($otp_input) || !isset($_SESSION['pending_password_change'])) {
            $response['message'] = 'No pending password change or invalid request';
            break;
        }

        $pending = $_SESSION['pending_password_change'];

        if (time() - $pending['otp_time'] > 900) { // 15 min
            unset($_SESSION['pending_password_change']);
            $response['message'] = 'Verification code expired. Please try again.';
            break;
        }

        if ($otp_input !== $pending['otp']) {
            $response['message'] = 'Invalid verification code';
            break;
        }

        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $pending['new_password_hash'], $user_id);
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Password updated successfully'
            ];
            unset($_SESSION['pending_password_change']);
        } else {
            $response['message'] = 'Failed to update password';
        }
        $stmt->close();
        break;

    // ────────────────────────────────────────────────────────────────
    //  DELETE ACCOUNT 
    // ────────────────────────────────────────────────────────────────
    case 'delete_account':
        $pass = $_POST['password'] ?? '';
        if (empty($pass)) {
            $response['message'] = 'Password required';
            break;
        }
        if (!password_verify($pass, $current['password'])) {
            $response['message'] = 'Incorrect password';
            break;
        }

        $conn->autocommit(false);
        try {
            $cleanup = [
                "DELETE FROM user_exam_responses WHERE attempt_id IN (SELECT id FROM user_exam_attempts WHERE user_id = ?)",
                "DELETE FROM user_exam_attempts WHERE user_id = ?",
                "DELETE FROM forum_threads WHERE user_id = ?",
                "DELETE FROM forum_replies WHERE user_id = ?",
                "DELETE FROM forum_likes WHERE user_id = ?",
                "DELETE FROM user_progress WHERE user_id = ?"
            ];

            foreach ($cleanup as $sql) {
                $s = $conn->prepare($sql);
                $s->bind_param("i", $user_id);
                $s->execute();
                $s->close();
            }

            if ($current['profile_image'] && file_exists('../' . $current['profile_image'])) {
                @unlink('../' . $current['profile_image']);
            }

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            session_destroy();
            $response = ['success' => true, 'message' => 'Account deleted'];
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Delete failed';
            error_log("Delete error: " . $e->getMessage());
        }
        $conn->autocommit(true);
        break;

    default:
        $response['message'] = 'Unknown action';
        break;
}

echo json_encode($response);
exit;