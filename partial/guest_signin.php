<?php
header('Content-Type: application/json');

session_start();

// Set guest session
$_SESSION['user_id']       = null;
$_SESSION['full_name']     = 'Guest';
$_SESSION['email']         = null;
$_SESSION['role']          = 'guest';
$_SESSION['profile_image']= null;
$_SESSION['is_guest']      = true;

// Redirect (same logic style as your login)
echo json_encode([
    'status'   => 'success',
    'message'  => 'Continuing as Guest',
    'redirect' => 'users/index.php'
]);