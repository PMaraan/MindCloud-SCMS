<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
if (!isset($_SESSION['user_id'])){
    header("Location: /login");
    exit;
}
    */


public function checkIfUserIsLoggedIn() {
    $isLoggedIn = isset($_SESSION['user_id']);
    if (!$isLoggedIn) {
        throw new Exception("User not logged in");
    }
}

