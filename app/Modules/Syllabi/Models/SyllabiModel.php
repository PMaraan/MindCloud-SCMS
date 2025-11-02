<?php
// /app/Modules/Syllabi/Models/SyllabiModel.php
declare(strict_types=1);

namespace App\Modules\Syllabi\Models;

use App\Interfaces\StorageInterface;
use PDO;

/**
 * SyllabiModel
 *
 * Abstracted data-access layer for Syllabi.
 * - Uses $db->getConnection() per your adapter contract.
 * - All SQL is TBD (you will supply the real schema later).
 * - Methods return shapes expected by the controller & views.
 */
final class SyllabiModel
{
    private StorageInterface $db;
    private PDO $pdo;

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        $this->pdo = $db->getConnection();
    }

    /**
     * List syllabi for listing page (with role/college/program context).
     * Return shape:
     *   ['rows' => [ ... ], 'total' => 0]
     */
    public function listSyllabi(string $role, ?int $collegeId, ?int $programId, int $pg, int $perpage, string $q): array
    {
        // TODO: replace with real query when schema is provided.
        // For now, return an empty, well-formed payload.
        return [
            'rows'  => [],
            'total' => 0,
        ];
    }

    /** Create syllabus (return new id). */
    public function createSyllabus(array $payload, string $userId): int
    {
        // TODO: implement when schema is provided.
        // Placeholder: pretend insert OK and return fake id.
        return 1;
    }

    /** Update syllabus by id. */
    public function updateSyllabus(int $id, array $payload, string $userId): void
    {
        // TODO: implement when schema is provided.
    }

    /** Delete syllabus by id. */
    public function deleteSyllabus(int $id, string $userId): void
    {
        // TODO: implement when schema is provided.
    }
}
