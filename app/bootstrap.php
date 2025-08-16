<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/factories/DatabaseFactory.php';

// Always include global flash renderer
require_once __DIR__ . '/views/layouts/partials/FlashGlobal.php';

// Return database connection for controllers
return DatabaseFactory::create(DB_DRIVER);
