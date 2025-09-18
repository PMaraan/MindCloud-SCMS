<?php
// /app/Modules/Curricula/Controllers/CurriculaController.php
declare(strict_types=1);

namespace App\Modules\Curricula\Controllers;

use App\Interfaces\StorageInterface;
use App\Security\RBAC;
use App\Helpers\FlashHelper;
use App\Modules\Curricula\Models\CurriculaModel;
use PDO;
final class CurriculaController
{
    private StorageInterface $db;
    private PDO $pdo;
    private RBAC $rbac;

    public function __construct(StorageInterface $db) {
        $this->db = $db;
        $this->pdo = $db->getConnection();
        $this->rbac = new RBAC($db);
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
    }

    public function index(): string
    {
        $registryPath = dirname(__DIR__, 4) . '/config/ModuleRegistry.php';
        $registry = is_file($registryPath) ? require $registryPath : [];
        $def = $registry['curricula'] ?? [];

        $userId = (string)($_SESSION['user_id'] ?? '');
        if (!empty($def['permission'])) {
            $this->rbac->require($userId, (string)$def['permission']);
        }

        $qRaw = $_GET['q'] ?? '';
        $q = strtolower(trim((string)$qRaw));

        // Standardize to global pager keys:
        $page    = max(1, (int)($_GET['pg'] ?? 1));
        $perPage = max(1, (int)(defined('UI_PER_PAGE_DEFAULT') ? UI_PER_PAGE_DEFAULT : 10));
        $offset  = ($page - 1) * $perPage;

        $model = new CurriculaModel($this->db);
        $total = $model->count($q);
        $rows  = $model->getPage($q, $perPage, $offset);

        // Compute from/to once here so the partial can just render
        $from = $total > 0 ? ($offset + 1) : 0;
        $to   = $total > 0 ? min($offset + $perPage, $total) : 0;

        // Build pager exactly for the global Pagination.php
        $pager = [
            'total'   => $total,
            'pg'      => $page,
            'perpage' => $perPage,
            'baseUrl' => BASE_PATH . '/dashboard?page=curricula',
            'query'   => $qRaw,
            'from'    => $from,
            'to'      => $to,
        ];

        $canCreate = !empty($def['actions']['create']) && $this->rbac->has($userId, (string)$def['actions']['create']);
        $canEdit   = !empty($def['actions']['edit'])   && $this->rbac->has($userId, (string)$def['actions']['edit']);
        $canDelete = !empty($def['actions']['delete']) && $this->rbac->has($userId, (string)$def['actions']['delete']);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        ob_start();
        // Expose standardized names directly
        $csrf       = $_SESSION['csrf_token'];
        // $rows, $canCreate, $canEdit, $canDelete, $pager already defined above
        require dirname(__DIR__) . '/Views/index.php';
        return (string)ob_get_clean();
    }

    public function create(): void
    {
        $this->requireAction('create');
        $this->validateCsrf();

        $code  = trim((string)($_POST['curriculum_code'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $start = trim((string)($_POST['effective_start'] ?? ''));
        $end   = trim((string)($_POST['effective_end'] ?? ''));

        if ($code === '' || $title === '' || $start === '') {
            FlashHelper::set('danger', 'Missing required fields.');
            $this->redirectBack();
        }

        try {
            (new CurriculaModel($this->db))->create([
                'curriculum_code' => $code,
                'title' => $title,
                'effective_start' => $start,
                'effective_end' => ($end !== '') ? $end : null,
            ]);
            FlashHelper::set('success', 'Curriculum created.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Create failed: ' . $e->getMessage());
        }
        $this->redirectBack();
    }

    public function edit(): void
    {
        $this->requireAction('edit');
        $this->validateCsrf();

        $id    = (int)($_POST['id'] ?? 0);
        $code  = trim((string)($_POST['curriculum_code'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $start = trim((string)($_POST['effective_start'] ?? ''));
        $end   = trim((string)($_POST['effective_end'] ?? ''));

        if ($id <= 0 || $code === '' || $title === '' || $start === '') {
            FlashHelper::set('danger', 'Missing required fields.');
            $this->redirectBack();
        }

        try {
            (new CurriculaModel($this->db))->update($id, [
                'curriculum_code' => $code,
                'title' => $title,
                'effective_start' => $start,
                'effective_end' => ($end !== '') ? $end : null,
            ]);
            FlashHelper::set('success', 'Curriculum updated.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Update failed: ' . $e->getMessage());
        }
        $this->redirectBack();
    }

    public function delete(): void
    {
        $this->requireAction('delete');
        $this->validateCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            FlashHelper::set('danger', 'Invalid ID.');
            $this->redirectBack();
        }

        try {
            (new CurriculaModel($this->db))->delete($id);
            FlashHelper::set('success', 'Curriculum deleted.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Delete failed: ' . $e->getMessage());
        }
        $this->redirectBack();
    }

    private function validateCsrf(): void
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
        $token = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$token)) {
            FlashHelper::set('danger', 'Invalid CSRF token.');
            $this->redirectBack();
        }
    }

    private function redirectBack(): void
    {
        header('Location: ' . BASE_PATH . '/dashboard?page=curricula');
        exit;
    }

    private function requireAction(string $actionKey): void
    {
        $registryPath = dirname(__DIR__, 4) . '/config/ModuleRegistry.php';
        $registry = is_file($registryPath) ? require $registryPath : [];
        $def = $registry['curricula'] ?? [];
        $perm = (string)($def['actions'][$actionKey] ?? '');
        if ($perm !== '') {
            $userId = (string)($_SESSION['user_id'] ?? '');
            $this->rbac->require($userId, $perm);
        }
    }
}
