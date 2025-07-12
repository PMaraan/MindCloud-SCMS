<?php
session_start();
//require '../../config/dbconn.php';

/*
// app/login_auth.php

// set environment variables
require_once __DIR__ . '/../config/config.php'; 
// database class for creating pdo
require_once __DIR__ . '/../app/lib/database.php'; 

if (USE_MOCK) {
    require_once __DIR__ . '/../app/models/MockUserModel.php'; 
    $userModel = new MockUserModel();
} else {
    require_once __DIR__ . '/../app/models/UserModel.php';
    $db = new Database(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
    $pdo = $db->connect();
    $userModel = new UserModel($pdo);
}

// Example login check (replace with real routing)
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$user = $userModel->authenticate($username, $password);

if ($user) {
    echo "Login successful!";
} else {
    echo "Invalid credentials.";
}
    */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
        header("Location: /login?error=invalid_email");
        exit;
    }

    

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id_no'];
        //$_SESSION['email'] = $user['email']; // Email is not used. Remove for production
        header("Location: /dashboard"); // redirect to dashboard; change this location for production
        exit;
    } else {
        header("Location: /login?error=1");
        exit;
    }
}
?>
