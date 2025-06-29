<?php
require '../../config/dbconn.php';

$id_no = '2025-01-00001';
$fname = 'admin';
$lname = 'admin';
$email = 'admin';
$password = 'password';

$hash = password_hash($password, PASSWORD_ARGON2ID);

$stmt = $pdo->prepare("INSERT INTO users (id_no, fname, lname, email, password) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$id_no, $fname, $lname, $email, $hash]);

echo "User created!";
?>
