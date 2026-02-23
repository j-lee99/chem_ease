<?php
header('Content-Type: application/json');

// Allow CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_once 'db_conn.php';

$email    = isset($_POST['email'])    ? trim($_POST['email'])    : '';
$password = isset($_POST['password']) ? $_POST['password']       : '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}

// ── MAIN LOGIN QUERY ──────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT id, full_name, password, role, profile_image, is_verified
    FROM users
    WHERE email = ?
      AND is_deleted = 0
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'No account found with this email. Please sign up first.'
    ]);
    $stmt->close();
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Check if account is verified
if ($user['is_verified'] == 0) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Your account is not verified yet. Please check your email (including spam/junk folder).'
    ]);
    exit;
}

// Verify password
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Incorrect password. Please try again.'
    ]);
    exit;
}

// ── SUCCESSFUL LOGIN ──────────────────────────────────────────────
session_start();

$_SESSION['user_id']      = $user['id'];
$_SESSION['full_name']    = $user['full_name'];
$_SESSION['email']        = $email;
$_SESSION['role']         = $user['role'];
$_SESSION['profile_image']= $user['profile_image'] ?? null;

// Decide redirect based on role
$redirect = ($user['role'] === 'admin' || $user['role'] == 'super_admin') 
    ? 'admin/index.php' 
    : 'users/index.php';

echo json_encode([
    'status'   => 'success',
    'message'  => 'Login successful! Welcome back ' . htmlspecialchars($user['full_name']) . '!',
    'redirect' => $redirect
]);

$conn->close();
?>