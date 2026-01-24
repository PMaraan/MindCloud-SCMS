<?php
// /app/Interfaces/StorageInterface.php
namespace App\Interfaces;

interface StorageInterface {
    public function getConnection(): \PDO;
    //public function checkPermission($userId, $permissionName): bool;
}
?>