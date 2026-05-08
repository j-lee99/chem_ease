<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_conn.php';

$systemSettings = [
    'maintenance_mode' => 0,
    'maintenance_message' => 'The system is currently under maintenance. Please try again later.',
    'site_banner_enabled' => 0,
    'site_banner_message' => ''
];

$result = $conn->query("SELECT * FROM system_settings WHERE id = 1 LIMIT 1");

if ($result && $result->num_rows > 0) {
    $systemSettings = array_merge($systemSettings, $result->fetch_assoc());
}

$currentRole = $_SESSION['role'] ?? '';
$isPrivilegedUser = in_array($currentRole, ['admin', 'super_admin'], true);

if ((int)$systemSettings['maintenance_mode'] === 1 && !$isPrivilegedUser) {
    $message = htmlspecialchars(
        $systemSettings['maintenance_message'] ?: 'The system is currently under maintenance. Please try again later.'
    );

    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maintenance Mode</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f8f9fa;
                font-family: Arial, sans-serif;
                padding: 20px;
            }
            .maintenance-box {
                max-width: 650px;
                width: 100%;
                background: white;
                border-radius: 16px;
                padding: 40px;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            }
            .maintenance-icon {
                font-size: 48px;
                color: #f59e0b;
                margin-bottom: 20px;
            }
            .maintenance-title {
                margin: 0 0 15px;
                font-size: 28px;
                color: #2c3e50;
            }
            .maintenance-message {
                margin: 0;
                color: #6c757d;
                line-height: 1.7;
                font-size: 16px;
            }
        </style>
    </head>
    <body>
        <div class="maintenance-box">
            <div class="maintenance-icon"><i class="fas fa-tools"></i></div>
            <h1 class="maintenance-title">System Under Maintenance</h1>
            <p class="maintenance-message">' . $message . '</p>
        </div>
    </body>
    </html>';
    exit;
}