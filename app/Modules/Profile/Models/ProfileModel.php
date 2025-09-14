<?php
// /app/Modules/Profile/Models/ProfileModel.php
declare(strict_types=1);

namespace App\Modules\Profile\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class ProfileModel
{
    private PDO $pdo;

    public function __construct(StorageInterface $db)
    {
        // Per your standard: StorageInterface::getConnection() returns \PDO
        $this->pdo = $db->getConnection();
    }

    /**
     * Fetch core profile info by id_no from users table.
     * Note: We do NOT select an avatar column to avoid errors if it doesn't exist yet.
     * If you later add an 'avatar' column, you can extend this to select it.
     */
    public function getByIdNo(string $idNo): ?array
    {
        $sql = 'SELECT id_no, fname, mname, lname, email FROM public.users WHERE id_no = ? LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * OPTIONAL (future):
     * If you add an avatar path column later, switch to this query:
     *
     *  SELECT id_no, fname, mname, lname, email, avatar
     *  FROM public.users WHERE id_no = ? LIMIT 1
     *
     * and then read $row['avatar'] in the controller.
     */
}
