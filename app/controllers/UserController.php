<?php
// root/app/controllers/UserController.php

//handle login logic
session_start();

// Load config and database from bootstrap.php
$db = require_once __DIR__ . '/../bootstrap.php';

// Load user model for authenticaton function
require_once __DIR__ . '/../models/UserModel.php';

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

    header("Location: " . BASE_PATH . "/dashboard"); // Redirects to dashboard controller
    exit;
} else {
    header("Location: " . BASE_PATH . "/app/views/login.php?error=1");
    exit;
}