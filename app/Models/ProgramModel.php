<?php
// root/app/models/ProgramModel.php
require_once __DIR__ . '/../interfaces/StorageInterface.php';

class ProgramModel {
    protected PDO $pdo;

    public function __construct(StorageInterface $db) {
        $this->pdo = $db->getConnection();
    }

    public function getAllPrograms() {
        $stmt = $this->pdo->prepare("
            SELECT p.program_id,
                    p.program_name,
                    d.department_id,
                    d.short_name AS department_short_name,
                    pc.chair_id,
                    u.fname || ' ' || COALESCE(u.mname || ' ', '') || u.lname AS full_name
            FROM programs p
            JOIN departments d ON p.college_id = d.department_id
            LEFT JOIN program_chairs pc ON p.program_id = pc.program_id
            LEFT JOIN users u ON pc.chair_id = u.id_no
            ORDER BY p.program_id ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>