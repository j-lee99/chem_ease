<?php
/**
 * Static password hasher
 * Password: admin123
 * Copy the hash output and paste into phpMyAdmin
 */

$password = 'admin123';

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

echo "<h3>Plain Password:</h3>";
echo htmlspecialchars($password);

echo "<h3>Hashed Password (store this in DB):</h3>";
echo "<textarea rows='4' cols='100' readonly>$hashedPassword</textarea>";
