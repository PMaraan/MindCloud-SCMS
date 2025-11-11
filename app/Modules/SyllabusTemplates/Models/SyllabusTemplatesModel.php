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
        WHERE NOT EXISTS (
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
            p.program_name     AS program_name,
            NULL::text         AS course_name
        FROM public.syllabus_templates t
        LEFT JOIN public.departments d ON d.department_id = t.owner_department_id
        LEFT JOIN public.programs p     ON p.program_id = t.program_id
        WHERE
            EXISTS (
              SELECT 1
              FROM public.syllabus_template_departments td
              WHERE td.template_id = t.template_id
                AND td.department_id = :did
            )
            AND EXISTS (
              SELECT 1
              FROM public.syllabus_template_programs sp
              WHERE sp.template_id = t.template_id
                AND sp.program_id   = :pid
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

        // Load source template (content + version we’ll copy)
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
                (scope, owner_department_id, program_id, course_id, title, version, status, content, source_template_id, created_by)
            VALUES
                (:scope, :dept, :prog, :course, :title, :version, :status, :content, :src, :created_by)
            RETURNING template_id
        ");

        $stmt->execute([
            ':scope'      => $meta['scope'],                         // 'global' | 'college' | 'program' | 'course'
            ':dept'       => $meta['owner_department_id'] ?? null,
            ':prog'       => $meta['program_id'] ?? null,
            ':course'     => $meta['course_id'] ?? null,
            ':title'      => $meta['title'],
            ':version'    => $version,                               // keep same version; you can reset to 'v1.0' if preferred
            ':status'     => $meta['status'] ?? 'draft',
            ':content'    => $content,
            ':src'        => $sourceId,
            ':created_by' => $meta['created_by'] ?? '',
        ]);

        $newId = (int)$stmt->fetchColumn();
        if ($newId <= 0) throw new \RuntimeException('Failed to insert duplicate.');

        // Triggers (mc_trg_tpl_program_scope_autosync + mc_validate_template_scope) will maintain link tables/consistency.
        return $newId;
    }
}
