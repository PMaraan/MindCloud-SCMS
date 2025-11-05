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

final class SyllabusTemplatesController
{
    private StorageInterface $db;
    private UserModel $userModel;
    private SyllabusTemplatesModel $model;

    // Keep same role groupings
    private array $SYSTEM_ROLES  = ['VPAA','Admin','Librarian','QA','Registrar'];
    private array $DEAN_ROLES    = ['Dean','College Dean'];
    private array $CHAIR_ROLES   = ['Program Chair','Department Chair','Coordinator'];

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

        // DEBUG: show what the controller sees (visible only to logged-in users; safe for dev)
        // Comment out when done.
        if (!isset($_GET['nodebug'])) {
            echo '<pre style="background:#111;color:#0f0;padding:8px;white-space:pre-wrap;">'
            . 'DEBUG SyllabusTemplatesController@index' . PHP_EOL
            . 'user_id=' . $esc((string)($_SESSION['user_id'] ?? '')) . PHP_EOL
            . 'role=' . $esc($role) . PHP_EOL
            . 'college_id=' . $esc((string)($collegeId ?? 'NULL')) . PHP_EOL
            . 'program_id=' . $esc((string)($programId ?? 'NULL')) . PHP_EOL
            . '</pre>';
        }

        // Convert simple GET flag into a flash (so JS can trigger a nice message without alert())
        if (isset($_GET['flash']) && $_GET['flash'] === 'missing-id') {
            \App\Helpers\FlashHelper::set('danger', 'Missing or invalid document id.');
        }

        // FOLDER-FIRST for non-college-bound roles (system roles)
        $openCollegeId = null;
        if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
            $openCollegeId = (int)$_GET['college'];
        }

        // Cache helpers (renamed to st_cache)
        $cacheGet = function (string $key) {
            return $_SESSION['st_cache'][$key] ?? null;
        };
        $cacheSet = function (string $key, $value): void {
            $_SESSION['st_cache'][$key] = $value;
        };

        // SYSTEM ROLES: show folders first. Only load templates when a folder is opened.
        if (in_array($role, $this->SYSTEM_ROLES, true)) {
            // Cache colleges list
            $colleges = $cacheGet('colleges_all');
            if ($colleges === null) {
                $colleges = $this->model->getAllColleges();
                $cacheSet('colleges_all', $colleges);
            }

            if ($openCollegeId === null) {
                // just folders view
                $viewData['mode']     = 'system-folders';
                $viewData['colleges'] = $colleges;
                return $this->render('index', $viewData);
            }

            // opened a college folder → load that college’s sections, but cache per-college
            $perKey = "college_sections_{$openCollegeId}";
            $collegeSections = $cacheGet($perKey);
            if ($collegeSections === null) {
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
                // also fetch the college basic info (from cached list)
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

            $viewData['mode']    = 'college';
            $viewData['college'] = $collegeSections['college'];
            $viewData['general'] = $collegeSections['general'];
            $viewData['programs']= $collegeSections['programs'];
            $viewData['showBackToFolders'] = true;
            $viewData['canCreateGlobal']   = true; // VPAA/Admin can create system/global
            $viewData['canCreateCollege']  = true; // they can also create college-level
            $viewData['allColleges']       = $colleges; // for modal selects
            $viewData['programsOfCollege'] = $this->model->getProgramsByCollege((int)$collegeSections['college']['college_id']);
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
            $viewData['canCreateCollege']  = true;
            $viewData['allColleges']       = [['college_id'=>$collegeId,'short_name'=>$user['college_short_name'] ?? '','college_name'=>$user['college_name'] ?? '']];
            $viewData['programsOfCollege'] = $programs;
            return $this->render('index', $viewData);
        }

        // CHAIRS: general (college-level) + their program section only
        if ($collegeId && $programId && in_array($role, $this->CHAIR_ROLES, true)) {
            $general  = $this->model->getCollegeGeneralTemplates($collegeId);
            $progOnly = $this->model->getProgramExclusiveTemplates($collegeId, $programId);
            $viewData['mode']    = 'program';
            $viewData['college'] = [
                'college_id'   => $collegeId,
                'short_name'   => (string)($user['college_short_name'] ?? ''),
                'college_name' => (string)($user['college_name'] ?? ''),
            ];
            $viewData['program'] = [
                'program_id'   => $programId,
                'program_name' => (string)($user['program_name'] ?? 'My Program'),
            ];
            $viewData['general'] = $general;
            $viewData['program_templates'] = $progOnly;
            $viewData['canCreateProgram']  = true;
            $viewData['allColleges']       = [['college_id'=>$collegeId,'short_name'=>$user['college_short_name'] ?? '','college_name'=>$user['college_name'] ?? '']];
            $viewData['programsOfCollege'] = [['program_id'=>$programId,'program_name'=>$user['program_name'] ?? '']];
            return $this->render('index', $viewData);
        }

        // fallback
        $viewData['mode']   = 'system-folders';
        $viewData['colleges'] = $this->model->getAllColleges();
        return $this->render('index', $viewData);
    }

    public function create(): void
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABUSTEMPLATES_CREATE);
        $user = $this->userModel->getUserProfile((string)$_SESSION['user_id']);
        $idno = (string)($user['id_no'] ?? 'SYS-UNKNOWN');

        // NOTE: per your standard, get PDO via getConnection()
        $pdo  = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            $title  = trim((string)($_POST['title'] ?? ''));
            $scope  = (string)($_POST['scope'] ?? 'system'); // system | college | program
            $colId  = isset($_POST['college_id']) ? (int)$_POST['college_id'] : null;
            $progId = isset($_POST['program_id']) ? (int)$_POST['program_id'] : null;

            if ($title === '') throw new \RuntimeException('Title is required.');

            // Insert template
            if ($scope === 'system') {
                $stmt = $pdo->prepare("INSERT INTO public.syllabus_templates (scope, title, status, content, created_by)
                                       VALUES ('system', :title, 'draft', '{}'::jsonb, :by)
                                       RETURNING template_id");
                $stmt->execute([':title'=>$title, ':by'=>$idno]);
                $tid = (int)$stmt->fetchColumn();

            } elseif ($scope === 'college') {
                if (!$colId) throw new \RuntimeException('College is required for college scope.');
                $stmt = $pdo->prepare("INSERT INTO public.syllabus_templates (scope, title, status, content, created_by)
                                       VALUES ('college', :title, 'draft', '{}'::jsonb, :by)
                                       RETURNING template_id");
                $stmt->execute([':title'=>$title, ':by'=>$idno]);
                $tid = (int)$stmt->fetchColumn();

                // exactly one college assignment
                $pdo->prepare("INSERT INTO public.syllabus_template_colleges (template_id, college_id) VALUES (:t,:c)")
                    ->execute([':t'=>$tid, ':c'=>$colId]);

            } else { // program
                if (!$progId) throw new \RuntimeException('Program is required for program scope.');
                // derive college for the program
                $colId = (int)$pdo->query("SELECT college_id FROM public.programs WHERE program_id = {$progId}")->fetchColumn();
                if (!$colId) throw new \RuntimeException('Program has no college.');

                $stmt = $pdo->prepare("INSERT INTO public.syllabus_templates (scope, owner_program_id, title, status, content, created_by)
                                       VALUES ('program', :pid, :title, 'draft', '{}'::jsonb, :by)
                                       RETURNING template_id");
                $stmt->execute([':pid'=>$progId, ':title'=>$title, ':by'=>$idno]);
                $tid = (int)$stmt->fetchColumn();

                // enforce program + its single college assignment
                $pdo->prepare("INSERT INTO public.syllabus_template_programs (template_id, program_id) VALUES (:t,:p)")
                    ->execute([':t'=>$tid, ':p'=>$progId]);
                $pdo->prepare("INSERT INTO public.syllabus_template_colleges (template_id, college_id) VALUES (:t,:c)
                               ON CONFLICT DO NOTHING")
                    ->execute([':t'=>$tid, ':c'=>$colId]);
            }

            $pdo->commit();

            // bust just enough cache so user sees new item on refresh
            unset($_SESSION['st_cache'], $_SESSION['tb_cache']);
            $_SESSION['flash'] = ['type'=>'success','message'=>'Template created.'];

            // new slug ?page=syllabus-templates; keep college param
            header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates' . ($scope!=='system' && isset($colId) ? '&college='.$colId : ''));
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['type'=>'danger','message'=>'Create failed: '.$e->getMessage()];
            header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates');
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
