<?php
session_start();
header('Content-Type: application/json');

require_once 'db_conn.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized.'
    ]);
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);

const ROLE_CODES = [
    'student' => 'STU',
    'admin'   => 'ADM',
    'teacher' => 'TCH'
];

function jsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function prepareOrFail(mysqli $conn, string $query): mysqli_stmt
{
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new RuntimeException('Database prepare failed: ' . $conn->error);
    }
    return $stmt;
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
        ");

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

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(405, [
            'status' => 'error',
            'message' => 'Method not allowed.'
        ]);
    }

    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $role = trim($_POST['role'] ?? 'admin');

    if (!in_array($role, ['admin', 'teacher'], true)) {
        jsonResponse(400, [
            'status' => 'error',
            'message' => 'Invalid role selected.'
        ]);
    }

    if (!$fullName || !$email || !$mobile || !$birthday || !$address || !$password || !$confirmPassword) {
        jsonResponse(400, [
            'status' => 'error',
            'message' => 'Please fill in all required fields.'
        ]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(400, [
            'status' => 'error',
            'message' => 'Invalid email format.'
        ]);
    }

    if (!preg_match('/^(09|\+639)\d{9}$/', $mobile)) {
        jsonResponse(400, [
            'status' => 'error',
            'message' => 'Invalid Philippine mobile number.'
        ]);
    }

    if ($password !== $confirmPassword) {
        jsonResponse(400, [
            'status' => 'error',
            'message' => 'Passwords do not match.'
        ]);
    }

    if (strlen($password) < 8) {
        jsonResponse(400, [
            'status' => 'error',
            'message' => 'Password must be at least 8 characters.'
        ]);
    }

    $stmt = prepareOrFail($conn, "SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        jsonResponse(400, [
            'status' => 'error',
            'message' => 'Email already exists.'
        ]);
    }
    $stmt->close();

    $stmt = prepareOrFail($conn, "SELECT id FROM users WHERE mobile = ?");
    $stmt->bind_param("s", $mobile);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        jsonResponse(400, [
            'status' => 'error',
            'message' => 'Mobile number already registered.'
        ]);
    }
    $stmt->close();

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $roleCode = ROLE_CODES[$role];
    $userUUID = generateUserUUID($conn, $roleCode);

    $stmt = prepareOrFail($conn, "
        INSERT INTO users
        (u_uid, full_name, email, mobile, birthday, address, password, role, is_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
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
        $role
    );

    if (!$stmt->execute()) {
        $dbError = $stmt->error;
        $stmt->close();

        jsonResponse(500, [
            'status' => 'error',
            'message' => 'Failed to create admin account.',
            'debug' => $dbError
        ]);
    }

    $stmt->close();

    jsonResponse(200, [
        'status' => 'success',
        'message' => ucfirst($role) . ' account created successfully.'
    ]);

} catch (Throwable $e) {
    jsonResponse(500, [
        'status' => 'error',
        'message' => 'Server error.',
        'debug' => $e->getMessage()
    ]);
}