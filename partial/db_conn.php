<?php
// Set the default timezone to Asia/Manila (Philippines)
date_default_timezone_set('Asia/Manila');

// Database configuration
// $host = 'localhost';
// $host = '127.0.0.1';
$dbname = 'u880179925_chemease';
// $dbname = 'chem_ease';
$username = 'u880179925_root';
// $username = 'root';
$password = 'Chemease123.';
// $password = '';

$conn = new mysqli($host, $username, $password, $dbname, 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Also set MySQL session timezone to match PHP (recommended for consistency)
$conn->query("SET time_zone = '+08:00'");

$conn->set_charset("utf8mb4");
?>