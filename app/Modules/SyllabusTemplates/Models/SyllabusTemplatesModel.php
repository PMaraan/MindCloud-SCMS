<?php
// /app/Modules/SyllabusTemplates/Models/SyllabusTemplatesModel.php
declare(strict_types=1);

namespace App\Modules\SyllabusTemplates\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class SyllabusTemplatesModel
{
    private StorageInterface $db;
    private PDO $pdo;

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        // MindCloud standard: Postgres adapter exposes getConnection(): \PDO
        $this->pdo = $db->getConnection();
    }

    /** Return all colleges (used by VPAA/Admin global view) */
    public function getAllColleges(): array
    {
        $sql = "
        SELECT 
            d.department_id AS college_id,                -- alias for UI
            d.short_name,
            d.department_name AS college_name
        FROM public.departments d
        WHERE d.is_college = TRUE
        ORDER BY d.short_name ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Programs of a college (shown for deans; or all for global view’s college sections) */
    public function getProgramsByCollege(int $departmentId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT program_id, program_name
            FROM public.programs
            WHERE department_id = :did
            ORDER BY program_name ASC"
        );
        $stmt->execute([':did' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Return programs assigned to a Chair (by user id).
     * Expected to read from a programs <-> chairs join table (program_chairs).
     * Returns an array of ['program_id'=>int, 'program_name'=>string].
     * If the underlying join table is missing or query fails, returns [] (caller should fallback).
     */
    public function getProgramsForChair(string $userId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT p.program_id, p.program_name
                 FROM public.programs p
                 JOIN public.program_chairs pc ON pc.program_id = p.program_id
                 WHERE pc.chair_id = :uid
                 ORDER BY p.program_name ASC"
            );
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            // Defensive: some schemas may not have program_chairs — let controller fallback gracefully
            return [];
        }
    }

    /**
     * Fetch programs by an array of program_ids (used for profile-based fallbacks).
     * Input: array of ints (ids). Returns array of ['program_id', 'program_name'].
     */
    public function getProgramsByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
        if (empty($ids)) return [];

        // Build positional placeholders safely
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT program_id, program_name FROM public.programs WHERE program_id IN ($placeholders) ORDER BY program_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Courses of a program (name-based labels).
     *  Uses program's department_id to fetch courses with matching courses.college_id.
     *  Returns: [{ id: int, label: string }, ...]
     */
    public function getCoursesByProgram(int $programId): array
    {
        $sql = "
            SELECT
                c.course_id AS id,
                c.course_name AS label
            FROM public.courses c
            WHERE c.college_id = (
                SELECT p.department_id
                FROM public.programs p
                WHERE p.program_id = :pid
            )
            ORDER BY c.course_name ASC
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([':pid' => $programId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Global/General templates (no assignments to college nor program)
     *  Also include human-friendly names so views/JS can show College/Program/Course.
     */
    public function getGlobalTemplates(): array
    {
        $sql = "
        SELECT
            t.*,
            NULL::text AS college_name,
            NULL::text AS college_short_name,
            NULL::text AS program_name,
            NULL::text AS course_name
        FROM public.syllabus_templates t
        WHERE t.scope = 'global'
          AND NOT EXISTS (SELECT 1 FROM public.syllabus_template_departments td WHERE td.template_id = t.template_id)
          AND NOT EXISTS (SELECT 1 FROM public.syllabus_template_programs    p  WHERE p.template_id  = t.template_id)
        ORDER BY t.updated_at DESC, t.title ASC
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * College General templates:
     *  - visible to the college (either explicitly assigned via tcol,
     *    or global (no assignments at all))
     *  - NOT program-specific (no tprog rows at all)
     */
     public function getCollegeGeneralTemplates(int $departmentId): array
    {
        $stmt = $this->pdo->prepare("
        WITH visible AS (
            SELECT t.*,
                   d.department_name AS college_name,
                   d.short_name       AS college_short_name,
                   NULL::text         AS program_name,
                   NULL::text         AS course_name
            FROM public.syllabus_templates t
            LEFT JOIN public.departments d ON d.department_id = t.owner_department_id
            WHERE
              (
                -- visible via explicit department assignment
                EXISTS (
                  SELECT 1
                  FROM public.syllabus_template_departments td
                  WHERE td.template_id = t.template_id
                    AND td.department_id = :did
                )
              )
              OR
              (
                -- global (no dept/program assignments)
                t.scope = 'global'
                AND NOT EXISTS (SELECT 1 FROM public.syllabus_template_departments td WHERE td.template_id = t.template_id)
                AND NOT EXISTS (SELECT 1 FROM public.syllabus_template_programs p WHERE p.template_id = t.template_id)
              )
        )
        SELECT v.*
        FROM visible v
        WHERE v.program_id IS NULL
          AND NOT EXISTS (
            SELECT 1 FROM public.syllabus_template_programs p
            WHERE p.template_id = v.template_id
          )
        ORDER BY v.updated_at DESC, v.title ASC
        ");
        $stmt->execute([':did' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Program-exclusive templates:
     *  - Must be visible to the college (has tcol with that college)
     *  - Must be assigned to THIS program
     *  - Must NOT be assigned to any other program (exclusive)
     */
    public function getProgramExclusiveTemplates(int $departmentId, int $programId): array
    {
        $stmt = $this->pdo->prepare("
        SELECT
            t.*,
            d.department_name AS college_name,
            d.short_name       AS college_short_name,
            lp.program_name    AS program_name,
            NULL::text         AS course_name
        FROM public.syllabus_templates t
        LEFT JOIN public.departments d
               ON d.department_id = t.owner_department_id
        LEFT JOIN public.programs lp
               ON lp.program_id = COALESCE(t.program_id, :pid)
        WHERE
            t.owner_department_id = :did
            AND t.scope IN ('program','course')
            AND (
                  t.program_id = :pid
                  OR EXISTS (
                        SELECT 1
                        FROM public.syllabus_template_programs sp
                        WHERE sp.template_id = t.template_id
                          AND sp.program_id   = :pid
                  )
                )
            AND (
                  t.program_id IS NULL
                  OR t.program_id = :pid
                )
            AND NOT EXISTS (
                  SELECT 1
                  FROM public.syllabus_template_programs sp2
                  WHERE sp2.template_id = t.template_id
                    AND sp2.program_id <> :pid
                )
        ORDER BY t.updated_at DESC, t.title ASC
        ");
        $stmt->execute([':did' => $departmentId, ':pid' => $programId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Determine a user’s effective sections based on role context.
     * This is a thin utility for the controller’s agile pass.
     */
    public function buildSectionsForUser(array $user): array
    {
        $role = (string)($user['role_name'] ?? '');
        $collegeId = isset($user['college_id']) ? (int)$user['college_id'] : null;
        $programId = isset($user['program_id']) ? (int)$user['program_id'] : null;

        // Role maps (edit here to adjust behavior)
        $GLOBAL_ROLES  = ['VPAA','VPAA Secretary']; // AAO only
        $DEAN_ROLES    = ['Dean'];
        $CHAIR_ROLES   = ['Chair'];

        // GLOBAL: see everything (global + each college [general + per-program])
        if (in_array($role, $GLOBAL_ROLES, true)) {
            $global = $this->getGlobalTemplates();
            $colleges = $this->getAllColleges();

            $collegeSections = [];
            foreach ($colleges as $c) {
                $cid = (int)$c['college_id'];
                $general = $this->getCollegeGeneralTemplates($cid);
                $programs = $this->getProgramsByCollege($cid);

                $programSections = [];
                foreach ($programs as $p) {
                    $pid = (int)$p['program_id'];
                    $programSections[] = [
                        'program'   => $p,
                        'templates' => $this->getProgramExclusiveTemplates($cid, $pid),
                    ];
                }
                $collegeSections[] = [
                    'college'  => $c,
                    'general'  => $general,
                    'programs' => $programSections,
                ];
            }

            return [
                'mode'     => 'global',
                'global'   => $global,
                'colleges' => $collegeSections,
            ];
        }

        // DEAN: see their college (general + per-program)
        if ($collegeId && in_array($role, $DEAN_ROLES, true)) {
            $general = $this->getCollegeGeneralTemplates($collegeId);
            $programs = $this->getProgramsByCollege($collegeId);

            $programSections = [];
            foreach ($programs as $p) {
                $pid = (int)$p['program_id'];
                $programSections[] = [
                    'program'   => $p,
                    'templates' => $this->getProgramExclusiveTemplates($collegeId, $pid),
                ];
            }

            return [
                'mode'    => 'college',
                'college' => [
                    'college_id'   => $collegeId,
                    'short_name'   => (string)($user['college_short_name'] ?? ''),
                    'college_name' => (string)($user['college_name'] ?? ''),
                ],
                'general'  => $general,
                'programs' => $programSections,
            ];
        }

        // CHAIR: a general section (college-level) + their single program section
        if ($collegeId && $programId && in_array($role, $CHAIR_ROLES, true)) {
            $general = $this->getCollegeGeneralTemplates($collegeId);
            $progTemplates = $this->getProgramExclusiveTemplates($collegeId, $programId);

            return [
                'mode'    => 'program',
                'college' => [
                    'college_id'   => $collegeId,
                    'short_name'   => (string)($user['college_short_name'] ?? ''),
                    'college_name' => (string)($user['college_name'] ?? ''),
                ],
                'program' => [
                    'program_id'   => $programId,
                    'program_code' => (string)($user['program_code'] ?? ''),
                    'program_name' => (string)($user['program_name'] ?? ''),
                ],
                'general'  => $general,
                'program_templates' => $progTemplates,
            ];
        }

        // Fallback: treat as global read-only (global only)
        return [
            'mode'   => 'global',
            'global' => $this->getGlobalTemplates(),
            'colleges' => [],
        ];
    }

    public function cloneTemplateWithMeta(int $sourceId, array $meta): int
    {
        $pdo = $this->pdo;

        // Load source template (content + version we'll copy)
        $src = $pdo->prepare("SELECT content, version FROM public.syllabus_templates WHERE template_id = :id");
        $src->execute([':id' => $sourceId]);
        $row = $src->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            throw new \RuntimeException('Source template not found.');
        }

        $content = $row['content'] ?? null;
        $version = $row['version'] ?? null;

        // Insert new template
        $stmt = $pdo->prepare("
            INSERT INTO public.syllabus_templates
                (scope, owner_department_id, program_id, course_id, title, version, status, content, created_by)
            VALUES
                (:scope, :dept, :prog, :course, :title, :version, :status, :content, :created_by)
            RETURNING template_id
        ");

        $stmt->execute([
            ':scope'      => $meta['scope'],
            ':dept'       => $meta['owner_department_id'] ?? null,
            ':prog'       => $meta['program_id'] ?? null,
            ':course'     => $meta['course_id'] ?? null,
            ':title'      => $meta['title'],
            ':version'    => $version,
            ':status'     => $meta['status'] ?? 'draft',
            ':content'    => $content,
            ':created_by' => $meta['created_by'] ?? '',
        ]);

        $newId = (int)$stmt->fetchColumn();
        if ($newId <= 0) throw new \RuntimeException('Failed to insert duplicate.');

        return $newId;
    }

    public function createTemplate(array $data): int
    {
        $pdo = $this->pdo;
        $pdo->beginTransaction();

        try {
            $title  = $data['title'] ?? '';
            $scope  = $data['scope'] ?? 'global';
            $colId  = $data['college_id'] ?? null;
            $progId = $data['program_id'] ?? null;
            $courseId = $data['course_id'] ?? null;
            $createdBy = $data['created_by'] ?? '';

            if (!$title) {
                throw new \RuntimeException('Title is required.');
            }

            if ($scope === 'global') {
                $stmt = $pdo->prepare("INSERT INTO public.syllabus_templates (scope, title, status, content, created_by)
                                    VALUES ('global', :title, 'draft', '{}'::jsonb, :by)
                                    RETURNING template_id");
                $stmt->execute([':title' => $title, ':by' => $createdBy]);
                $tid = (int)$stmt->fetchColumn();

            } elseif ($scope === 'college') {
                $stmt = $pdo->prepare("INSERT INTO public.syllabus_templates (scope, title, status, content, created_by, owner_department_id)
                                    VALUES ('college', :title, 'draft', '{}'::jsonb, :by, :dept)
                                    RETURNING template_id");
                $stmt->execute([':title' => $title, ':by' => $createdBy, ':dept' => $colId]);
                $tid = (int)$stmt->fetchColumn();

            } elseif ($scope === 'program') {
                $deptId = (int)$this->pdo->query("SELECT department_id FROM public.programs WHERE program_id = {$progId}")->fetchColumn();
                if (!$deptId) throw new \RuntimeException('Program has no college department.');

                $stmt = $pdo->prepare("INSERT INTO public.syllabus_templates (scope, title, status, content, created_by, owner_department_id, program_id)
                                    VALUES ('program', :title, 'draft', '{}'::jsonb, :by, :dept, :pid)
                                    RETURNING template_id");
                $stmt->execute([':title' => $title, ':by' => $createdBy, ':dept' => $deptId, ':pid' => $progId]);
                $tid = (int)$stmt->fetchColumn();

            } elseif ($scope === 'course') {
                $deptId = (int)$this->pdo->query("SELECT department_id FROM public.programs WHERE program_id = {$progId}")->fetchColumn();
                if (!$deptId) throw new \RuntimeException('Program has no college department.');
                if (!$courseId) throw new \RuntimeException('Course is required for course scope.');

                $stmt = $pdo->prepare("INSERT INTO public.syllabus_templates (scope, title, status, content, created_by,
                                                    owner_department_id, program_id, course_id)
                                    VALUES ('course', :title, 'draft', '{}'::jsonb, :by, :dept, :pid, :cid)
                                    RETURNING template_id");
                $stmt->execute([':title' => $title, ':by' => $createdBy, ':dept' => $deptId, ':pid' => $progId, ':cid' => $courseId]);
                $tid = (int)$stmt->fetchColumn();

            } else {
                throw new \RuntimeException('Invalid scope.');
            }

            $pdo->commit();
            return $tid;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    
}
