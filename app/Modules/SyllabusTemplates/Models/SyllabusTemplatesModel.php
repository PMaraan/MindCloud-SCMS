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

    /** Return all colleges (used by VPAA/Admin system view) */
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

    /** Programs of a college (shown for deans; or all for system view’s college sections) */
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

    /** System → Global/General templates (no assignments to college nor program) */
    public function getSystemGlobalTemplates(): array
    {
        $sql = "
        SELECT t.*
        FROM public.syllabus_templates t
        WHERE t.scope = 'system'
          AND NOT EXISTS (SELECT 1 FROM public.syllabus_template_colleges c WHERE c.template_id = t.template_id)
          AND NOT EXISTS (SELECT 1 FROM public.syllabus_template_programs p WHERE p.template_id = t.template_id)
        ORDER BY t.updated_at DESC, t.title ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * College General templates:
     *  - visible to the college (either explicitly assigned via tcol,
     *    or system-global (no assignments at all))
     *  - NOT program-specific (no tprog rows at all)
     */
    public function getCollegeGeneralTemplates(int $departmentId): array
    {
        $stmt = $this->pdo->prepare("
        WITH visible AS (
            SELECT t.*
            FROM public.syllabus_templates t
            WHERE
            -- visible via explicit department assignment
            EXISTS (
                SELECT 1
                FROM public.syllabus_template_departments td
                WHERE td.template_id = t.template_id
                    AND td.department_id = :did
            )
            OR
            -- system-global (no dept/program assignments)
            (
                t.scope = 'system'
                AND NOT EXISTS (SELECT 1 FROM public.syllabus_template_departments td WHERE td.template_id = t.template_id)
                AND NOT EXISTS (SELECT 1 FROM public.syllabus_template_programs    p  WHERE p.template_id  = t.template_id)
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
        SELECT t.*
            FROM public.syllabus_templates t
        WHERE
            EXISTS (
            SELECT 1
                FROM public.syllabus_template_departments td
                WHERE td.template_id = t.template_id
                AND td.department_id = :did
            )
            AND EXISTS (
            SELECT 1
                FROM public.syllabus_template_programs p
                WHERE p.template_id = t.template_id
                AND p.program_id   = :pid
            )
            AND NOT EXISTS (
            SELECT 1
                FROM public.syllabus_template_programs p2
                WHERE p2.template_id = t.template_id
                AND p2.program_id <> :pid
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
        $SYSTEM_ROLES  = ['VPAA','Admin','Librarian','QA','Registrar'];
        $DEAN_ROLES    = ['Dean','College Dean'];
        $CHAIR_ROLES   = ['Program Chair','Department Chair','Coordinator'];

        // SYSTEM: see everything (global + each college [general + per-program])
        if (in_array($role, $SYSTEM_ROLES, true)) {
            $global = $this->getSystemGlobalTemplates();
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
                'mode'     => 'system',
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

        // Fallback: treat as system read-only (global only)
        return [
            'mode'   => 'system',
            'global' => $this->getSystemGlobalTemplates(),
            'colleges' => [],
        ];
    }
}
