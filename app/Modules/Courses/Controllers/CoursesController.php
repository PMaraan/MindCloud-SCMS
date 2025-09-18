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

        $rawQ   = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        $search = ($rawQ !== null && $rawQ !== '') ? mb_strtolower($rawQ) : null;

        $page    = max(1, (int)($_GET['pg'] ?? 1));
        $perPage = max(1, (int)(defined('UI_PER_PAGE_DEFAULT') ? UI_PER_PAGE_DEFAULT : 10));
        $offset  = ($page - 1) * $perPage;

        $result = $this->model->getPage($search, $perPage, $offset);
        $rows   = $result['rows'];
        $total  = $result['total'];

        // Compute range for "Showing X–Y of Z"
        $from = $total > 0 ? ($offset + 1) : 0;
        $to   = $total > 0 ? min($total, $offset + count($rows)) : 0;

        // Build pager expected by /app/Views/partials/Pagination.php
        $pager = [
            'pg'       => $page,
            'perpage'  => $perPage,
            'total'    => $total,
            'baseUrl'  => $this->baseUrl,  // e.g., BASE_PATH . '/dashboard?page=courses'
            'query'    => $rawQ,
            'from'     => $from,
            'to'       => $to,
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
            'course_code' => trim((string)($_POST['course_code'] ?? '')),
            'course_name' => trim((string)($_POST['course_name'] ?? '')),
            'college_id'  => ($_POST['college_id'] ?? '') === '' ? null : (int)$_POST['college_id'],
        ];
        // Multiple selections allowed; may be absent
        $curriculumIds = isset($_POST['curriculum_ids']) && is_array($_POST['curriculum_ids'])
            ? array_filter($_POST['curriculum_ids'], fn($v) => (int)$v > 0)
            : [];

        $errors = [];
        if ($data['course_code'] === '') $errors[] = 'Course code is required.';
        if ($data['course_name'] === '') $errors[] = 'Course name is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            $this->redirect($this->baseUrl);
        }

        try {
            $newId = $this->model->create([
                'course_code' => $data['course_code'],
                'course_name' => $data['course_name'],
                'college_id'  => $data['college_id'],
                // curriculum_id removed (M:N now)
            ]);
            // link selections
            if (!empty($curriculumIds)) {
                $this->model->setCourseCurricula((int)$newId, $curriculumIds);
            }
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
            'course_code' => trim((string)($_POST['course_code'] ?? '')),
            'course_name' => trim((string)($_POST['course_name'] ?? '')),
            'college_id'  => ($_POST['college_id'] ?? '') === '' ? null : (int)$_POST['college_id'],
        ];
        $curriculumIds = isset($_POST['curriculum_ids']) && is_array($_POST['curriculum_ids'])
            ? array_filter($_POST['curriculum_ids'], fn($v) => (int)$v > 0)
            : [];

        $errors = [];
        if ($data['course_code'] === '') $errors[] = 'Course code is required.';
        if ($data['course_name'] === '') $errors[] = 'Course name is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            $this->redirect($this->baseUrl);
        }

        try {
            $ok = $this->model->update($id, $data);
            // rewrite mappings (even if none)
            $this->model->setCourseCurricula($id, $curriculumIds);

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
