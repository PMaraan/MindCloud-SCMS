<?php
session_start();
session_unset();
session_destroy();
header("Location: ../../public/loginprototype.html"); //change address for deployment
exit;
?>
