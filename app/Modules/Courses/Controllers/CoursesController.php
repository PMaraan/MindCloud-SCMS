<?php
// /app/Modules/Courses/Controllers/CoursesController.php
namespace App\Modules\Courses\Controllers;

use App\Helpers\FlashHelper;
use App\Security\RBAC;
use App\Modules\Courses\Models\CoursesModel;
use App\Interfaces\StorageInterface;

final class CoursesController
{
    private StorageInterface $db;
    private CoursesModel $model;
    private RBAC $rbac;

     /** Cached module definition from ModuleRegistry */
    private array $moduleDef = [];

    /** Convenience: base URL for this module */
    private string $baseUrl;

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        $this->model = new CoursesModel($db);
        $this->rbac  = new RBAC($db);

        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->baseUrl = BASE_PATH . '/dashboard?page=courses';
        $this->moduleDef = $this->loadModuleDef('courses');
    }

    /** Load one module definition from ModuleRegistry.php safely */
    private function loadModuleDef(string $key): array
    {
        $registryPath = dirname(__DIR__, 4) . '/config/ModuleRegistry.php';
        $registry = is_file($registryPath) ? require $registryPath : [];
        $def = $registry[$key] ?? [];
        return is_array($def) ? $def : [];
    }

    /** Pull an action permission name from the cached module def */
    private function getActionPermission(string $actionKey): string
    {
        $actions = (array)($this->moduleDef['actions'] ?? []);
        $perm = (string)($actions[$actionKey] ?? '');
        return $perm;
    }

    /** Enforce module-level permission (visibility/entry gate) */
    private function enforceModulePermission(): void
    {
        $perm = (string)($this->moduleDef['permission'] ?? '');
        if ($perm !== '') {
            $this->rbac->require((string)($_SESSION['user_id'] ?? ''), $perm);
        }
    }

    /** Enforce action-specific permission (create/edit/delete) */
    private function requireActionPermission(string $key): void
    {
        $perm = $this->getActionPermission($key);
        if ($perm !== '') {
            $this->rbac->require((string)($_SESSION['user_id'] ?? ''), $perm);
        }
    }

    /** CSRF checker + rotation (uses name="csrf") */
    private function assertCsrf(): void
    {
        $token = (string)($_POST['csrf'] ?? '');
        $sess  = (string)($_SESSION['csrf_token'] ?? '');
        if ($token === '' || $sess === '' || !hash_equals($sess, $token)) {
            FlashHelper::set('danger', 'Invalid CSRF token.');
            $this->redirect($this->baseUrl);
        }
        // Rotate after successful POST
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public function index(): string
    {
        $this->enforceModulePermission();

        $uid       = (string)($_SESSION['user_id'] ?? '');
        $canCreate = ($p = $this->getActionPermission('create')) !== '' ? $this->rbac->has($uid, $p) : false;
        $canEdit   = ($p = $this->getActionPermission('edit'))   !== '' ? $this->rbac->has($uid, $p) : false;
        $canDelete = ($p = $this->getActionPermission('delete')) !== '' ? $this->rbac->has($uid, $p) : false;

        $rawQ    = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        $search  = ($rawQ !== null && $rawQ !== '') ? mb_strtolower($rawQ) : null;
        $page    = max(1, (int)($_GET['pg'] ?? 1));
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        $result = $this->model->getPage($search, $perPage, $offset);
        $rows   = $result['rows'];
        $total  = $result['total'];

        $pages = max(1, (int)ceil($total / $perPage));
        $pager = [
            'page'     => $page,
            'perPage'  => $perPage,
            'total'    => $total,
            'pages'    => $pages,
            'hasPrev'  => $page > 1,
            'hasNext'  => $page < $pages,
            'prev'     => max(1, $page - 1),
            'next'     => min($pages, $page + 1),
            'baseUrl'  => $this->baseUrl,
            'query'    => $rawQ,
        ];

        // Data for modals
        $colleges  = $this->model->listColleges();
        $curricula = $this->model->listCurricula();

        $data = [
            'rows'      => $rows,
            'pager'     => $pager,
            'canCreate' => $canCreate,
            'canEdit'   => $canEdit,
            'canDelete' => $canDelete,
            'colleges'  => $colleges,
            'curricula' => $curricula,
            'csrf'      => (string)($_SESSION['csrf_token'] ?? ''),
        ];
        extract($data, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/../Views/index.php';
        return (string)ob_get_clean();
    }

    public function create(): void
    {
        $this->requireActionPermission('create');
        $this->assertCsrf();

        $data = [
            'course_code'   => trim((string)($_POST['course_code'] ?? '')),
            'course_name'   => trim((string)($_POST['course_name'] ?? '')),
            'curriculum_id' => (int)($_POST['curriculum_id'] ?? 0),
            'college_id'    => ($_POST['college_id'] ?? '') === '' ? null : (int)$_POST['college_id'],
        ];

        $errors = [];
        if ($data['course_code'] === '')   $errors[] = 'Course code is required.';
        if ($data['course_name'] === '')   $errors[] = 'Course name is required.';
        if ($data['curriculum_id'] <= 0)   $errors[] = 'Curriculum is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            $this->redirect($this->baseUrl);
        }

        try {
            $this->model->create($data);
            FlashHelper::set('success', 'Course created.');
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') {
                FlashHelper::set('danger', 'Duplicate: course code must be unique per curriculum.');
            } else {
                FlashHelper::set('danger', 'Create failed: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Create failed: ' . $e->getMessage());
        }

        $this->redirect($this->baseUrl);
    }

    public function edit(): void
    {
        $this->requireActionPermission('edit');
        $this->assertCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            FlashHelper::set('danger', 'Invalid ID.');
            $this->redirect($this->baseUrl);
        }

        $data = [
            'course_code'   => trim((string)($_POST['course_code'] ?? '')),
            'course_name'   => trim((string)($_POST['course_name'] ?? '')),
            'curriculum_id' => (int)($_POST['curriculum_id'] ?? 0),
            'college_id'    => ($_POST['college_id'] ?? '') === '' ? null : (int)$_POST['college_id'],
        ];

        $errors = [];
        if ($data['course_code'] === '')   $errors[] = 'Course code is required.';
        if ($data['course_name'] === '')   $errors[] = 'Course name is required.';
        if ($data['curriculum_id'] <= 0)   $errors[] = 'Curriculum is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            $this->redirect($this->baseUrl);
        }

        try {
            $ok = $this->model->update($id, $data);
            $ok ? FlashHelper::set('success', 'Course updated.')
                : FlashHelper::set('warning', 'No changes were made.');
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') {
                FlashHelper::set('danger', 'Duplicate: course code must be unique per curriculum.');
            } else {
                FlashHelper::set('danger', 'Update failed: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Update failed: ' . $e->getMessage());
        }

        $this->redirect($this->baseUrl);
    }

    public function delete(): void
    {
        $this->requireActionPermission('delete');
        $this->assertCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            FlashHelper::set('danger', 'Invalid ID.');
            $this->redirect($this->baseUrl);
        }

        try {
            $ok = $this->model->delete($id);
            $ok ? FlashHelper::set('success', 'Course deleted.')
                : FlashHelper::set('warning', 'Course not found.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Delete failed: ' . $e->getMessage());
        }

        $this->redirect($this->baseUrl);
    }
}
