<?php
session_start();
session_unset();
session_destroy();
header("Location: /"); //change address for deployment
exit;
?>
