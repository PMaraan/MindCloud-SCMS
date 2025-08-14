<?php
// root/app/controllers/UserController.php

//handle login logic
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config and database from bootstrap.php
$db = require_once __DIR__ . '/../bootstrap.php';

// Load user model for authenticaton function
require_once __DIR__ . '/../models/UserModel.php';

// Load the flash for displaying success/error/info messages
require_once __DIR__ . '/../helpers/FlashHelper.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    die ("Invalid request!");
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$userModel = new UserModel($db);
$userData = $userModel->authenticate($email, $password);
if ($userData) {
    // Set session data
    session_regenerate_id(true);
    //$_SESSION['username'] = $userData['fname'] . " " . $userData['lname'];
    $_SESSION['user_id'] = $userData['id_no'];
    //$_SESSION['role_id'] = intval($userData['role_id']);
    //$_SESSION['role'] = $userData['role_name'];
    //$_SESSION['college_id'] = $userData['college_short'];
    //$_SESSION['college'] = $userData['college_name'];

    FlashHelper::set('success', 'Welcome back, ' . htmlspecialchars($userData['fname']) . '!');
    header("Location: " . BASE_PATH . "/dashboard"); // Redirects to dashboard controller
    exit;
} else {
    FlashHelper::set('danger', 'Invalid email or password.');
    header("Location: " . BASE_PATH . "/login");
    exit;
}