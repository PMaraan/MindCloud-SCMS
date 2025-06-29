<?php
$password = 'password';
$hash = password_hash($password, PASSWORD_ARGON2ID);  // or PASSWORD_ARGON2I
echo $hash;
?>