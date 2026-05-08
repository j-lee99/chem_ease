<?php
    header('Content-Type: application/json');
    
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
    
    mysqli_report(MYSQLI_REPORT_OFF);
    
    /*
    |--------------------------------------------------------------------------
    | DEBUG MODE
    |--------------------------------------------------------------------------
    | Set to false in production.
    */
    const SIGNUP_DEBUG = true;
    
    function jsonResponse(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_PRETTY_PRINT);
        exit;
    }
    
    function debugResponse(int $statusCode, string $message, array $extra = []): void
    {
        $payload = [
            'status' => 'error',
            'message' => $message,
        ];
    
        if (SIGNUP_DEBUG) {
            $payload['debug'] = $extra;
        }
    
        jsonResponse($statusCode, $payload);
    }
    
    function debugLog(string $stage, array $context = []): void
    {
        if (!SIGNUP_DEBUG) {
            return;
        }
    
        $logLine = '[' . date('Y-m-d H:i:s') . '] ' . $stage . ' ' . json_encode($context) . PHP_EOL;
        file_put_contents(__DIR__ . '/signup_debug.log', $logLine, FILE_APPEND);
    }
    
    set_exception_handler(function (Throwable $e) {
        error_log('signup_api_debug.php exception: ' . $e->getMessage());
        debugLog('exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    
        debugResponse(500, 'Unhandled server exception.', [
            'exception_message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    });
    
    register_shutdown_function(function () {
        $error = error_get_last();
    
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            error_log('signup_api_debug.php fatal error: ' . $error['message']);
            debugLog('fatal', $error);
    
            if (!headers_sent()) {
                debugResponse(500, 'Fatal server error.', [
                    'type' => $error['type'],
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                ]);
            }
        }
    });
    
    const ROLE_CODES = [
        'student' => 'STU',
        'admin'   => 'ADM',
        'teacher' => 'TCH'
    ];
    
    function prepareOrFail(mysqli $conn, string $query, string $label = 'query'): mysqli_stmt
    {
        $stmt = $conn->prepare($query);
    
        if (!$stmt) {
            debugLog('prepare_failed', [
                'label' => $label,
                'db_error' => $conn->error,
                'query' => $query,
            ]);
    
            throw new RuntimeException('Database prepare failed for ' . $label . ': ' . $conn->error);
        }
    
        return $stmt;
    }
    
    function safeRequestSnapshot(): array
    {
        return [
            'fullName' => trim($_POST['fullName'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'mobile' => trim($_POST['mobile'] ?? ''),
            'birthday' => trim($_POST['birthday'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'hasPassword' => isset($_POST['password']) && $_POST['password'] !== '',
            'hasConfirmPassword' => isset($_POST['confirmPassword']) && $_POST['confirmPassword'] !== '',
            'terms_raw' => $_POST['terms'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        ];
    }
    
    function generateUserUUID(mysqli $conn, string $roleCode): string
    {
        $year = date('Y');
    
        if (!$conn->query("LOCK TABLES users WRITE")) {
            throw new RuntimeException('Failed to lock users table: ' . $conn->error);
        }
    
        try {
            $stmt = prepareOrFail($conn, "
                SELECT COALESCE(
                    MAX(CAST(SUBSTRING_INDEX(u_uid, '-', -1) AS UNSIGNED)),
                    0
                ) AS max_seq
                FROM users
                WHERE u_uid LIKE CONCAT(?, '-', ?, '-%')
            ", 'generate_user_uuid');
    
            $stmt->bind_param("ss", $year, $roleCode);
    
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new RuntimeException('Failed to generate UUID sequence: ' . $error);
            }
    
            $stmt->bind_result($maxSeq);
            $stmt->fetch();
            $stmt->close();
    
            $nextSeq = ((int) $maxSeq) + 1;
        } finally {
            $conn->query("UNLOCK TABLES");
        }
    
        return sprintf('%s-%s-%04d', $year, $roleCode, $nextSeq);
    }
    
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        debugResponse(405, 'Method not allowed.', [
            'received_method' => $_SERVER['REQUEST_METHOD'] ?? null,
        ]);
    }
    
    debugLog('request_started', safeRequestSnapshot());
    
    require_once 'db_conn.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    if (!isset($conn) || !($conn instanceof mysqli)) {
        debugResponse(500, 'Database connection was not initialized properly.', [
            'db_conn_loaded' => isset($conn),
        ]);
    }
    
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
        debugResponse(400, 'Please fill in all required fields.', safeRequestSnapshot());
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        debugResponse(400, 'Invalid email format.', ['email' => $email]);
    }
    
    if (!preg_match('/^(09|\+639)\d{9}$/', $mobile)) {
        debugResponse(400, 'Invalid Philippine mobile number.', ['mobile' => $mobile]);
    }
    
    try {
        $birthDate = new DateTime($birthday);
        $today     = new DateTime();
        $age       = $today->diff($birthDate)->y;
    } catch (Throwable $e) {
        debugResponse(400, 'Birthday is invalid.', [
            'birthday' => $birthday,
            'exception' => $e->getMessage(),
        ]);
    }
    
    if ($age < 13) {
        debugResponse(400, 'You must be at least 13 years old to register.', [
            'birthday' => $birthday,
            'age' => $age,
        ]);
    }
    
    if ($password !== $confirm) {
        debugResponse(400, 'Passwords do not match.');
    }
    
    if (strlen($password) < 8) {
        debugResponse(400, 'Password must be at least 8 characters.', [
            'password_length' => strlen($password),
        ]);
    }
    
    if (!$terms) {
        debugResponse(400, 'Please accept the terms.', [
            'terms_raw' => $_POST['terms'] ?? null,
            'terms_parsed' => $terms,
        ]);
    }
    
    /* ---------- Duplicate Checks ---------- */
    
    $stmt = prepareOrFail(
        $conn,
        "SELECT id FROM users WHERE email = ? AND is_deleted = 0",
        'duplicate_email_check'
    );
    $stmt->bind_param("s", $email);
    
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        debugResponse(500, 'Email duplicate check failed.', [
            'db_error' => $error,
            'email' => $email,
        ]);
    }
    
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        debugResponse(400, 'Email already exists.', ['email' => $email]);
    }
    $stmt->close();
    
    $stmt = prepareOrFail(
        $conn,
        "SELECT id FROM users WHERE mobile = ? AND is_deleted = 0",
        'duplicate_mobile_check'
    );
    $stmt->bind_param("s", $mobile);
    
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        debugResponse(500, 'Mobile duplicate check failed.', [
            'db_error' => $error,
            'mobile' => $mobile,
        ]);
    }
    
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        debugResponse(400, 'Mobile number already registered.', ['mobile' => $mobile]);
    }
    $stmt->close();
    
    /* ---------- Insert ---------- */
    
    try {
        $token    = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        debugResponse(500, 'Failed generating verification token.', [
            'exception' => $e->getMessage(),
        ]);
    }
    
    $hashed   = password_hash($password, PASSWORD_DEFAULT);
    $roleCode = ROLE_CODES['student'];
    $userUUID = generateUserUUID($conn, $roleCode);
    
    $stmt = prepareOrFail($conn, "
        INSERT INTO users
        (u_uid, full_name, email, mobile, birthday, address, password, verification_token, is_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
    ", 'user_insert');
    
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
        $dbError = $stmt->error;
        $dbErrno = $stmt->errno;
        $stmt->close();
    
        debugLog('insert_failed', [
            'db_error' => $dbError,
            'db_errno' => $dbErrno,
            'user_uuid' => $userUUID,
            'email' => $email,
            'mobile' => $mobile,
        ]);
    
        debugResponse(500, 'Account creation failed.', [
            'db_error' => $dbError,
            'db_errno' => $dbErrno,
            'user_uuid' => $userUUID,
            'email' => $email,
            'mobile' => $mobile,
            'hint' => 'Check if the users table columns exactly match: u_uid, full_name, email, mobile, birthday, address, password, verification_token, is_verified',
        ]);
    }
    
    $userId = $stmt->insert_id;
    $stmt->close();
    
    debugLog('insert_success', [
        'user_id' => $userId,
        'user_uuid' => $userUUID,
        'email' => $email,
    ]);
    
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
                            <tr>
                                <td style='background: linear-gradient(135deg, #17a2b8, #0dcaf0); padding:40px 30px; text-align:center;'>
                                    <h1 style='color:#ffffff; margin:0; font-size:32px;'>Welcome to ChemEase!</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding:40px 50px;'>
                                    <h2 style='color:#2c3e50; margin-top:0;'>Hi {$fullName},</h2>
                                    <p style='font-size:16px; color:#555; line-height:1.6;'>
                                        Thank you for signing up! We're excited to have you join our community of chemistry learners.
                                    </p>
                                    <p style='font-size:16px; color:#555; line-height:1.6;'>
                                        Please verify your email address by clicking the button below:
                                    </p>
    
                                    <div style='text-align:center; margin:40px 0;'>
                                        <a href='{$verifyUrl}'
                                           style='background:#17a2b8; color:white; padding:16px 40px;
                                                  text-decoration:none; border-radius:50px; font-size:18px;
                                                  font-weight:bold; display:inline-block; box-shadow:0 4px 15px rgba(23,162,184,0.3);'>
                                            Verify Email Address
                                        </a>
                                    </div>
    
                                    <p style='font-size:15px; color:#666;'>
                                        Or copy and paste this link in your browser:<br>
                                        <a href='{$verifyUrl}' style='color:#17a2b8; word-break:break-all;'>{$verifyUrl}</a>
                                    </p>
    
                                    <p style='font-size:14px; color:#888; margin-top:40px;'>
                                        This link will expire in 24 hours for security reasons.
                                    </p>
                                </td>
                            </tr>
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
    
        debugLog('email_sent', [
            'email' => $email,
            'user_id' => $userId,
        ]);
    
        jsonResponse(200, [
            'status' => 'success',
            'message' => 'Account created! Please check your email to verify your account.',
            'debug' => SIGNUP_DEBUG ? [
                'user_id' => $userId,
                'user_uuid' => $userUUID,
                'email_status' => 'sent',
            ] : null,
        ]);
    } catch (Exception $e) {
        error_log('Email error: ' . $mail->ErrorInfo);
        debugLog('email_failed', [
            'email' => $email,
            'user_id' => $userId,
            'mailer_error' => $mail->ErrorInfo,
            'exception' => $e->getMessage(),
        ]);
    
        jsonResponse(200, [
            'status' => 'success',
            'message' => 'Account created successfully, but email sending failed.',
            'debug' => SIGNUP_DEBUG ? [
                'user_id' => $userId,
                'user_uuid' => $userUUID,
                'email_status' => 'failed',
                'mailer_error' => $mail->ErrorInfo,
                'exception' => $e->getMessage(),
            ] : null,
        ]);
    }
    
    $conn->close();
