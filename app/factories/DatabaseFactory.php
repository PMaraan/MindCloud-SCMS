<?php
// root/app/factories/DatabaseFactory.php
namespace App\Factories;

use App\Interfaces\StorageInterface;
use App\Models\Postgres;
// use App\Models\MySQL;
// use App\Models\MockDB;

//require_once __DIR__ . '/../../config/config.php'; ...
final class DatabaseFactory {
    /**
     * Create a database connection based on driver.
     *
     * @param string $driver "pgsql" | "mysql" | "mock"
     * @return StorageInterface
     */
    public static function create(string $dbDriver): StorageInterface {
        switch(strtolower($dbDriver)) {
            case 'pgsql':
                //require_once __DIR__ . '/../models/Postgres.php'; ...
                return new Postgres();
            /*
            case 'mysql':
                return new MySQL();
            */
                /*
            case 'mock':
                return new MockDB();
            */
            default:
                throw new \InvalidArgumentException("Unsupported DB driver: {$dbDriver}");
                break;
        }
    }
}
// Note: This factory can be extended to support more drivers in the future.