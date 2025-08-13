<?php
// root/app/factories/DatabaseFactory.php
require_once __DIR__ . '/../../config/config.php';
class DatabaseFactory {
    public static function create(string $dbDriver): StorageInterface {
        switch(strtolower($dbDriver)) {
            case 'pgsql':
                require_once __DIR__ . '/../models/Postgres.php';
                return new Postgres();
                break;
            /*
            case 'mysql':
                require_once '/../models/MySQL.php';
                return new MySQL();
                break;
            */
                /*
            case 'mock':
                require_once '/../models/MockDB.php';
                return new MockDB();
                break;
            */
            default:
                throw new Exception("Unsupported DB driver: $dbDriver");
                break;
        }
    }
}
?>