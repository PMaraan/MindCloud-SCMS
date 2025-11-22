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
    private RBAC $rbac;

    /** Keep role groupings parallel with Syllabus Templates */
    private array $GLOBAL_ROLES  = ['VPAA','VPAA Secretary'];
    private array $DEAN_ROLES    = ['Dean'];
    private array $CHAIR_ROLES   = ['Chair'];

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
        $this->userModel = new UserModel($db);
        $this->model     = new SyllabiModel($db);
        $this->rbac      = new RBAC($db);
    }

    /**
     * Index – aligned with Syllabus Templates UX:
     *   /dashboard?page=syllabi
     *   /dashboard?page=syllabi&college={id}
     *   /dashboard?page=syllabi&college={id}&program={id}
     */
    public function index(): string
    {
        $this->rbac->require((string)$_SESSION['user_id'], Permissions::SYLLABI_VIEW);

        $user      = ($this->userModel)->getUserProfile((string)$_SESSION['user_id']);
        $role      = (string)($user['role_name'] ?? '');
        $collegeId = isset($user['college_id']) ? (int)$user['college_id'] : null;
        $programId = isset($user['program_id']) ? (int)$user['program_id'] : null;

        $ASSET_BASE = (defined('BASE_PATH') ? BASE_PATH : '') . '/public';
        $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $PAGE_KEY = 'syllabi';

        $qCollege = isset($_GET['college']) ? (int)$_GET['college'] : null;
        $qProgram = isset($_GET['program']) ? (int)$_GET['program'] : null;

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

        // AAO Roles: Global folders mode (no college param)
        if(in_array($role, $this->GLOBAL_ROLES, true)) {
            $colleges = $this->model->getCollegesForFolders();
            
            // If college param not provided, render global-folders mode
            if ($qCollege === null) {
                $colleges = $this->model->getCollegesForFolders();
                $view['mode']     = 'global-folders';
                $view['colleges'] = $colleges;
                $view['canCreate'] = $this->canCreate($role, $collegeId, $programId);
                return $this->render('index', $view);
            }

            // If college param provided, render college mode

            // programs should be an array[
            //  'program' => [...],
            //  'syllabi' => [...],
            // ]
            $collegePrograms = $this->model->getProgramsByCollege($qCollege);
            $programs = [];
            foreach ($collegePrograms as $p) {
                $pid = (int)($p['program_id'] ?? 0);
                $programs[] = [
                    'program' => $p,
                    'syllabi' => $this->model->getProgramSyllabi($pid),
                ];
            }
            
            $view['mode'] = 'college';
            $view['college'] = $this->model->getCollege($qCollege);
            $view['programs'] = $programs;
            $view['canCreate'] = $this->canCreate($role, $collegeId, $programId);
            $view['showBackToFolders'] = true;
            return $this->render('index', $view);
        // Dean Roles: College mode only (college param ignored)    
        } elseif ($collegeId && in_array($role, $this->DEAN_ROLES, true)) {
            $qCollege = null;
            $view['mode'] = 'college';
            $view['college'] = $this->model->getCollege($collegeId);
            $collegePrograms = $this->model->getProgramsByCollege($collegeId);
            $programs = [];
            foreach ($collegePrograms as $p) {
                $pid = (int)($p['program_id'] ?? 0);
                $programs[] = [
                    'program' => $p,
                    'syllabi' => $this->model->getProgramSyllabi($pid),
                ];
            }
            $view['programs'] = $programs;
            $view['canCreate'] = $this->canCreate($role, $collegeId, $programId);
            // Values for create modal selects
            $view['colleges'] = [[ 'college_id' => $collegeId ?? '', 'short_name' => $user['college_short_name'] ?? '', 'college_name' => $user['college_name'] ?? '']]; 
            $view['courses']  = $this->model->getCoursesOfCollege($collegeId);

            return $this->render('index', $view);
        } elseif (in_array($role, $this->CHAIR_ROLES, true)) {
            // Chair Roles: Program mode only (college & program params ignored)
            $qCollege = $collegeId;
            $qProgram = $programId;
        }

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
        // AAO roles can't create syllabus in any mode; only allow viewing
        if (in_array($role, $this->GLOBAL_ROLES, true)) {
            return false;
        } elseif (in_array($role, $this->DEAN_ROLES, true)) {
            return true; // Deans can create in college mode
        } elseif (in_array($role, $this->CHAIR_ROLES, true)) {
            return true; // Chairs can create in program mode
        }
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
