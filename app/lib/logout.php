<?php
session_start();
session_unset();
session_destroy();
header("Location: /MindCloud-SCMS/public/index.php"); //change address for deployment
exit;
?>
