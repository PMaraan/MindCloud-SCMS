<?php
// root/app/controllers/UserController.php

//handle login logic
session_start();

// load environment variables
require_once __DIR__ . '/../../config/config.php';
/* Used only for testing. Delete for production
$useM = getenv('USE_MOCK');
echo $useM;
echo $_ENV['USE_MOCK'];
*/
//*
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
}else {
    die ("Invalid request!");
}
//*/
if (USE_MOCK) {
    // declare a new mock database instance
    require_once __DIR__ . '/../models/MockDatabase.php';
    $pdo = new MockDatabase();
    echo "Use Mock Database";
}else {
    // declare a new pdo
    require_once __DIR__ . '/../models/PostgresDatabase.php';
    $pdo = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
    echo "Use Postgres Database <br>";
}

$pdo->authenticate($email, $password);
