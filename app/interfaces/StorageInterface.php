<?php
interface StorageInterface {
    public function getConnection(): PDO;
    public function checkPermission($userId, $permissionName): bool;
}
?>