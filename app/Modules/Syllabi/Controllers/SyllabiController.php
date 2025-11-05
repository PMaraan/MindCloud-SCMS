<?php
// /app/Modules/Syllabi/Controllers/SyllabiController.php
declare(strict_types=1);

namespace App\Modules\Syllabi\Controllers;

use App\Interfaces\StorageInterface;
use App\Security\RBAC;
use App\Config\Permissions;
use App\Models\UserModel;
use App\Modules\Syllabi\Models\SyllabiModel;

/**
 * SyllabiController
 *
 * MVC Controller for the Syllabi module.
 * - Mirrors Syllabus Templates’ conventions (PAGE_KEY usage, RBAC, clean controller, no raw SQL).
 * - Prepares global pagination pattern (pager keys: total, pg, perpage, baseUrl, query, from, to).
 * - Abstracts DB operations via SyllabiModel (you will replace stubbed methods later).
 *
 * ISO 25010: Maintainability
 * - Separation of concerns (controller delegates to model).
 * - Readability (explicit variables, comments, phpdoc).
 * - Modularity (no framework; Bootstrap only).
 */
final class SyllabiController
{
    private StorageInterface $db;
    private UserModel $userModel;
    private SyllabiModel $model;

    /** Keep role groupings parallel with Syllabus Templates */
    private array $SYSTEM_ROLES  = ['VPAA','Admin','Librarian','QA','Registrar'];
    private array $DEAN_ROLES    = ['Dean','College Dean'];
    private array $CHAIR_ROLES   = ['Program Chair','Department Chair','Coordinator'];

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
        $this->userModel = new UserModel($db);
        $this->model     = new SyllabiModel($db);
    }

    /**
     * Index – aligned with Syllabus Templates UX:
     *   /dashboard?page=syllabi
     *   /dashboard?page=syllabi&college={id}
     *   /dashboard?page=syllabi&college={id}&program={id}
     */
    public function index(): string
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABI_VIEW);

        $user      = (new \App\Models\UserModel($this->db))->getUserProfile((string)$_SESSION['user_id']);
        $role      = (string)($user['role_name'] ?? '');
        $collegeId = isset($user['college_id']) ? (int)$user['college_id'] : null;
        $programId = isset($user['program_id']) ? (int)$user['program_id'] : null;

        $ASSET_BASE = (defined('BASE_PATH') ? BASE_PATH : '') . '/public';
        $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $PAGE_KEY = 'syllabi';

        $qCollege = isset($_GET['college']) ? (int)$_GET['college'] : 0;
        $qProgram = isset($_GET['program']) ? (int)$_GET['program'] : 0;

        // Defaults for view
        $view = [
            'ASSET_BASE' => $ASSET_BASE,
            'esc'        => $esc,
            'PAGE_KEY'   => $PAGE_KEY,
            'user'       => $user,
            'role'       => $role,
            'canCreate'  => true, // keep UX; refine with policy later
            'canEdit'    => true,
            'canDelete'  => true,
        ];

        // System folders mode (no college param)
        if ($qCollege <= 0) {
            $colleges = $this->model->getCollegesForFolders();
            $view += [
                'mode'     => 'system-folders',
                'colleges' => $colleges,
            ];
            return $this->render('index', $view);
        }

        // College or Program mode
        $college = $this->model->getCollege($qCollege);
        if (!$college) {
            $_SESSION['flash'] = ['type'=>'danger','message'=>'College not found.'];
            header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabi');
            exit;
        }

        // Lists for Create modal
        $allColleges      = $this->model->getCollegesForFolders();
        $programsOfCollege= $this->model->getProgramsByCollege($qCollege);
        $coursesOfProgram = $qProgram > 0
            ? $this->model->getCoursesByProgramApprox($qProgram) // see model note
            : []; // filled on demand client-side if needed

        if ($qProgram > 0) {
            // PROGRAM mode
            $program = $this->model->getProgram($qProgram);
            if (!$program || (int)($program['college_id'] ?? 0) !== $qCollege) {
                $_SESSION['flash'] = ['type'=>'danger','message'=>'Program not found for this college.'];
                header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabi&college=' . $qCollege);
                exit;
            }

            $general = $this->model->getCollegeSyllabi($qCollege);
            $programSyllabi = $this->model->getProgramSyllabi($qProgram);

            $view += [
                'mode'             => 'program',
                'showBackToFolders'=> true,
                'college'          => $college,
                'general'          => $general,
                'program'          => $program,
                'program_syllabi'  => $programSyllabi,
                'allColleges'      => $allColleges,
                'programsOfCollege'=> $programsOfCollege,
                'coursesOfProgram' => $coursesOfProgram,
            ];
            return $this->render('index', $view);
        }

        // COLLEGE mode
        $general  = $this->model->getCollegeSyllabi($qCollege);
        $sections = [];
        foreach ($programsOfCollege as $p) {
            $pid = (int)($p['program_id'] ?? 0);
            $sections[] = [
                'program' => $p,
                'syllabi' => $this->model->getProgramSyllabi($pid),
            ];
        }

        $view += [
            'mode'             => 'college',
            'showBackToFolders'=> true,
            'college'          => $college,
            'general'          => $general,
            'programs'         => $sections,
            'allColleges'      => $allColleges,
            'programsOfCollege'=> $programsOfCollege,
            'coursesOfProgram' => $coursesOfProgram,
        ];
        return $this->render('index', $view);
    }


    /** POST /dashboard?page=syllabi&action=create */
    public function create(): void
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABI_CREATE);

        // NOTE: keep controller slim; model will validate & insert later when DB schema is ready.
        $payload = [
            'title'   => trim((string)($_POST['title'] ?? '')),
            'course'  => trim((string)($_POST['course'] ?? '')),
            'section' => trim((string)($_POST['section'] ?? '')),
            // more fields later…
        ];

        try {
            $id = $this->model->createSyllabus($payload, (string)($_SESSION['user_id'] ?? ''));
            $_SESSION['flash'] = ['type'=>'success','message'=>'Syllabus created.'];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type'=>'danger','message'=>'Create failed: '.$e->getMessage()];
        }

        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabi');
        exit;
    }

    /** POST /dashboard?page=syllabi&action=update */
    public function update(): void
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABI_EDIT);

        $id = isset($_POST['syllabus_id']) ? (int)$_POST['syllabus_id'] : 0;
        $payload = [
            'title'   => trim((string)($_POST['title'] ?? '')),
            'course'  => trim((string)($_POST['course'] ?? '')),
            'section' => trim((string)($_POST['section'] ?? '')),
        ];

        try {
            if ($id <= 0) throw new \RuntimeException('Missing id.');
            $this->model->updateSyllabus($id, $payload, (string)($_SESSION['user_id'] ?? ''));
            $_SESSION['flash'] = ['type'=>'success','message'=>'Syllabus updated.'];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type'=>'danger','message'=>'Update failed: '.$e->getMessage()];
        }

        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabi');
        exit;
    }

    /** POST /dashboard?page=syllabi&action=delete */
    public function delete(): void
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABI_DELETE);

        $id = isset($_POST['syllabus_id']) ? (int)$_POST['syllabus_id'] : 0;

        try {
            if ($id <= 0) throw new \RuntimeException('Missing id.');
            $this->model->deleteSyllabus($id, (string)($_SESSION['user_id'] ?? ''));
            $_SESSION['flash'] = ['type'=>'success','message'=>'Syllabus deleted.'];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type'=>'danger','message'=>'Delete failed: '.$e->getMessage()];
        }

        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabi');
        exit;
    }

    // ---- RBAC helpers (adjust later if needed) ----
    private function canCreate(string $role, ?int $collegeId, ?int $programId): bool
    {
        return true; // mirror templates UX; refine later with your exact policy
    }
    private function canEdit(string $role, ?int $collegeId, ?int $programId): bool
    {
        return true;
    }
    private function canDelete(string $role, ?int $collegeId, ?int $programId): bool
    {
        return true;
    }

    // ---- View renderer ----
    private function render(string $view, array $vars): string
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . "/Views/{$view}.php";
        return (string)ob_get_clean();
    }
}
