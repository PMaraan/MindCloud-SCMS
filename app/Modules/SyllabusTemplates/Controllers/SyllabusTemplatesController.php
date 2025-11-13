<?php
// /app/Modules/SyllabusTemplates/Controllers/SyllabusTemplatesController.php
declare(strict_types=1);

namespace App\Modules\SyllabusTemplates\Controllers;

use App\Interfaces\StorageInterface;
use App\Security\RBAC;
use App\Config\Permissions;
use App\Models\UserModel;
use App\Modules\SyllabusTemplates\Models\SyllabusTemplatesModel;
use PDO;
use Throwable;
use App\Helpers\FlashHelper;

final class SyllabusTemplatesController
{
    private StorageInterface $db;
    private UserModel $userModel;
    private SyllabusTemplatesModel $model;

    // Keep same role groupings
    private array $GLOBAL_ROLES  = ['VPAA','VPAA Secretary'];
    private array $DEAN_ROLES    = ['Dean'];
    private array $CHAIR_ROLES   = ['Chair'];

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
        $this->userModel = new UserModel($db);
        $this->model     = new SyllabusTemplatesModel($db);

        // Use new cache bucket, but gracefully carry over the old one if it exists
        if (!isset($_SESSION['st_cache'])) {
            $_SESSION['st_cache'] = isset($_SESSION['tb_cache']) && is_array($_SESSION['tb_cache'])
                ? $_SESSION['tb_cache']
                : [];
        }
    }

    public function index(): string
    {
        // Same permission, now referenced via alias constant
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABUSTEMPLATES_VIEW);

        $user      = $this->userModel->getUserProfile((string)$_SESSION['user_id']);
        $role      = (string)($user['role_name'] ?? '');

        // ---- JSON gates for dependent selects (no router change needed) ----
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            $ajax = (string)$_GET['ajax'];

            try {
                if ($ajax === 'programs') {
                    // Return [] on missing/invalid id (do not 400 — keeps console clean)
                    $deptId = (int)($_GET['department_id'] ?? 0);
                    if ($deptId <= 0) {
                        echo json_encode(['programs' => []], JSON_UNESCAPED_SLASHES);
                        return '';
                    }
                    $rows = $this->model->getProgramsByCollege($deptId);
                    $out  = array_map(static fn($r) => [
                        'id'    => (int)($r['program_id'] ?? 0),
                        'label' => (string)($r['program_name'] ?? ''),
                    ], $rows);
                    echo json_encode(['programs' => $out], JSON_UNESCAPED_SLASHES);
                    exit;
                }

                if ($ajax === 'courses') {
                    $pid = (int)($_GET['program_id'] ?? 0);
                    if ($pid <= 0) {
                        echo json_encode(['courses' => []], JSON_UNESCAPED_SLASHES);
                        return '';
                    }
                    if (method_exists($this->model, 'getCoursesForProgram')) {
                        $rows = $this->model->getCoursesForProgram($pid); // returns [ ['id'=>..,'label'=>..], ... ]
                        echo json_encode(['courses' => $rows], JSON_UNESCAPED_SLASHES);
                        exit;
                    }
                    // Fallback if helper not present yet
                    echo json_encode(['courses' => []], JSON_UNESCAPED_SLASHES);
                    return '';
                }

                // Unknown ajax -> empty OK
                echo json_encode([], JSON_UNESCAPED_SLASHES);
                return '';

            } catch (\Throwable $e) {
                // Mask server errors as empty list to avoid noisy consoles
                echo json_encode(['programs' => [], 'courses' => []], JSON_UNESCAPED_SLASHES);
                exit;
            }
        }

        $collegeId = isset($user['college_id']) ? (int)$user['college_id'] : null;
        $programId = isset($user['program_id']) ? (int)$user['program_id'] : null;

        $ASSET_BASE = (defined('BASE_PATH') ? BASE_PATH : '') . '/public';
        $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $viewData = [
            'ASSET_BASE' => $ASSET_BASE,
            'esc'        => $esc,
            'user'       => $user,
            'role'       => $role,
        ];

        // ---- Per-scope edit permissions to drive UI/JS ----
        // Canonical role names (DB): VPAA, VPAA Secretary, Dean, Chair
        $roleL = strtolower((string)$role);

        // Only AAO roles may edit Global scope
        $canEditGlobal  = in_array($roleL, ['vpaa', 'vpaa secretary'], true);

        // Dean can edit college; Chair can edit program.
        // AAO can edit both college and program scopes as well.
        $canEditCollege = in_array($roleL, ['vpaa', 'vpaa secretary', 'dean', 'college secretary'], true);
        $canEditProgram = in_array($roleL, ['vpaa', 'vpaa secretary', 'dean', 'college secretary', 'chair'], true);

        $viewData['canEditGlobal']  = $canEditGlobal;
        $viewData['canEditCollege'] = $canEditCollege;
        $viewData['canEditProgram'] = $canEditProgram;

        // DEBUG: show what the controller sees (visible only to logged-in users; safe for dev)
        // Comment out when done.
        /*
        if (!isset($_GET['nodebug'])) {
            echo '<pre style="background:#111;color:#0f0;padding:8px;white-space:pre-wrap;">'
            . 'DEBUG SyllabusTemplatesController@index' . PHP_EOL
            . 'user_id=' . $esc((string)($_SESSION['user_id'] ?? '')) . PHP_EOL
            . 'role=' . $esc($role) . PHP_EOL
            . 'college_id=' . $esc((string)($collegeId ?? 'NULL')) . PHP_EOL
            . 'program_id=' . $esc((string)($programId ?? 'NULL')) . PHP_EOL
            . '</pre>';
        }
        */

        // Convert simple GET flag into a flash (so JS can trigger a nice message without alert())
        if (isset($_GET['flash']) && $_GET['flash'] === 'missing-id') {
            \App\Helpers\FlashHelper::set('danger', 'Missing or invalid document id.');
        }

        // FOLDER-FIRST for non-college-bound roles (global roles)
        $openCollegeId = null;
        if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
            $val = (int)$_GET['college'];
            // Only accept positive college ids — ignore 0 / empty/invalid which would incorrectly open a "college"
            if ($val > 0) $openCollegeId = $val;
        }

        // Cache helpers (renamed to st_cache)
        $cacheGet = function (string $key) {
            return $_SESSION['st_cache'][$key] ?? null;
        };
        $cacheSet = function (string $key, $value): void {
            $_SESSION['st_cache'][$key] = $value;
        };

        // GLOBAL ROLES: show folders first. Only load templates when a folder is opened.
        if (in_array($role, $this->GLOBAL_ROLES, true)) {
            // Cache colleges list
            $colleges = $cacheGet('colleges_all');
            if ($colleges === null) {
                $colleges = $this->model->getAllColleges();
                $cacheSet('colleges_all', $colleges);
            }

            if ($openCollegeId === null) {
                // folders view + allow AAO (VPAA + VPAA Secretary) to create Global/College templates
                // View expects 'global-folders'
                $viewData['mode']             = 'global-folders';
                $viewData['colleges']         = $colleges;

                // AAO roles (can manage global templates)
                $isAAO = in_array($role, $this->GLOBAL_ROLES, true);
                $viewData['canCreateGlobal']   = $isAAO;
                $viewData['canCreateCollege']  = $isAAO;
                $viewData['canCreateProgram']  = $isAAO;
                // expose AAO action flags for global-scope templates (used by details pane)
                $viewData['canEditGlobal']     = $isAAO;
                $viewData['canArchiveGlobal']  = $isAAO;

                // Provide lists needed by the modal
                $viewData['allColleges']       = $colleges;
                $viewData['programsOfCollege'] = [];

                return $this->render('index', $viewData);
            }

            // opened a folder → always fetch fresh sections to avoid stale program lists
            $general   = $this->model->getCollegeGeneralTemplates($openCollegeId);
            $programs  = $this->model->getProgramsByCollege($openCollegeId);
            $progSecs  = [];
            foreach ($programs as $p) {
                $pid = (int)$p['program_id'];
                $progSecs[] = [
                    'program'   => $p,
                    'templates' => $this->model->getProgramExclusiveTemplates($openCollegeId, $pid),
                ];
            }
            // find the “college” record from the folders list
            $college = null;
            foreach ($colleges as $c) {
                if ((int)$c['college_id'] === $openCollegeId) { $college = $c; break; }
            }

            $viewData['mode']     = 'college';
            $viewData['college']  = $college ?: ['college_id'=>$openCollegeId,'short_name'=>'','college_name'=>''];
            $viewData['general']  = $general;
            $viewData['programs'] = $progSecs;
            $viewData['showBackToFolders'] = true;
            $viewData['canCreateGlobal']   = true; // VPAA/Admin can create global
            $viewData['canCreateCollege']  = true; // they can also create college-level
            $viewData['allColleges']       = $colleges; // for modal selects
            $deptId = (int)($college['college_id'] ?? $openCollegeId);
            $viewData['programsOfCollege'] = $this->model->getProgramsByCollege($deptId);
            return $this->render('index', $viewData);
        }

        // DEANS: show their college sections directly
        if ($collegeId && in_array($role, $this->DEAN_ROLES, true)) {
            $general  = $this->model->getCollegeGeneralTemplates($collegeId);
            $programs = $this->model->getProgramsByCollege($collegeId);
            $progSecs = [];
            foreach ($programs as $p) {
                $pid = (int)$p['program_id'];
                $progSecs[] = [
                    'program'   => $p,
                    'templates' => $this->model->getProgramExclusiveTemplates($collegeId, $pid),
                ];
            }
            $viewData['mode']    = 'college';
            $viewData['college'] = [
                'college_id'   => $collegeId,
                'short_name'   => (string)($user['college_short_name'] ?? ''),
                'college_name' => (string)($user['college_name'] ?? ''),
            ];
            $viewData['general'] = $general;
            $viewData['programs']= $progSecs;

            // Create permissions for deans:
            $viewData['canCreateCollege']  = true;
            $viewData['canCreateProgram']  = true; // <— allow Program-scope creation
            // if you also want Course scope creation for deans, set a flag your modal can read
            $viewData['canCreateCourse']   = true; // optional: toggle to false if you don't want it yet

            // Lists used by the Create modal
            $viewData['allColleges']       = [['college_id'=>$collegeId,'short_name'=>$user['college_short_name'] ?? '','college_name'=>$user['college_name'] ?? '']];
            $viewData['programsOfCollege'] = $programs;

            // Pass default college for preselect
            $viewData['defaultCollegeId']  = $collegeId;

            return $this->render('index', $viewData);
        }

        // CHAIRS: show their college (general) + ONLY their own program section
        if ($collegeId && $programId && in_array($role, $this->CHAIR_ROLES, true)) {
            $general   = $this->model->getCollegeGeneralTemplates($collegeId);

            // Only the chair's program section (view & duplicate allowed; edit only for their program)
            $programRow = [
                'program_id'   => $programId,
                'program_name' => (string)($user['program_name'] ?? 'My Program'),
            ];
            $progSection = [
                'program'   => $programRow,
                'templates' => $this->model->getProgramExclusiveTemplates($collegeId, $programId),
            ];

            $viewData['mode']    = 'college';
            $viewData['college'] = [
                'college_id'   => $collegeId,
                'short_name'   => (string)($user['college_short_name'] ?? ''),
                'college_name' => (string)($user['college_name'] ?? ''),
            ];
            $viewData['general']  = $general;
            $viewData['programs'] = [$progSection];

            // Creation capabilities for Chair: program/course only; never global/college
            $viewData['canCreateGlobal']   = false;
            $viewData['canCreateCollege']  = false;
            $viewData['canCreateProgram']  = true;

            // For selects in modals
            $viewData['allColleges']       = [[
                'college_id'   => $collegeId,
                'short_name'   => (string)($user['college_short_name'] ?? ''),
                'college_name' => (string)($user['college_name'] ?? ''),
            ]];
            $viewData['programsOfCollege'] = [[
                'program_id'   => $programId,
                'program_name' => (string)($user['program_name'] ?? 'My Program'),
            ]];

            // No "Back to folders" for chairs
            $viewData['showBackToFolders'] = false;



            // --- DEBUG DUMP (developer only) ----------------------------------
            // Temporarily print what the model returned so we can inspect
            if (isset($_GET['devdump']) && $_GET['devdump'] === '1') {
                echo '<pre style="background:#111;color:#0f0;padding:8px;">';
                echo "DEBUG: SyllabusTemplatesController@index debug dump\n\n";

                // Global / general / programs depending on mode
                try {
                    $glo = method_exists($this->model, 'getGlobalTemplates') ? $this->model->getGlobalTemplates() : null;
                    $gen = isset($general) ? $general : (method_exists($this->model,'getCollegeGeneralTemplates') ? $this->model->getCollegeGeneralTemplates($openCollegeId ?? $collegeId ?? 0) : null);
                    $progs = isset($programs) ? $programs : (method_exists($this->model,'getProgramsByCollege') ? $this->model->getProgramsByCollege($openCollegeId ?? $collegeId ?? 0) : null);

                    echo "getGlobalTemplates(): " . (is_array($glo) ? count($glo) . " rows" : var_export($glo, true)) . "\n";
                    echo "getCollegeGeneralTemplates(): " . (is_array($gen) ? count($gen) . " rows" : var_export($gen, true)) . "\n";
                    echo "getProgramsByCollege(): " . (is_array($progs) ? count($progs) . " rows" : var_export($progs, true)) . "\n\n";

                    // show first 5 rows of each, if arrays
                    if (is_array($glo)) { echo "GLOBAL sample:\n" . print_r(array_slice($glo,0,5), true) . "\n"; }
                    if (is_array($gen)) { echo "GENERAL sample:\n" . print_r(array_slice($gen,0,5), true) . "\n"; }
                    if (is_array($progs)) { echo "PROGRAMS sample:\n" . print_r(array_slice($progs,0,5), true) . "\n"; }

                } catch (\Throwable $e) {
                    echo "Model exception: " . get_class($e) . " - " . $e->getMessage() . "\n" . $e->getTraceAsString();
                }

                echo '</pre>';
                // stop so you see only debug info
                exit;
            }


            return $this->render('index', $viewData);
        }

        // fallback: show folders first
        $viewData['mode']   = 'global-folders';
         $viewData['colleges'] = $this->model->getAllColleges();
         return $this->render('index', $viewData);
    }

    public function apiPrograms(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Require login (private route already guards this, but double-safety)
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_SLASHES);
            exit;
        }

        $deptId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
        if ($deptId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'department_id required'], JSON_UNESCAPED_SLASHES);
            exit;
        }

        try {
            // Uses your existing model method
            $rows = $this->model->getProgramsByCollege($deptId);
            // Normalize shape for the select helper
            $out = array_map(static fn(array $r) => [
                'id'    => (int)($r['program_id'] ?? 0),
                'label' => (string)($r['program_name'] ?? ''),
            ], $rows);
            echo json_encode($out, JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error'], JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    public function apiCourses(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_SLASHES);
            exit;
        }

        $programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
        if ($programId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'program_id required'], JSON_UNESCAPED_SLASHES);
            exit;
        }

        try {
            $rows = $this->model->getCoursesByProgram($programId);
            echo json_encode($rows, JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error'], JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    public function create(): void
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABUSTEMPLATES_CREATE);
        $user = $this->userModel->getUserProfile((string)$_SESSION['user_id']);
        $idno = (string)($user['id_no'] ?? 'SYS-UNKNOWN');
        $roleName  = strtolower((string)($user['role_name'] ?? ''));
        $userColId = isset($user['college_id']) ? (int)$user['college_id'] : null;

        // Deans cannot create global scope
        if (in_array($roleName, ['dean'], true) && $scope === 'global') {
            throw new \RuntimeException('Not allowed: deans cannot create Global templates.');
        }

        // If dean is creating college/program/course, force college to their own
        if (in_array($roleName, ['dean'], true) && in_array($scope, ['college','program','course'], true)) {
            if ($userColId) {
                $colId = $userColId; // override incoming
            } else {
                throw new \RuntimeException('Your profile is missing a college.');
            }
        }

        // NOTE: per your standard, get PDO via getConnection()
        $pdo  = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            $title  = trim((string)($_POST['title'] ?? ''));
            $scope  = (string)($_POST['scope'] ?? 'global'); // global | college | program
            $colId  = isset($_POST['college_id']) ? (int)$_POST['college_id'] : null;
            $progId = isset($_POST['program_id']) ? (int)$_POST['program_id'] : null;

            if ($title === '') throw new \RuntimeException('Title is required.');

            // Insert template
            if ($scope === 'global') {
                $stmt = $pdo->prepare("INSERT INTO public.syllabus_templates (scope, title, status, content, created_by)
                                    VALUES ('global', :title, 'draft', '{}'::jsonb, :by)
                                    RETURNING template_id");
                $stmt->execute([':title'=>$title, ':by'=>$idno]);
                $tid = (int)$stmt->fetchColumn();

            } elseif ($scope === 'college') {
                if (!$colId) throw new \RuntimeException('College is required for college scope.');
                $stmt = $pdo->prepare("INSERT INTO public.syllabus_templates (scope, title, status, content, created_by, owner_department_id)
                                    VALUES ('college', :title, 'draft', '{}'::jsonb, :by, :dept)
                                    RETURNING template_id");
                $stmt->execute([':title'=>$title, ':by'=>$idno, ':dept'=>$colId]);
                $tid = (int)$stmt->fetchColumn();

                $pdo->prepare("INSERT INTO public.syllabus_template_departments (template_id, department_id) VALUES (:t,:d)")
                    ->execute([':t'=>$tid, ':d'=>$colId]);

            } elseif ($scope === 'program') {
                if (!$progId) throw new \RuntimeException('Program is required for program scope.');
                $deptId = (int)$pdo->query("SELECT department_id FROM public.programs WHERE program_id = {$progId}")->fetchColumn();
                if (!$deptId) throw new \RuntimeException('Program has no college department.');

                $stmt = $pdo->prepare("INSERT INTO public.syllabus_templates (scope, title, status, content, created_by, owner_department_id, program_id)
                                    VALUES ('program', :title, 'draft', '{}'::jsonb, :by, :dept, :pid)
                                    RETURNING template_id");
                $stmt->execute([':title'=>$title, ':by'=>$idno, ':dept'=>$deptId, ':pid'=>$progId]);
                $tid = (int)$stmt->fetchColumn();

                $pdo->prepare("INSERT INTO public.syllabus_template_programs (template_id, program_id) VALUES (:t,:p)")
                    ->execute([':t'=>$tid, ':p'=>$ProgId]);
                $pdo->prepare("INSERT INTO public.syllabus_template_departments (template_id, department_id) VALUES (:t,:d)
                            ON CONFLICT DO NOTHING")->execute([':t'=>$tid, ':d'=>$deptId]);

            } elseif ($scope === 'course') {
                if (!$progId) throw new \RuntimeException('Program is required for course scope.');
                $deptId = (int)$pdo->query("SELECT department_id FROM public.programs WHERE program_id = {$progId}")->fetchColumn();
                if (!$deptId) throw new \RuntimeException('Program has no college department.');
                $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
                if ($courseId <= 0) throw new \RuntimeException('Course is required for course scope.');

                $stmt = $pdo->prepare("INSERT INTO public.syllabus_templates (scope, title, status, content, created_by,
                                                    owner_department_id, program_id, course_id)
                                    VALUES ('course', :title, 'draft', '{}'::jsonb, :by, :dept, :pid, :cid)
                                    RETURNING template_id");
                $stmt->execute([':title'=>$title, ':by'=>$idno, ':dept'=>$deptId, ':pid'=>$progId, ':cid'=>$courseId]);
                $tid = (int)$stmt->fetchColumn();

                $pdo->prepare("INSERT INTO public.syllabus_template_programs (template_id, program_id) VALUES (:t,:p)
                            ON CONFLICT DO NOTHING")->execute([':t'=>$tid, ':p'=>$ProgId]);
                $pdo->prepare("INSERT INTO public.syllabus_template_departments (template_id, department_id) VALUES (:t,:d)
                            ON CONFLICT DO NOTHING")->execute([':t'=>$tid, ':d'=>$deptId]);

            } else {
                throw new \RuntimeException('Invalid scope.');
            }

            $pdo->commit();

            // bust just enough cache so user sees new item on refresh
            unset($_SESSION['st_cache'], $_SESSION['tb_cache']);
            $_SESSION['flash'] = ['type'=>'success','message'=>'Template created.'];

            // Redirect: preserve college param or use the college from the created template
            $redirectUrl = (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates';
            
            // Determine which college to redirect to
            $targetCollege = null;
            
            // 1) If college param exists in request, use it (user was in a college view)
            if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
                $targetCollege = (int)$_GET['college'];
            }
            // 2) Otherwise use the college from the created template
            elseif ($scope !== 'global') {
                if ($scope === 'college') {
                    $targetCollege = $colId;
                } elseif ($scope === 'program' || $scope === 'course') {
                    $deptId = (int)$pdo->query("SELECT department_id FROM public.programs WHERE program_id = {$progId}")->fetchColumn();
                    $targetCollege = $deptId ?: null;
                }
            }
            
            if ($targetCollege && $targetCollege > 0) {
                $redirectUrl .= '&college=' . $targetCollege;
            }

            header('Location: ' . $redirectUrl);
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['type'=>'danger','message'=>'Create failed: '.$e->getMessage()];
            
            // Preserve college param on error redirect too
            $redirectUrl = (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates';
            if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
                $collegeParam = (int)$_GET['college'];
                if ($collegeParam > 0) {
                    $redirectUrl .= '&college=' . $collegeParam;
                }
            }
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    public function duplicate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();

        // CSRF
        $token = (string)($_POST['csrf_token'] ?? '');
        if ($token === '' || $token !== (string)($_SESSION['csrf_token'] ?? '')) {
            \App\Helpers\FlashHelper::set('danger', 'Invalid CSRF.');
            header('Location: ' . BASE_PATH . '/dashboard?page=syllabus-templates');
            return;
        }

        $srcId = (int)($_POST['source_template_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $scope = strtolower((string)($_POST['scope'] ?? 'college')); // default to college

        $deptId = isset($_POST['college_id']) ? (int)$_POST['college_id'] : null;
        $progId = isset($_POST['program_id']) ? (int)$_POST['program_id'] : null;
        $crsId  = isset($_POST['course_id'])  ? (int)$_POST['course_id']  : null;

        if ($srcId <= 0 || $title === '') {
            \App\Helpers\FlashHelper::set('danger', 'Missing source or title.');
            header('Location: ' . BASE_PATH . '/dashboard?page=syllabus-templates');
            return;
        }

        // Normalize scope and required fields
        switch ($scope) {
            case 'global':
                $deptId = $progId = $crsId = null;
                break;
            case 'college':
                if (!$deptId) { \App\Helpers\FlashHelper::set('danger', 'College is required.'); goto dup_fail; }
                $progId = $crsId = null;
                break;
            case 'program':
                if (!$deptId || !$progId) { \App\Helpers\FlashHelper::set('danger', 'College and Program are required.'); goto dup_fail; }
                $crsId = null;
                break;
            case 'course':
                if (!$deptId || !$progId || !$crsId) { \App\Helpers\FlashHelper::set('danger', 'College, Program, and Course are required.'); goto dup_fail; }
                break;
            default:
                $scope = 'college';
                if (!$deptId) { \App\Helpers\FlashHelper::set('danger', 'College is required.'); goto dup_fail; }
                $progId = $crsId = null;
        }

        // Role gate: only VPAA / VPAA Secretary can duplicate into global
        $roleName = strtolower((string)($_SESSION['role_name'] ?? ''));
        if ($scope === 'global' && !in_array($roleName, ['vpaa','vpaa secretary'], true)) {
            \App\Helpers\FlashHelper::set('danger', 'You are not allowed to create Global templates.');
            goto dup_fail;
        }

        try {
            $model = new \App\Modules\SyllabusTemplates\Models\SyllabusTemplatesModel($this->db);
            $newId = $model->cloneTemplateWithMeta(
                $srcId,
                [
                    'title'                  => $title,
                    'scope'                  => $scope,
                    'owner_department_id'    => $deptId,
                    'program_id'             => $progId,
                    'course_id'              => $crsId,
                    'status'                 => 'draft',
                    'created_by'             => (string)($_SESSION['user_id'] ?? ''),
                ]
            );

            \App\Helpers\FlashHelper::set('success', 'Template duplicated.');
            
            // Preserve college param
            $redirectUrl = (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates';
            if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
                $collegeParam = (int)$_GET['college'];
                if ($collegeParam > 0) {
                    $redirectUrl .= '&college=' . $collegeParam;
                }
            }
            header('Location: ' . $redirectUrl);
            return;
        } catch (\Throwable $e) {
            \App\Helpers\FlashHelper::set('danger', 'Duplicate failed: ' . $e->getMessage());
        }

    dup_fail:
        $redirectUrl = (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates';
        if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
            $collegeParam = (int)$_GET['college'];
            if ($collegeParam > 0) {
                $redirectUrl .= '&college=' . $collegeParam;
            }
        }
        header('Location: ' . $redirectUrl);
    }

    public function edit(): void
    {
        (new \App\Security\RBAC($this->db))->require((string)$_SESSION['user_id'], \App\Config\Permissions::SYLLABUSTEMPLATES_EDIT);
        $user = $this->userModel->getUserProfile((string)$_SESSION['user_id']);
        $roleName  = strtolower((string)($user['role_name'] ?? ''));
        $userColId = isset($user['college_id']) ? (int)$user['college_id'] : null;

        // Only VPAA / VPAA Secretary can edit Global templates
        $allowedGlobalEditors = ['vpaa', 'vpaa secretary'];
        if ($scope === 'global' && !in_array($roleName, $allowedGlobalEditors, true)) {
            throw new \RuntimeException('Not allowed: only VPAA or VPAA Secretary can edit Global templates.');
        }

        // If dean saving as college/program/course, force college to their own
        if (in_array($roleName, ['dean'], true) && in_array($scope, ['college','program','course'], true)) {
            if ($userColId) {
                $deptId = $userColId; // override
            } else {
                throw new \RuntimeException('Your profile is missing a college.');
            }
        }
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            $tid   = (int)($_POST['template_id'] ?? 0);
            $title = trim((string)($_POST['title'] ?? ''));
            $scope = strtolower(trim((string)($_POST['scope'] ?? 'global')));

            // incoming (may be empty strings)
            $deptId   = $_POST['owner_department_id'] ?? $_POST['college_id'] ?? null;
            $progId   = $_POST['program_id'] ?? null;
            $courseId = $_POST['course_id'] ?? null;
            $status   = (string)($_POST['status'] ?? 'draft');
            $version  = (string)($_POST['version'] ?? '');

            if ($tid <= 0 || $title === '') {
                throw new \RuntimeException('Missing template id or title.');
            }

            // normalize to ints or NULL
            $toIntOrNull = static function($v) {
                if ($v === '' || $v === null) return null;
                $n = (int)$v;
                return $n > 0 ? $n : null;
            };
            $deptId   = $toIntOrNull($deptId);
            $progId   = $toIntOrNull($progId);
            $courseId = $toIntOrNull($courseId);

            // enforce by scope
            if ($scope === 'global') {
                $deptId = $progId = $courseId = null;
            } elseif ($scope === 'college') {
                if (!$deptId) throw new \RuntimeException('College is required.');
                $progId = $courseId = null;
            } elseif ($scope === 'program') {
                if (!$deptId || !$progId) throw new \RuntimeException('College and Program are required.');
                $courseId = null;
            } elseif ($scope === 'course') {
                if (!$deptId || !$progId || !$courseId) throw new \RuntimeException('College, Program, and Course are required.');
            } else {
                throw new \RuntimeException('Invalid scope.');
            }

            // Update row
            $stmt = $pdo->prepare("
                UPDATE public.syllabus_templates
                SET title = :title,
                    scope = :scope,
                    owner_department_id = :dept,
                    program_id = :prog,
                    course_id = :course,
                    status = :status,
                    updated_at = NOW()
                WHERE template_id = :tid
            ");
            $stmt->execute([
                ':title'  => $title,
                ':scope'  => $scope,
                ':dept'   => $deptId,
                ':prog'   => $progId,
                ':course' => $courseId,
                ':status' => $status,
                ':tid'    => $tid,
            ]);

            $pdo->commit();
            unset($_SESSION['st_cache'], $_SESSION['tb_cache']);
            $_SESSION['flash'] = ['type'=>'success','message'=>'Template updated.'];

            // Redirect: preserve college param or use the college from the edited template
            $redirectUrl = (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates';
            
            // Determine which college to redirect to
            $targetCollege = null;
            
            // 1) If college param exists in request, use it (user was in a college view)
            if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
                $targetCollege = (int)$_GET['college'];
            }
            // 2) Otherwise use the college from the edited template
            elseif ($scope !== 'global') {
                $targetCollege = $deptId;
            }
            
            if ($targetCollege && $targetCollege > 0) {
                $redirectUrl .= '&college=' . $targetCollege;
            }

            header('Location: ' . $redirectUrl);
            exit;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['type'=>'danger','message'=>'Update failed: '.$e->getMessage()];
            
            // Preserve college param on error redirect too
            $redirectUrl = (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates';
            if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
                $collegeParam = (int)$_GET['college'];
                if ($collegeParam > 0) {
                    $redirectUrl .= '&college=' . $collegeParam;
                }
            }
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    public function delete(): void
    {
        // Accept only POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            FlashHelper::set('danger', 'Method not allowed.');
            header('Location: ' . BASE_PATH . '/dashboard?page=syllabus-templates');
            exit;
        }

        // Basic CSRF
        $token = (string)($_POST['csrf_token'] ?? '');
        if ($token === '' || $token !== (string)($_SESSION['csrf_token'] ?? '')) {
            http_response_code(403);
            FlashHelper::set('danger', 'Invalid CSRF token.');
            header('Location: ' . BASE_PATH . '/dashboard?page=syllabus-templates');
            exit;
        }

        // Permission
        $permDeleteConst = 'App\\Config\\Permissions::SYLLABUSTEMPLATES_DELETE';
        $permEditConst   = 'App\\Config\\Permissions::SYLLABUSTEMPLATES_EDIT';
        if (defined($permDeleteConst)) {
            $requiredPerm = constant($permDeleteConst);
        } elseif (defined($permEditConst)) {
            $requiredPerm = constant($permEditConst);
        } else {
            $requiredPerm = 'SyllabusTemplateModification';
        }

        try {
            (new RBAC($this->db))->require((string)$_SESSION['user_id'], $requiredPerm);
        } catch (\Throwable $rbacErr) {
            FlashHelper::set('danger', 'Permission denied.');
            header('Location: ' . BASE_PATH . '/dashboard?page=syllabus-templates');
            exit;
        }

        $tplId = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
        if ($tplId <= 0) {
            FlashHelper::set('danger', 'Missing template id.');
            header('Location: ' . BASE_PATH . '/dashboard?page=syllabus-templates');
            exit;
        }

        try {
            $pdo = $this->db->getConnection();

            // Lock and fetch
            $st = $pdo->prepare("SELECT template_id, status FROM public.syllabus_templates WHERE template_id = :tid FOR UPDATE");
            $st->execute([':tid' => $tplId]);
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                FlashHelper::set('danger', 'Template not found.');
                header('Location: ' . BASE_PATH . '/dashboard?page=syllabus-templates');
                exit;
            }

            // Only archived templates can be deleted
            if (strtolower((string)$row['status']) !== 'archived') {
                FlashHelper::set('danger', 'Template must be archived before it can be deleted.');
                header('Location: ' . BASE_PATH . '/dashboard?page=syllabus-templates');
                exit;
            }

            // Delete
            $pdo->beginTransaction();
            try {
                $delTpl = $pdo->prepare("DELETE FROM public.syllabus_templates WHERE template_id = :tid");
                $delTpl->execute([':tid' => $tplId]);
                $pdo->commit();
            } catch (\Throwable $inner) {
                $pdo->rollBack();
                throw $inner;
            }

            // Bust caches
            unset($_SESSION['st_cache'], $_SESSION['tb_cache']);

            FlashHelper::set('success', 'Template deleted.');
            
            // Redirect: preserve college param like create/edit/duplicate do
            $redirectUrl = (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates';
            if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
                $collegeParam = (int)$_GET['college'];
                if ($collegeParam > 0) {
                    $redirectUrl .= '&college=' . $collegeParam;
                }
            }
            header('Location: ' . $redirectUrl);
            exit;
        } catch (Throwable $e) {
            error_log('TB delete error: ' . $e->getMessage());
            FlashHelper::set('danger', 'Server error while deleting template.');
            
            // Error redirect also preserves college
            $redirectUrl = (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates';
            if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
                $collegeParam = (int)$_GET['college'];
                if ($collegeParam > 0) {
                    $redirectUrl .= '&college=' . $collegeParam;
                }
            }
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    public function archive(): void
    {
        // Only POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        // CSRF
        $token = (string)($_POST['csrf_token'] ?? '');
        if ($token === '' || $token !== (string)($_SESSION['csrf_token'] ?? '')) {
            http_response_code(403);
            FlashHelper::set('danger', 'Invalid CSRF token.');
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit;
        }

        // Permission: require edit permission for templates
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABUSTEMPLATES_EDIT);

        $tplId = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
        if ($tplId <= 0) {
            http_response_code(400);
            FlashHelper::set('danger', 'Missing template id.');
            echo json_encode(['success' => false, 'message' => 'Missing template id']);
            exit;
        }

        // target may be provided (archived|draft|active) or omitted -> toggle
        $target = strtolower((string)($_POST['target'] ?? ''));

        try {
            $pdo = $this->db->getConnection();
            $st = $pdo->prepare("SELECT status FROM public.syllabus_templates WHERE template_id = :tid");
            $st->execute([':tid' => $tplId]);
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                FlashHelper::set('danger', 'Template not found.');
                echo json_encode(['success' => false, 'message' => 'Template not found']);
                exit;
            }

            $cur = strtolower((string)($row['status'] ?? 'active'));
            if ($target !== 'archived' && $target !== 'draft' && $target !== 'active') {
                // toggle: archived <-> active (user requested unarchive -> active)
                $target = ($cur === 'archived' || $cur === 'archive') ? 'active' : 'archived';
            }

            $upd = $pdo->prepare("UPDATE public.syllabus_templates SET status = :status, updated_at = NOW() WHERE template_id = :tid");
            $upd->execute([':status' => $target, ':tid' => $tplId]);

            // bust caches
            unset($_SESSION['st_cache'], $_SESSION['tb_cache']);

            $msg = ($target === 'archived') ? 'Template archived.' : 'Template unarchived.';
            FlashHelper::set('success', $msg);
            echo json_encode(['success' => true, 'status' => $target, 'message' => $msg]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            error_log('TB archive error: ' . $e->getMessage());
            FlashHelper::set('danger', 'Server error while archiving.');
            echo json_encode(['success' => false, 'message' => 'Server error']);
            exit;
        }
    }

    private function render(string $view, array $vars): string
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        // New Views path under SyllabusTemplates
        require dirname(__DIR__) . "/Views/{$view}.php";
        return (string)ob_get_clean();
    }
}
