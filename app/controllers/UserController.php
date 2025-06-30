<?php
// /app/controllers/UserController.php

//handle login logic
session_start();

// load environment variables
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
}else {
    die ("Invalid request!");
}

if (USE_MOCK) {
    // declare a new mock database instance
    require_once __DIR__ . '/../models/MockDatabase.php';
    $pdo = new MockDatabase();
}else {
    // declare a new pdo
    require_once __DIR__ . '/../models/PostgresDatabase.php';
    $pdo = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
}

$pdo->authenticate($email, $password);
