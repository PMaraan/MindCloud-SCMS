<?php
// /app/Modules/Syllabi/Models/SyllabiModel.php
declare(strict_types=1);

namespace App\Modules\Syllabi\Models;

use App\Interfaces\StorageInterface;
use PDO;

/**
 * SyllabiModel
 *
 * Implements listing (search + pagination), create, update, delete for the `public.syllabi` table.
 * Updated to use `public.syllabi_programs` (many-to-many) instead of a single program_id column.
 */
final class SyllabiModel
{
    private StorageInterface $db;
    private PDO $pdo;

    /** Match role sets to your controllers (adjust if needed) */
    private array $SYSTEM_ROLES  = ['VPAA','Admin','Librarian','QA','Registrar'];
    private array $DEAN_ROLES    = ['Dean','College Dean'];
    private array $CHAIR_ROLES   = ['Program Chair','Department Chair','Coordinator'];

    public function __construct(StorageInterface $db)
    {
        $this->db  = $db;
        $this->pdo = $db->getConnection();
    }

    // --------- Simple read helpers for folders/college/program views ---------

    /** List colleges for the folders screen. */
    public function getCollegesForFolders(): array
    {
        $sql = "SELECT c.college_id, c.short_name, c.college_name
                FROM public.colleges c
                ORDER BY COALESCE(NULLIF(c.short_name,''), c.college_name) ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** One college by id. */
    public function getCollege(int $collegeId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT college_id, short_name, college_name FROM public.colleges WHERE college_id = :id");
        $stmt->execute([':id' => $collegeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Programs for a college.
     *
     * NOTE: the programs table uses `department_id` (departments table) rather than
     * `college_id`. We keep compatibility by selecting department_id AS college_id.
     */
    public function getProgramsByCollege(int $collegeId): array
    {
        $sql = "
            SELECT
                program_id,
                program_name,
                department_id AS college_id
            FROM public.programs
            WHERE department_id = :did
            ORDER BY program_name
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':did' => $collegeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** One program. */
    public function getProgram(int $programId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT program_id, program_name, department_id AS college_id FROM public.programs WHERE program_id = :pid");
        $stmt->execute([':pid' => $programId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get a list of all courses of the college
     */
    public function getCoursesOfCollege(int $collegeId): array
    {
        $college = $this->getCollege($collegeId); // college_id, short_name, college_name
        if (!$college) return [];
        $cid = (int)$college['college_id'];
        // course_id, course_code, course_name, college_id
        $stmt = $this->pdo->prepare("
            SELECT course_id, course_code, course_name
            FROM public.courses
            WHERE college_id = :cid
            ORDER BY course_code, course_name
        ");
        $stmt->execute([':cid' => $cid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** College-wide syllabi (no per-program split).
     * Returns program_ids/program_names arrays for each syllabus.
     */
    public function getCollegeSyllabi(int $collegeId): array
    {
        $sql = "
            SELECT
                s.syllabus_id, s.title, s.filename, s.version, s.status,
                s.course_id, c.course_code, c.course_name,
                array_remove(array_agg(DISTINCT p.program_name), NULL) AS program_names,
                array_remove(array_agg(DISTINCT sp.program_id), NULL) AS program_ids,
                (SELECT MIN(sp2.program_id) FROM public.syllabi_programs sp2 WHERE sp2.syllabus_id = s.syllabus_id) AS rep_program_id,
                (SELECT p2.program_name FROM public.programs p2 WHERE p2.program_id = (
                    SELECT MIN(sp3.program_id) FROM public.syllabi_programs sp3 WHERE sp3.syllabus_id = s.syllabus_id
                )) AS rep_program_name,
                s.updated_at
            FROM public.syllabi s
            LEFT JOIN public.courses  c ON c.course_id  = s.course_id
            LEFT JOIN public.syllabi_programs sp ON sp.syllabus_id = s.syllabus_id
            LEFT JOIN public.programs p ON p.program_id = sp.program_id
            WHERE c.college_id = :cid
            GROUP BY s.syllabus_id, s.title, s.filename, s.version, s.status, s.course_id, c.course_code, c.course_name, s.updated_at
            ORDER BY s.updated_at DESC, s.syllabus_id DESC
            LIMIT 120
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cid' => $collegeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Syllabi for one program.
     * Returns program_names/program_ids arrays (in practice these will contain the single program).
     */
    public function getProgramSyllabi(int $programId): array
    {
        $sql = "
            SELECT
                s.syllabus_id, s.title, s.filename, s.version, s.status,
                s.course_id, c.course_code, c.course_name,
                array_remove(array_agg(DISTINCT p.program_name), NULL) AS program_names,
                array_remove(array_agg(DISTINCT sp.program_id), NULL) AS program_ids,
                (SELECT MIN(sp2.program_id) FROM public.syllabi_programs sp2 WHERE sp2.syllabus_id = s.syllabus_id) AS rep_program_id,
                (SELECT p2.program_name FROM public.programs p2 WHERE p2.program_id = (
                    SELECT MIN(sp3.program_id) FROM public.syllabi_programs sp3 WHERE sp3.syllabus_id = s.syllabus_id
                )) AS rep_program_name,
                s.updated_at
            FROM public.syllabi s
            LEFT JOIN public.syllabi_programs sp ON sp.syllabus_id = s.syllabus_id
            LEFT JOIN public.courses  c ON c.course_id  = s.course_id
            LEFT JOIN public.programs p ON p.program_id = sp.program_id
            WHERE sp.program_id = :pid
            GROUP BY s.syllabus_id, s.title, s.filename, s.version, s.status, s.course_id, c.course_code, c.course_name, s.updated_at
            ORDER BY s.updated_at DESC, s.syllabus_id DESC
            LIMIT 120
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pid' => $programId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    /**
     * List syllabi with filters:
     * - System roles: see all (optionally filter by search).
     * - Dean roles: default-filter by their college's programs (if $collegeId passed).
     * - Chair roles: default-filter by their $programId (if provided).
     *
     * Returns ['rows'=>..., 'total'=>N]
     */
    public function listSyllabi(
        string $userId,
        string $role,
        ?int $collegeId,
        ?int $programId,
        int $pg,
        int $perpage,
        string $q
    ): array {
        $pg      = max(1, (int)$pg);
        $perpage = max(1, (int)$perpage);
        $offset  = ($pg - 1) * $perpage;
        $limit   = $perpage;

        // Base WHERE fragments (use EXISTS for program/college scoping)
        $wheres = [];
        $params = [];

        // Text search on title/filename
        if ($q !== '') {
            $wheres[] = "(s.title ILIKE :q OR s.filename ILIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        // Role-based scoping
        if (!in_array($role, $this->SYSTEM_ROLES, true)) {
            if (in_array($role, $this->CHAIR_ROLES, true) && $programId) {
                // restrict to syllabi that map to given programId
                $wheres[] = "EXISTS (
                    SELECT 1 FROM public.syllabi_programs sp_ch
                    WHERE sp_ch.syllabus_id = s.syllabus_id AND sp_ch.program_id = :pgid
                )";
                $params[':pgid'] = $programId;
            } elseif (in_array($role, $this->DEAN_ROLES, true) && $collegeId) {
                // restrict to syllabi mapped to any program under the college
                $wheres[] = "EXISTS (
                    SELECT 1 FROM public.syllabi_programs sp_de
                    JOIN public.programs pp ON pp.program_id = sp_de.program_id
                    WHERE sp_de.syllabus_id = s.syllabus_id AND pp.department_id = :cid
                )";
                $params[':cid'] = $collegeId;
            }
        }

        // Course access narrowing
        $courseIds = $this->getAccessibleCourseIds($userId, $role, $collegeId, $programId);
        if (is_array($courseIds)) {
            if (count($courseIds) === 0) {
                // Strict: deny all quickly
                $wheres[] = "1=0";
            } else {
                $in = implode(',', array_map('intval', $courseIds));
                $wheres[] = "s.course_id IN ($in)";
            }
        }

        $whereSql = count($wheres) ? ("WHERE " . implode(" AND ", $wheres)) : "";

        // COUNT distinct syllabi
        $sqlCount = "SELECT COUNT(DISTINCT s.syllabus_id) FROM public.syllabi s $whereSql";
        $stmtC = $this->pdo->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $stmtC->bindValue($k, $v);
        }
        $stmtC->execute();
        $total = (int)$stmtC->fetchColumn();

        // DATA: aggregate program names/ids and include a representative program id+name (rep_program_*)
        $sql = "
            SELECT
                s.syllabus_id, s.title, s.filename, s.version, s.status,
                s.course_id, c.course_code, c.course_name,
                array_remove(array_agg(DISTINCT p.program_name), NULL) AS program_names,
                array_remove(array_agg(DISTINCT sp.program_id), NULL) AS program_ids,
                (SELECT MIN(sp2.program_id) FROM public.syllabi_programs sp2 WHERE sp2.syllabus_id = s.syllabus_id) AS rep_program_id,
                (SELECT p2.program_name FROM public.programs p2 WHERE p2.program_id = (
                    SELECT MIN(sp3.program_id) FROM public.syllabi_programs sp3 WHERE sp3.syllabus_id = s.syllabus_id
                )) AS rep_program_name,
                s.updated_at
            FROM public.syllabi s
            LEFT JOIN public.courses  c ON c.course_id  = s.course_id
            LEFT JOIN public.syllabi_programs sp ON sp.syllabus_id = s.syllabus_id
            LEFT JOIN public.programs p ON p.program_id = sp.program_id
            $whereSql
            GROUP BY s.syllabus_id, s.title, s.filename, s.version, s.status, s.course_id, c.course_code, c.course_name, s.updated_at
            ORDER BY s.updated_at DESC, s.syllabus_id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Create a syllabus.
     * Accepts either 'program_id' (int) for legacy callers or 'program_ids' (array) for multiple mappings.
     * Expected keys in $payload: title, course_id, program_id|program_ids, version?, status?, filename?, content?
     * Returns new syllabus_id.
     */
    public function createSyllabus(array $payload, string $userId): int
    {
        $title     = trim((string)($payload['title'] ?? 'Untitled'));
        $course_id = (int)($payload['course_id'] ?? 0);

        // program mapping(s)
        $programIds = [];
        if (isset($payload['program_ids']) && is_array($payload['program_ids'])) {
            foreach ($payload['program_ids'] as $pid) {
                $p = (int)$pid;
                if ($p > 0) $programIds[] = $p;
            }
        } elseif (isset($payload['program_id'])) {
            $p = (int)$payload['program_id'];
            if ($p > 0) $programIds[] = $p;
        }

        if ($course_id <= 0 || count($programIds) === 0) {
            throw new \InvalidArgumentException('course_id and at least one program_id are required.');
        }

        $version   = (string)($payload['version'] ?? null);
        $status    = (string)($payload['status']  ?? 'draft');
        $filename  = (string)($payload['filename'] ?? '');
        $content   = $payload['content'] ?? new \stdClass(); // json

        $pdo = $this->pdo;
        $pdo->beginTransaction();
        try {
            $sql = "
                INSERT INTO public.syllabi
                    (title, course_id, version, content, status, filename)
                VALUES
                    (:title, :course_id, :version, CAST(:content AS jsonb), :status, :filename)
                RETURNING syllabus_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title'    => $title,
                ':course_id'=> $course_id,
                ':version'  => ($version === '' ? null : $version),
                ':content'  => json_encode($content, JSON_UNESCAPED_UNICODE),
                ':status'   => ($status === '' ? 'draft' : $status),
                ':filename' => ($filename === '' ? null : $filename),
            ]);
            $newId = (int)$stmt->fetchColumn();

            // insert mappings into syllabi_programs
            $ins = $pdo->prepare("INSERT INTO public.syllabi_programs (syllabus_id, program_id) VALUES (:sid, :pid) ON CONFLICT DO NOTHING");
            foreach ($programIds as $pid) {
                $ins->execute([':sid' => $newId, ':pid' => $pid]);
            }

            $pdo->commit();
            return $newId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Update basic fields; sets updated_at = now().
     * If payload contains 'program_ids' (array) the mappings are replaced atomically.
     */
    public function updateSyllabus(int $id, array $payload, string $userId): void
    {
        if ($id <= 0) throw new \InvalidArgumentException('Invalid id.');

        $title     = isset($payload['title']) ? trim((string)$payload['title']) : null;
        $course_id = isset($payload['course_id']) ? (int)$payload['course_id'] : null;
        $version   = array_key_exists('version', $payload) ? (string)$payload['version'] : null;
        $status    = array_key_exists('status', $payload) ? (string)$payload['status'] : null;
        $filename  = array_key_exists('filename', $payload) ? (string)$payload['filename'] : null;
        $content   = array_key_exists('content', $payload) ? $payload['content'] : null;

        // optional program_ids replacement
        $programIds = null;
        if (array_key_exists('program_ids', $payload)) {
            $programIds = [];
            if (is_array($payload['program_ids'])) {
                foreach ($payload['program_ids'] as $pid) {
                    $p = (int)$pid;
                    if ($p > 0) $programIds[] = $p;
                }
            }
            // If empty array provided, we'll remove all mappings (caller explicitly asked).
        }

        // Build dynamic SET list
        $sets = ["updated_at = CURRENT_TIMESTAMP"];
        $params = [':id' => $id];

        $this->maybeSet($sets, $params, 'title', $title);
        $this->maybeSet($sets, $params, 'course_id', $course_id);
        $this->maybeSet($sets, $params, 'version', $version, true);
        $this->maybeSet($sets, $params, 'status', $status, true);
        $this->maybeSet($sets, $params, 'filename', $filename, true);

        if ($content !== null) {
            $sets[] = "content = CAST(:content AS jsonb)";
            $params[':content'] = json_encode($content, JSON_UNESCAPED_UNICODE);
        }

        $pdo = $this->pdo;
        $pdo->beginTransaction();
        try {
            if (count($sets) <= 1) {
                // nothing to update except timestamp
                $sql = "UPDATE public.syllabi SET updated_at = CURRENT_TIMESTAMP WHERE syllabus_id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $id]);
            } else {
                $sql = "UPDATE public.syllabi SET " . implode(', ', $sets) . " WHERE syllabus_id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }

            // Replace program mappings if caller provided program_ids (can be empty array to clear)
            if ($programIds !== null) {
                // delete existing
                $del = $pdo->prepare("DELETE FROM public.syllabi_programs WHERE syllabus_id = :sid");
                $del->execute([':sid' => $id]);

                if (count($programIds) > 0) {
                    $ins = $pdo->prepare("INSERT INTO public.syllabi_programs (syllabus_id, program_id) VALUES (:sid, :pid) ON CONFLICT DO NOTHING");
                    foreach ($programIds as $pid) {
                        $ins->execute([':sid' => $id, ':pid' => $pid]);
                    }
                }
            }

            $pdo->commit();
            return;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Delete by id. */
    public function deleteSyllabus(int $id, string $userId): void
    {
        if ($id <= 0) throw new \InvalidArgumentException('Invalid id.');
        $stmt = $this->pdo->prepare("DELETE FROM public.syllabi WHERE syllabus_id = :id");
        $stmt->execute([':id' => $id]);
        // syllabi_programs will cascade if FK ON DELETE CASCADE; otherwise left orphan-cleanup can be added.
    }

    // -----------------------
    // Helpers
    // -----------------------

    /**
     * Placeholder: return a list of course_ids the user is allowed to see (for the “same assigned course id” rule).
     * Return null to skip narrowing (e.g., for system roles), or [] to deny all.
     */
    private function getAccessibleCourseIds(string $userId, string $role, ?int $collegeId, ?int $programId): ?array
    {
        // System roles see everything — do not narrow at course level.
        if (in_array($role, $this->SYSTEM_ROLES, true)) {
            return null;
        }

        // Chairs/Deans/Faculty (and everyone else) — narrow by explicit mapping.
        $sql = "
            SELECT uca.course_id
            FROM public.user_course_access uca
            WHERE uca.user_id_no = :uid
            AND (uca.valid_from IS NULL OR uca.valid_from <= CURRENT_DATE)
            AND (uca.valid_to   IS NULL OR uca.valid_to   >= CURRENT_DATE)
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $ids  = array_map(static fn($r) => (int)$r['course_id'], $rows);

        // If no mapping rows exist, return null to fallback to role scoping
        return (count($ids) ? $ids : null);
    }

    /**
     * Add a field to SET list if value is not null. If $emptyToNull is true, empty strings become NULL.
     */
    private function maybeSet(array &$sets, array &$params, string $col, $val, bool $emptyToNull = false): void
    {
        if ($val === null) return;
        if ($emptyToNull && $val === '') $val = null;
        $sets[] = "{$col} = :{$col}";
        $params[":{$col}"] = $val;
    }
}
