<?php
//require '../../config/dbconn.php';

$host = 'localhost';
$db = 'cms_db';
$user = 'postgres';
$pass = 'root';

$dsn = "pgsql:host=$host;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

$id_no = '2025-01-00006';
$fname = 'course';
$lname = 'professor';
$email = 'professor@lpunetwork.edu.ph';
$password = 'password';

$hash = password_hash($password, PASSWORD_ARGON2ID);

$stmt = $pdo->prepare("INSERT INTO users (id_no, fname, lname, email, password) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$id_no, $fname, $lname, $email, $hash]);

echo "User created!";
?>
