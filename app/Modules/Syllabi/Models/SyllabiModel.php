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
 * - Uses $db->getConnection() (per your adapter standard).
 * - Keeps the "same assigned course_id" access rule pluggable via getAccessibleCourseIds().
 * - Search applies to title and filename (ILIKE).
 * - Pagination uses sanitized LIMIT/OFFSET (inline ints, driver-safe).
 *
 * ISO 25010: Maintainability
 * - Separation of concerns (no SQL in controllers).
 * - Readability and explicit parameter binding.
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
     * `college_id`. For compatibility with callers that expect `college_id` we
     * select `department_id AS college_id` so the returned rows keep the same key.
     *
     * @param int $collegeId department_id (college) to filter programs by
     * @return array<int,array<string,mixed>>
     */
    public function getProgramsByCollege(int $collegeId): array
    {
        $sql = "
            SELECT
                program_id,
                program_name,
                department_id AS college_id
            FROM public.programs
            WHERE department_id = :cid
            ORDER BY program_name
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cid' => $collegeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** One program. */
    public function getProgram(int $programId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT program_id, program_name, college_id FROM public.programs WHERE program_id = :pid");
        $stmt->execute([':pid' => $programId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Courses “by program” (approx): filter courses by the program’s college_id.
     * We don’t assume a program↔course mapping table; this keeps the modal useful
     * without schema changes.
     */
    public function getCoursesByProgramApprox(int $programId): array
    {
        $prog = $this->getProgram($programId);
        if (!$prog) return [];
        $cid = (int)$prog['college_id'];
        $stmt = $this->pdo->prepare("
            SELECT course_id, course_code, course_name
            FROM public.courses
            WHERE college_id = :cid
            ORDER BY course_code, course_name
        ");
        $stmt->execute([':cid' => $cid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        // For client-side filter UX, add data-program-id attribute support (same pid)
        foreach ($rows as &$r) { $r['program_id'] = $programId; }
        return $rows;
    }

    /** College-wide syllabi (no per-program split). */
    public function getCollegeSyllabi(int $collegeId): array
    {
        $sql = "
            SELECT
                s.syllabus_id, s.title, s.filename, s.version, s.status,
                s.course_id, c.course_code, c.course_name,
                s.program_id, p.program_name,
                s.updated_at
            FROM public.syllabi s
            LEFT JOIN public.courses  c ON c.course_id  = s.course_id
            LEFT JOIN public.programs p ON p.program_id = s.program_id
            WHERE c.college_id = :cid
            ORDER BY s.updated_at DESC, s.syllabus_id DESC
            LIMIT 120
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cid' => $collegeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Syllabi for one program. */
    public function getProgramSyllabi(int $programId): array
    {
        $sql = "
            SELECT
                s.syllabus_id, s.title, s.filename, s.version, s.status,
                s.course_id, c.course_code, c.course_name,
                s.program_id, p.program_name,
                s.updated_at
            FROM public.syllabi s
            LEFT JOIN public.courses  c ON c.course_id  = s.course_id
            LEFT JOIN public.programs p ON p.program_id = s.program_id
            WHERE s.program_id = :pid
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
     * - Dean roles: default-filter by their college's programs if you pass $collegeId (optional).
     * - Chair roles: default-filter by their $programId (if provided).
     * - Future: when you confirm a mapping table (e.g., faculty assigned courses),
     *   implement getAccessibleCourseIds($userId) and we’ll narrow by course_id IN (...)
     *
     * @return array{rows: array<int, array<string,mixed>>, total: int}
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

        // Base WHERE clauses & params
        $wheres = [];
        $params = [];

        // Text search on title/filename
        if ($q !== '') {
            $wheres[] = "(s.title ILIKE :q OR s.filename ILIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        // Role-based default scoping (light, until we plug exact course-access table)
        if (!in_array($role, $this->SYSTEM_ROLES, true)) {
            if (in_array($role, $this->CHAIR_ROLES, true) && $programId) {
                $wheres[] = "s.program_id = :pgid";
                $params[':pgid'] = $programId;
            } elseif (in_array($role, $this->DEAN_ROLES, true) && $collegeId) {
                // If dean has a college, derive program_ids from that college (cheap filter).
                // This assumes programs.program_id -> syllabi.program_id (FK).
                $wheres[] = "s.program_id IN (SELECT p.program_id FROM public.programs p WHERE p.college_id = :cid)";
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
                // Safe IN (...) with sanitized ints
                $in = implode(',', array_map('intval', $courseIds));
                $wheres[] = "s.course_id IN ($in)";
            }
        }

        $whereSql = count($wheres) ? ("WHERE " . implode(" AND ", $wheres)) : "";

        // COUNT
        $sqlCount = "SELECT COUNT(*) FROM public.syllabi s $whereSql";
        $stmtC = $this->pdo->prepare($sqlCount);
        foreach ($params as $k => $v) {
            // For arrays stringified to '{1,2}', bind as string
            $stmtC->bindValue($k, $v);
        }
        $stmtC->execute();
        $total = (int)$stmtC->fetchColumn();

        // DATA
        // Join courses/programs for nice columns (safe, both have FKs already)
        $sql = "
            SELECT
                s.syllabus_id, s.title, s.filename, s.version, s.status,
                s.course_id, c.course_code, c.course_name,
                s.program_id, p.program_name,
                s.updated_at
            FROM public.syllabi s
            LEFT JOIN public.courses  c ON c.course_id  = s.course_id
            LEFT JOIN public.programs p ON p.program_id = s.program_id
            $whereSql
            ORDER BY s.updated_at DESC, s.syllabus_id DESC
            LIMIT {$limit} OFFSET {$offset}";
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
     * Expected keys in $payload: title, course_id, program_id, version?, status?, noted_by?, approved_by?, source_template_id?, filename?, content?
     * Returns new syllabus_id.
     */
    public function createSyllabus(array $payload, string $userId): int
    {
        $title     = trim((string)($payload['title'] ?? 'Untitled'));
        $course_id = (int)($payload['course_id'] ?? 0);
        $program_id= (int)($payload['program_id'] ?? 0);

        if ($course_id <= 0 || $program_id <= 0) {
            throw new \InvalidArgumentException('course_id and program_id are required.');
        }

        $version   = (string)($payload['version'] ?? null);
        $status    = (string)($payload['status']  ?? 'draft');
        $noted_by  = (string)($payload['noted_by'] ?? '');
        $approved_by = (string)($payload['approved_by'] ?? '');
        $source_template_id = isset($payload['source_template_id']) ? (int)$payload['source_template_id'] : null;
        $filename  = (string)($payload['filename'] ?? '');
        $content   = $payload['content'] ?? new \stdClass(); // json

        $sql = "
            INSERT INTO public.syllabi
                (title, course_id, program_id, version, content, status, noted_by, approved_by, source_template_id, filename)
            VALUES
                (:title, :course_id, :program_id, :version, CAST(:content AS jsonb), :status, :noted_by, :approved_by, :source_template_id, :filename)
            RETURNING syllabus_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':title'    => $title,
            ':course_id'=> $course_id,
            ':program_id'=> $program_id,
            ':version'  => ($version === '' ? null : $version),
            ':content'  => json_encode($content, JSON_UNESCAPED_UNICODE),
            ':status'   => ($status === '' ? 'draft' : $status),
            ':noted_by' => ($noted_by === '' ? null : $noted_by),
            ':approved_by' => ($approved_by === '' ? null : $approved_by),
            ':source_template_id' => $source_template_id,
            ':filename' => ($filename === '' ? null : $filename),
        ]);
        return (int)$stmt->fetchColumn();
    }

    /** Update basic fields; sets updated_at = now(). */
    public function updateSyllabus(int $id, array $payload, string $userId): void
    {
        if ($id <= 0) throw new \InvalidArgumentException('Invalid id.');

        $title     = isset($payload['title']) ? trim((string)$payload['title']) : null;
        $course_id = isset($payload['course_id']) ? (int)$payload['course_id'] : null;
        $program_id= isset($payload['program_id']) ? (int)$payload['program_id'] : null;
        $version   = array_key_exists('version', $payload) ? (string)$payload['version'] : null;
        $status    = array_key_exists('status', $payload) ? (string)$payload['status'] : null;
        $noted_by  = array_key_exists('noted_by', $payload) ? (string)$payload['noted_by'] : null;
        $approved_by = array_key_exists('approved_by', $payload) ? (string)$payload['approved_by'] : null;
        $source_template_id = array_key_exists('source_template_id', $payload) ? (int)$payload['source_template_id'] : null;
        $filename  = array_key_exists('filename', $payload) ? (string)$payload['filename'] : null;
        $content   = array_key_exists('content', $payload) ? $payload['content'] : null;

        // Build dynamic SET list
        $sets = ["updated_at = CURRENT_TIMESTAMP"];
        $params = [':id' => $id];

        $this->maybeSet($sets, $params, 'title', $title);
        $this->maybeSet($sets, $params, 'course_id', $course_id);
        $this->maybeSet($sets, $params, 'program_id', $program_id);
        $this->maybeSet($sets, $params, 'version', $version, true);
        $this->maybeSet($sets, $params, 'status', $status, true);
        $this->maybeSet($sets, $params, 'noted_by', $noted_by, true);
        $this->maybeSet($sets, $params, 'approved_by', $approved_by, true);
        $this->maybeSet($sets, $params, 'source_template_id', $source_template_id, false);
        $this->maybeSet($sets, $params, 'filename', $filename, true);

        if ($content !== null) {
            $sets[] = "content = CAST(:content AS jsonb)";
            $params[':content'] = json_encode($content, JSON_UNESCAPED_UNICODE);
        }

        if (count($sets) <= 1) {
            // nothing to update except timestamp
            $sql = "UPDATE public.syllabi SET updated_at = CURRENT_TIMESTAMP WHERE syllabus_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            return;
        }

        $sql = "UPDATE public.syllabi SET " . implode(', ', $sets) . " WHERE syllabus_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /** Delete by id. */
    public function deleteSyllabus(int $id, string $userId): void
    {
        if ($id <= 0) throw new \InvalidArgumentException('Invalid id.');
        $stmt = $this->pdo->prepare("DELETE FROM public.syllabi WHERE syllabus_id = :id");
        $stmt->execute([':id' => $id]);
    }

    // =====================================================================
    // Sectioned data (mirrors Syllabus Templates)
    // - getAllColleges()
    // - getProgramsByCollege($collegeId)
    // - getCollegeGeneralSyllabi($collegeId)
    // - getProgramSyllabiExclusive($collegeId, $programId)
    // =====================================================================
    
    // -----------------------
    // Helpers
    // -----------------------

    /**
     * Placeholder: return a list of course_ids the user is allowed to see (for the “same assigned course id” rule).
     * Return null to skip narrowing (e.g., for system roles), or [] to deny all.
     *
     * Replace this with a real query when you share your assignment mapping (e.g., faculty_load, user_courses, etc.).
     */
    private function getAccessibleCourseIds(string $userId, string $role, ?int $collegeId, ?int $programId): ?array
    {
        // System roles see everything — do not narrow at course level.
        if (in_array($role, $this->SYSTEM_ROLES, true)) {
            return null;
        }

        // Chairs/Deans/Faculty (and everyone else) — narrow by explicit mapping.
        // Optional: also respect valid_from/valid_to if present.
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

        // If no mapping rows exist, you can choose to return [] (deny all) or null (fallback to role scoping).
        // To strictly enforce mapping, return [] when empty:
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
