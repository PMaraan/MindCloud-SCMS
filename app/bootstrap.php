<?php
// root/app/bootstrap.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/factories/DatabaseFactory.php';

return DatabaseFactory::create(DB_DRIVER);
?>