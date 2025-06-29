<?php
session_start();
/*
// Force HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
*/
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You are not logged in!'); window.location='../../public/loginprototype.html';</script>";
    exit;
}
?>
