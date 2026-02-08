<?php
require_once 'partial/db_conn.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

$message = '';
$success = false;

if (empty($token)) {
    $message = "Invalid or missing verification token.";
} else {
    // Find user with this token
    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0 LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $userId = $user['id'];

        // Mark as verified + remove token
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $update->bind_param("i", $userId);

        if ($update->execute()) {
            $success = true;
            $message = "Your email has been successfully verified! You can now sign in.";
        } else {
            $message = "Failed to update verification status. Please try again later.";
        }
        $update->close();
    } else {
        $message = "Invalid, expired, or already used verification link.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - ChemEase</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e8f4f8 0%, #f0f9ff 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .card {
            max-width: 500px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(23,162,184,0.2);
        }
        .card-header {
            background: linear-gradient(90deg, #17a2b8, #0dcaf0);
            color: white;
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <h3 class="mb-0">Email Verification</h3>
    </div>
    <div class="card-body p-5 text-center">
        <?php if ($success): ?>
            <div class="mb-4">
                <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
            </div>
            <h4 class="text-success mb-4">Verification Successful!</h4>
            <p class="lead mb-4"><?php echo htmlspecialchars($message); ?></p>
            <a href="signin.php" class="btn btn-primary btn-lg px-5">Go to Sign In</a>
        <?php else: ?>
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle text-danger" style="font-size: 5rem;"></i>
            </div>
            <h4 class="text-danger mb-4">Verification Failed</h4>
            <p class="lead mb-4"><?php echo htmlspecialchars($message); ?></p>
            <a href="signup.php" class="btn btn-outline-primary">Try Signing Up Again</a>
        <?php endif; ?>
    </div>
</div>

<script src="https://kit.fontawesome.com/4c256af6f2.js" crossorigin="anonymous"></script>
</body>
</html>