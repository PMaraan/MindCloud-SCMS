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
     * Index (folders/sections) – mirrors Syllabus Templates layout
     * URL:
     *   /dashboard?page=syllabi
     *   /dashboard?page=syllabi&college={id}   (open a college folder)
     */
    public function index(): string
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABI_VIEW);

        $user      = $this->userModel->getUserProfile((string)$_SESSION['user_id']);
        $role      = (string)($user['role_name'] ?? '');
        $collegeId = isset($user['college_id']) ? (int)$user['college_id'] : null;
        $programId = isset($user['program_id']) ? (int)$user['program_id'] : null;

        $ASSET_BASE = (defined('BASE_PATH') ? BASE_PATH : '') . '/public';
        $esc        = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $PAGE_KEY   = 'syllabi';

        // Query param to open a specific college folder in “system roles” view
        $openCollegeId = null;
        if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
            $openCollegeId = (int)$_GET['college'];
        }

        // Cache (optional; mirrors templates controller behavior)
        if (!isset($_SESSION['sy_cache'])) $_SESSION['sy_cache'] = [];
        $cacheGet = fn(string $k) => $_SESSION['sy_cache'][$k] ?? null;
        $cacheSet = function(string $k, $v): void { $_SESSION['sy_cache'][$k] = $v; };

        $viewData = [
            'ASSET_BASE' => $ASSET_BASE,
            'esc'        => $esc,
            'PAGE_KEY'   => $PAGE_KEY,
            'user'       => $user,
            'role'       => $role,
        ];

        // 1) SYSTEM ROLES → folders view by default; open a college if requested
        if (in_array($role, $this->SYSTEM_ROLES, true)) {
            // cache the college list
            $colleges = $cacheGet('colleges_all');
            if ($colleges === null) {
                $colleges = $this->model->getAllColleges();
                $cacheSet('colleges_all', $colleges);
            }

            if ($openCollegeId === null) {
                $viewData['mode']     = 'system-folders';
                $viewData['colleges'] = $colleges;
                // show “New Syllabus” on system roles when drilled into a college (not here)
                return $this->render('index', $viewData);
            }

            // opened a college: load sections (general + per-program)
            $perKey = "college_sections_{$openCollegeId}";
            $collegeSections = $cacheGet($perKey);
            if ($collegeSections === null) {
                $general  = $this->model->getCollegeGeneralSyllabi($openCollegeId);
                $programs = $this->model->getProgramsByCollege($openCollegeId);

                $progSecs = [];
                foreach ($programs as $p) {
                    $pid = (int)$p['program_id'];
                    $progSecs[] = [
                        'program' => $p,
                        // “exclusive to this program” or simply “program’s syllabi”
                        'syllabi' => $this->model->getProgramSyllabiExclusive($openCollegeId, $pid),
                    ];
                }

                // pick the college row from cached list
                $college = null;
                foreach ($colleges as $c) {
                    if ((int)$c['college_id'] === $openCollegeId) { $college = $c; break; }
                }

                $collegeSections = [
                    'college'  => $college ?: ['college_id'=>$openCollegeId,'short_name'=>'','college_name'=>''],
                    'general'  => $general,
                    'programs' => $progSecs,
                ];
                $cacheSet($perKey, $collegeSections);
            }

            $viewData['mode']               = 'college';
            $viewData['college']            = $collegeSections['college'];
            $viewData['general']            = $collegeSections['general'];
            $viewData['programs']           = $collegeSections['programs'];
            $viewData['showBackToFolders']  = true;
            $viewData['canCreateSyllabus']  = true; // system roles can create
            $viewData['allColleges']        = $colleges;
            $viewData['programsOfCollege']  = $this->model->getProgramsByCollege((int)$collegeSections['college']['college_id']);
            return $this->render('index', $viewData);
        }

        // 2) DEANS → land directly on their college (general + programs)
        if ($collegeId && in_array($role, $this->DEAN_ROLES, true)) {
            $general  = $this->model->getCollegeGeneralSyllabi($collegeId);
            $programs = $this->model->getProgramsByCollege($collegeId);

            $progSecs = [];
            foreach ($programs as $p) {
                $pid = (int)$p['program_id'];
                $progSecs[] = [
                    'program' => $p,
                    'syllabi' => $this->model->getProgramSyllabiExclusive($collegeId, $pid),
                ];
            }

            $viewData['mode']              = 'college';
            $viewData['college']           = [
                'college_id'   => $collegeId,
                'short_name'   => (string)($user['college_short_name'] ?? ''),
                'college_name' => (string)($user['college_name'] ?? ''),
            ];
            $viewData['general']           = $general;
            $viewData['programs']          = $progSecs;
            $viewData['canCreateSyllabus'] = true;
            $viewData['allColleges']       = [['college_id'=>$collegeId,'short_name'=>$user['college_short_name'] ?? '','college_name'=>$user['college_name'] ?? '']];
            $viewData['programsOfCollege'] = $programs;
            return $this->render('index', $viewData);
        }

        // 3) CHAIRS → college general + their program only
        if ($collegeId && $programId && in_array($role, $this->CHAIR_ROLES, true)) {
            $general       = $this->model->getCollegeGeneralSyllabi($collegeId);
            $programSyl    = $this->model->getProgramSyllabiExclusive($collegeId, $programId);

            $viewData['mode']              = 'program';
            $viewData['college']           = [
                'college_id'   => $collegeId,
                'short_name'   => (string)($user['college_short_name'] ?? ''),
                'college_name' => (string)($user['college_name'] ?? ''),
            ];
            $viewData['program']           = [
                'program_id'   => $programId,
                'program_name' => (string)($user['program_name'] ?? 'My Program'),
            ];
            $viewData['general']           = $general;
            $viewData['program_syllabi']   = $programSyl;
            $viewData['canCreateSyllabus'] = true;
            $viewData['allColleges']       = [['college_id'=>$collegeId,'short_name'=>$user['college_short_name'] ?? '','college_name'=>$user['college_name'] ?? '']];
            $viewData['programsOfCollege'] = [['program_id'=>$programId,'program_name'=>$user['program_name'] ?? '']];
            return $this->render('index', $viewData);
        }

        // 4) Fallback → folders
        $viewData['mode']     = 'system-folders';
        $viewData['colleges'] = $this->model->getAllColleges();
        return $this->render('index', $viewData);
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
