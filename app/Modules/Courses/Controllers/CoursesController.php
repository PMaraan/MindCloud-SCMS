<?php
// /app/Modules/Courses/Controllers/CoursesController.php
namespace App\Modules\Courses\Controllers;

use App\Helpers\FlashHelper;
use App\Security\RBAC;
use App\Modules\Courses\Models\CoursesModel;
use App\Interfaces\StorageInterface;
use App\Models\UserModel;

final class CoursesController
{
    private StorageInterface $db;
    private CoursesModel $model;
    private RBAC $rbac;
    private UserModel $userModel;

     /** Cached module definition from ModuleRegistry */
    private array $moduleDef = [];

    /** Convenience: base URL for this module */
    private string $baseUrl;

    /** Role groupings for finer access controls*/
    private array $GLOBAL_ROLES  = ['VPAA','VPAA Secretary'];
    private array $DEAN_ROLES    = ['Dean'];
    private array $CHAIR_ROLES   = ['Chair'];
    private array $FACULTY_ROLES = ['Professor'];

    public function __construct(StorageInterface $db)
    {
        $this->db   = $db;
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
        $this->model = new CoursesModel($db);
        $this->rbac  = new RBAC($db);
        $this->userModel = new UserModel($db);

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
        $user      = $this->userModel->getUserProfile($uid);
        $role      = (string)($user['role_name'] ?? '');
        $collegeId = isset($user['college_id']) ? (int)$user['college_id'] : null;

        $canCreate = ($p = $this->getActionPermission('create')) !== '' ? $this->rbac->has($uid, $p) : false;
        $canEdit   = ($p = $this->getActionPermission('edit'))   !== '' ? $this->rbac->has($uid, $p) : false;
        $canDelete = ($p = $this->getActionPermission('delete')) !== '' ? $this->rbac->has($uid, $p) : false;

        $rawQ   = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        $search = ($rawQ !== null && $rawQ !== '') ? mb_strtolower($rawQ) : null;

        $page    = max(1, (int)($_GET['pg'] ?? 1));
        $perPage = max(1, (int)(defined('UI_PER_PAGE_DEFAULT') ? UI_PER_PAGE_DEFAULT : 10));
        $offset  = ($page - 1) * $perPage;

        $restrictToCollege = in_array($role, $this->DEAN_ROLES, true) || in_array($role, $this->CHAIR_ROLES, true);
        $result = $this->model->getPage(
            $search,
            $perPage,
            $offset,
            $restrictToCollege ? $collegeId : null
        );
        $rows  = $result['rows'];
        $total = $result['total'];

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

        $canAssignProfessors = $this->canAssignProfessors($role);
        $professors = $canAssignProfessors
            ? $this->model->listProfessors($collegeId)
            : [];

        $data = [
            'rows'      => $rows,
            'pager'     => $pager,
            'canCreate' => $canCreate,
            'canEdit'   => $canEdit,
            'canDelete' => $canDelete,
            'colleges'  => $colleges,
            'curricula' => $curricula,
            'csrf'      => (string)($_SESSION['csrf_token'] ?? ''),
            'role'                => $role,
            'collegeId'           => $collegeId,
            'restrictCollege'     => $restrictToCollege,
            'lockedCollegeId'     => $restrictToCollege ? $collegeId : null,
            'canAssignProfessors' => $canAssignProfessors,
            'professors'          => $professors,
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

        $uid       = (string)($_SESSION['user_id'] ?? '');
        $user      = $this->userModel->getUserProfile($uid);
        $role      = (string)($user['role_name'] ?? '');
        $collegeId = isset($user['college_id']) ? (int)$user['college_id'] : null;
        $canAssignProfessors = $this->canAssignProfessors($role);

        $data = [
            'course_code'   => trim((string)($_POST['course_code'] ?? '')),
            'course_name'   => trim((string)($_POST['course_name'] ?? '')),
            'department_id' => ($_POST['department_id'] ?? '') === '' ? null : (int)$_POST['department_id'],
        ];
        // Multiple selections allowed; may be absent
        $curriculumIds = isset($_POST['curriculum_ids']) && is_array($_POST['curriculum_ids'])
            ? array_filter($_POST['curriculum_ids'], fn($v) => (int)$v > 0)
            : [];

        $professorIds = $this->resolveProfessorIds($_POST['professor_ids'] ?? [], $canAssignProfessors, $collegeId);

        $errors = [];
        if ($data['course_code'] === '') $errors[] = 'Course code is required.';
        if ($data['course_name'] === '') $errors[] = 'Course name is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            $this->redirect($this->baseUrl);
        }

        try {
            $newId = $this->model->create([
                'course_code'   => $data['course_code'],
                'course_name'   => $data['course_name'],
                'department_id' => $data['department_id'],
            ]);

            if ($canAssignProfessors) {
                $this->model->setCourseProfessors((int)$newId, $professorIds);
            }

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

        $uid       = (string)($_SESSION['user_id'] ?? '');
        $user      = $this->userModel->getUserProfile($uid);
        $role      = (string)($user['role_name'] ?? '');
        $collegeId = isset($user['college_id']) ? (int)$user['college_id'] : null;
        $canAssignProfessors = $this->canAssignProfessors($role);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            FlashHelper::set('danger', 'Invalid ID.');
            $this->redirect($this->baseUrl);
        }

        $data = [
            'course_code'   => trim((string)($_POST['course_code'] ?? '')),
            'course_name'   => trim((string)($_POST['course_name'] ?? '')),
            'department_id' => ($_POST['department_id'] ?? '') === '' ? null : (int)$_POST['department_id'],
        ];

        $curriculumIds = isset($_POST['curriculum_ids']) && is_array($_POST['curriculum_ids'])
            ? array_filter($_POST['curriculum_ids'], fn($v) => (int)$v > 0)
            : [];

        $professorIds = $this->resolveProfessorIds($_POST['professor_ids'] ?? [], $canAssignProfessors, $collegeId);

        $errors = [];
        if ($data['course_code'] === '') $errors[] = 'Course code is required.';
        if ($data['course_name'] === '') $errors[] = 'Course name is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            $this->redirect($this->baseUrl);
        }

        try {
            $ok = $this->model->update($id, $data);
            $this->model->setCourseCurricula($id, $curriculumIds);
            if ($canAssignProfessors) {
                $this->model->setCourseProfessors($id, $professorIds);
            }

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

    private function canAssignProfessors(string $role): bool
    {
        return in_array($role, $this->DEAN_ROLES, true) || in_array($role, $this->CHAIR_ROLES, true);
    }

    private function resolveProfessorIds(mixed $raw, bool $allowAssignments, ?int $collegeId): array
    {
        if (!$allowAssignments) {
            return [];
        }

        $values = is_array($raw) ? $raw : [$raw];
        $clean  = [];

        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value !== '' && !isset($clean[$value])) {
                $clean[$value] = $value;
            }
        }

        if (empty($clean)) {
            return [];
        }

        $allowed = $this->model->listProfessors($collegeId);
        if (empty($allowed)) {
            return [];
        }

        $allowedMap = [];
        foreach ($allowed as $prof) {
            $allowedMap[(string)$prof['id_no']] = true;
        }

        return array_values(array_filter(
            $clean,
            static fn(string $id): bool => isset($allowedMap[$id])
        ));
    }
}
