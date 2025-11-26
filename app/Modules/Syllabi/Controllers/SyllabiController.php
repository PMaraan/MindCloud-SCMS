<?php
// /app/Modules/Syllabi/Controllers/SyllabiController.php
declare(strict_types=1);

namespace App\Modules\Syllabi\Controllers;

use App\Interfaces\StorageInterface;
use App\Security\RBAC;
use App\Config\Permissions;
use App\Models\UserModel;
use App\Modules\Syllabi\Models\SyllabiModel;
use App\Helpers\FlashHelper;

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
     * Wraps programs + their syllabi for a given college.
     * @param int $collegeId
     * @return array   // [
     *                      { 
     *                          'program' => {...},
     *                          'syllabi' => [...] 
     *                      }, 
     *                      ...
*                         ]
     */
    private function getProgramsAndSyllabiByCollege(int $collegeId): array
    {
        $collegePrograms = $this->model->getProgramsByCollege($collegeId);
        $programs = [];
        foreach ($collegePrograms as $p) {
            $pid = (int)($p['program_id'] ?? 0);
            $programs[] = [
                'program' => $p,
                'syllabi' => $this->model->getProgramSyllabi($pid),
            ];
        }
        return $programs;
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
        /*// Debugging RBAC
        var_dump([
            'role'              => $role,
            'canEdit'           => $this->canEdit($role, $collegeId, $programId),
            'canCreate'         => $this->canCreate($role, $collegeId, $programId),
            'GLOBAL_MATCH'      => in_array($role, $this->GLOBAL_ROLES, true),
            'DEAN_MATCH'        => in_array($role, $this->DEAN_ROLES, true),
            'CHAIR_MATCH'       => in_array($role, $this->CHAIR_ROLES, true),
        ]);
        exit;*/

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
            
            $view['mode'] = 'college';
            $view['college'] = $this->model->getCollege($qCollege);
            $view['programs'] = $this->getProgramsAndSyllabiByCollege($qCollege);
            $view['canCreate'] = $this->canCreate($role, $collegeId, $programId);
            $view['canEdit'] = $this->canEdit($role, $collegeId, $programId);
            $view['showBackToFolders'] = true;
            return $this->render('index', $view);
        // Dean Roles: College mode only (college param ignored)    
        } elseif ($collegeId && in_array($role, $this->DEAN_ROLES, true)) {
            $qCollege = null;
            $view['mode'] = 'college';
            $view['college'] = $this->model->getCollege($collegeId);
            $view['programs'] = $this->getProgramsAndSyllabiByCollege($collegeId);
            $view['canCreate'] = $this->canCreate($role, $collegeId, $programId);
            $view['canEdit'] = $this->canEdit($role, $collegeId, $programId);
            // Values for create modal selects
            $view['colleges'] = [[ 'college_id' => $collegeId ?? '', 'short_name' => $user['college_short_name'] ?? '', 'college_name' => $user['college_name'] ?? '']]; 
            $view['lockCollege'] = true;
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

        // Map expected POST fields to the model's payload shape.
        // Note: program_id[] (multiple) or single program_id are supported.
        $title = trim((string)($_POST['title'] ?? 'Untitled'));
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

        // program(s) may come as program_id[] (array) or program_id (single)
        $programIds = [];
        if (isset($_POST['program_id'])) {
            if (is_array($_POST['program_id'])) {
                foreach ($_POST['program_id'] as $v) {
                    $n = (int)$v;
                    if ($n > 0) $programIds[] = $n;
                }
            } else {
                $n = (int)$_POST['program_id'];
                if ($n > 0) $programIds[] = $n;
            }
        }

        $version = trim((string)($_POST['version'] ?? ''));

        $payload = [
            'title'       => $title,
            'course_id'   => $courseId,
            'program_ids' => $programIds, // model accepts program_ids (array) or program_id (int)
            'version'     => $version,
        ];

        try {
            $id = $this->model->createSyllabus($payload, (string)($_SESSION['user_id'] ?? ''));
            FlashHelper::set('success', 'Syllabus created.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Create failed: '.$e->getMessage());
        }

        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabi');
        exit;
    }

    /** POST /dashboard?page=syllabi&action=update */
    public function update(): void
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABI_EDIT);

        $id = isset($_POST['syllabus_id']) ? (int)$_POST['syllabus_id'] : 0;
        $title     = trim((string)($_POST['title'] ?? ''));
        $courseId  = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $collegeId = isset($_POST['college_id']) ? (int)$_POST['college_id'] : 0;
        $version   = trim((string)($_POST['version'] ?? ''));
        $status    = trim((string)($_POST['status'] ?? 'draft'));

        $programIds = [];
        if (!empty($_POST['program_ids']) && is_array($_POST['program_ids'])) {
            foreach ($_POST['program_ids'] as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) {
                    $programIds[] = $pid;
                }
            }
        }

        $user = $this->userModel->getUserProfile((string)$_SESSION['user_id']);
        $role = (string)($user['role_name'] ?? '');

        if (in_array($role, $this->DEAN_ROLES, true)) {
            $collegeId = (int)($user['college_id'] ?? 0);
        } elseif (in_array($role, $this->CHAIR_ROLES, true)) {
            $collegeId = (int)($user['college_id'] ?? 0);
            if (!$programIds && !empty($user['program_id'])) {
                $programIds[] = (int)$user['program_id'];
            }
        }

        $payload = [
            'title'       => $title,
            'course_id'   => $courseId,
            'college_id'  => $collegeId ?: null,
            'program_ids' => $programIds,
            'version'     => $version !== '' ? $version : null,
            'status'      => $status !== '' ? $status : null,
        ];

        try {
            if ($id <= 0) {
                throw new \RuntimeException('Missing id.');
            }
            $this->model->updateSyllabus($id, $payload, (string)($_SESSION['user_id'] ?? ''));
            FlashHelper::set('success', 'Syllabus updated.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Update failed: '.$e->getMessage());
        }

        $redirectCollege = $collegeId ?: (int)($user['college_id'] ?? 0);
        $location = (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabi';
        if ($redirectCollege) {
            $location .= '&college=' . $redirectCollege;
        }
        header('Location: ' . $location);
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
            FlashHelper::set('success', 'Syllabus deleted.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Delete failed: '.$e->getMessage());
        }

        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabi');
        exit;
    }

    /** POST /dashboard?page=syllabi&action=edit */
    public function edit(): void
    {
        $this->rbac->require((string)$_SESSION['user_id'], Permissions::SYLLABI_EDIT);

        $id        = (int)($_POST['syllabus_id'] ?? 0);
        $title     = trim((string)($_POST['title'] ?? ''));
        $courseId  = (int)$_POST['course_id'] ?? 0;
        $collegeId = (int)$_POST['college_id'] ?? 0;
        $version   = trim((string)($_POST['version'] ?? ''));
        $status    = trim((string)($_POST['status'] ?? 'draft'));

        $programIds = [];
        if (!empty($_POST['program_ids']) && is_array($_POST['program_ids'])) {
            foreach ($_POST['program_ids'] as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) {
                    $programIds[] = $pid;
                }
            }
        }

        $user = $this->userModel->getUserProfile((string)$_SESSION['user_id']);
        $role = (string)($user['role_name'] ?? '');

        if (in_array($role, $this->DEAN_ROLES, true)) {
            $collegeId = (int)($user['college_id'] ?? 0);
        } elseif (in_array($role, $this->CHAIR_ROLES, true)) {
            $collegeId = (int)($user['college_id'] ?? 0);
            if (!$programIds && !empty($user['program_id'])) {
                $programIds[] = (int)$user['program_id'];
            }
        }

        $payload = [
            'title'       => $title,
            'course_id'   => $courseId,
            'college_id'  => $collegeId ?: null,
            'program_ids' => $programIds,
            'version'     => $version !== '' ? $version : null,
            'status'      => $status !== '' ? $status : null,
        ];

        try {
            if ($id <= 0) {
                throw new \RuntimeException('Missing syllabus id.');
            }
            $this->model->updateSyllabus($id, $payload, (string)($_SESSION['user_id'] ?? ''));
            FlashHelper::set('success', 'Syllabus updated.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Update failed: ' . $e->getMessage());
        }

        $redirectCollege = $collegeId ?: (int)($user['college_id'] ?? 0);
        $location = (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabi';
        if ($redirectCollege) {
            $location .= '&college=' . $redirectCollege;
        }
        header('Location: ' . $location);
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
        
        if (in_array($role, $this->GLOBAL_ROLES, true)) {
            return false; // AAO roles cannot edit the metadata
        } elseif (in_array($role, $this->DEAN_ROLES, true)) {
            return true; // Deans can edit
        } elseif (in_array($role, $this->CHAIR_ROLES, true)) {
            return true; // Chairs can edit
        }
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
