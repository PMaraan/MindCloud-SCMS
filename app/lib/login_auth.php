<?php
session_start();
require '../../config/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id_no'];
        $_SESSION['username'] = $user['email'];
        header("Location: dashboard.php");
        exit;
    } else {
        echo "<script>alert('Invalid username or password'); window.location='login.html';</script>";
    }
}
?>
