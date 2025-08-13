<?php
session_start();
session_unset();
session_destroy();
header("Location: /MindCloud-SCMS/"); //change address for deployment ///public/index.php
exit;
?>
