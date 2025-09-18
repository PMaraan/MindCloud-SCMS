<?php
// /app/Modules/Programs/Controllers/ProgramsController.php
declare(strict_types=1);

namespace App\Modules\Programs\Controllers;

use App\Config\Permissions;
use App\Helpers\FlashHelper;
use App\Modules\Programs\Models\ProgramsModel;
use App\Security\RBAC;
use App\Interfaces\StorageInterface;

final class ProgramsController
{
    private ProgramsModel $model;
    private StorageInterface $db;
    private RBAC $rbac;

    public function __construct(StorageInterface $db)
    {
        $this->db    = $db;
        $this->model = new ProgramsModel($db);
        $this->rbac  = new RBAC($db); // instantiate once, reuse
    }

    /** Returns HTML (DashboardController expects a string). */
    public function index(): string
    {
        // Dashboard already gates the module, but this keeps direct calls safe:
        $this->rbac->require((string)$_SESSION['user_id'], Permissions::PROGRAMS_VIEW);

        // Query params for search and pagination
        $rawQ = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $q    = ($rawQ !== '') ? mb_strtolower($rawQ) : null;

        $page    = max(1, (int)($_GET['pg'] ?? 1));
        $perPage = max(1, (int)(defined('UI_PER_PAGE_DEFAULT') ? UI_PER_PAGE_DEFAULT : 10));
        $offset  = ($page - 1) * $perPage;

        [$rows, $total] = $this->model->getProgramsPage($q, $perPage, $offset);

        $pager = [
            'total'   => $total,
            'pg'      => $page,                         // ← global partial expects 'pg'
            'perpage' => $perPage,                      // ← and 'perpage'
            'baseUrl' => BASE_PATH . '/dashboard?page=programs',
            'query'   => $rawQ,                         // keep original casing in UI
            'from'    => ($total === 0) ? 0 : ($offset + 1),
            'to'      => min($offset + $perPage, $total),
        ];

        $userId    = (string)$_SESSION['user_id'];
        $canCreate = $this->rbac->has($userId, Permissions::PROGRAMS_CREATE);
        $canEdit   = $this->rbac->has($userId, Permissions::PROGRAMS_EDIT);
        $canDelete = $this->rbac->has($userId, Permissions::PROGRAMS_DELETE);

        // Preload dropdown options so views don't construct models.
        $colleges = $this->model->getCollegesList();

        ob_start();
        extract(compact('pager','rows','canCreate','canEdit','canDelete','colleges'), EXTR_OVERWRITE);
        /** @noinspection PhpIncludeInspection */
        require __DIR__ . '/../Views/index.php';
        return (string)ob_get_clean();
    }

    public function create(): void
    {
        $this->rbac->require((string)$_SESSION['user_id'], Permissions::PROGRAMS_CREATE);

        $data = [
            'program_name' => trim((string)($_POST['program_name'] ?? '')),
            'college_id'   => (int)($_POST['college_id'] ?? 0),
        ];

        try {
            if ($data['program_name'] === '' || $data['college_id'] <= 0) {
                throw new \InvalidArgumentException('Program name and college are required.');
            }
            $this->model->createProgram($data);
            FlashHelper::set('success', 'Program created.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Create failed: ' . $e->getMessage());
        }

        header('Location: ' . BASE_PATH . '/dashboard?page=programs');
        exit;
    }

    public function edit(): void
    {
        $this->rbac->require((string)$_SESSION['user_id'], Permissions::PROGRAMS_EDIT);

        $programId = (int)($_POST['program_id'] ?? 0);
        $data = [
            'program_name' => trim((string)($_POST['program_name'] ?? '')),
            'college_id'   => (int)($_POST['college_id'] ?? 0),
        ];

        try {
            if ($programId <= 0) {
                throw new \InvalidArgumentException('Invalid program id.');
            }
            if ($data['program_name'] === '' || $data['college_id'] <= 0) {
                throw new \InvalidArgumentException('Program name and college are required.');
            }
            $this->model->updateProgram($programId, $data);
            FlashHelper::set('success', 'Program updated.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Update failed: ' . $e->getMessage());
        }

        header('Location: ' . BASE_PATH . '/dashboard?page=programs');
        exit;
    }

    public function delete(): void
    {
        $this->rbac->require((string)$_SESSION['user_id'], Permissions::PROGRAMS_DELETE);

        $programId = (int)($_POST['program_id'] ?? 0);

        try {
            if ($programId <= 0) {
                throw new \InvalidArgumentException('Invalid program id.');
            }
            $this->model->deleteProgram($programId);
            FlashHelper::set('success', 'Program deleted.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Delete failed: ' . $e->getMessage());
        }

        header('Location: ' . BASE_PATH . '/dashboard?page=programs');
        exit;
    }
}
