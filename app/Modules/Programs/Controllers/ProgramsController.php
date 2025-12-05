<?php
// /app/Modules/Programs/Controllers/ProgramsController.php
declare(strict_types=1);

namespace App\Modules\Programs\Controllers;

use App\Config\Permissions;
use App\Helpers\FlashHelper;
use App\Interfaces\StorageInterface;
use App\Models\UserModel;
use App\Modules\Programs\Models\ProgramsModel;
use App\Security\RBAC;
use App\Services\AssignmentsService;

final class ProgramsController
{
    private StorageInterface $db;
    private ProgramsModel $model;
    private RBAC $rbac;

    public function __construct(StorageInterface $db)
    {
        $this->db    = $db;
        $this->model = new ProgramsModel($db);
        $this->rbac  = new RBAC($db);
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
    }

    /** Returns HTML for the Dashboard shell. */
    public function index(): string
    {
        // Gate module view (Dashboard also gates, but keep direct calls safe)
        $this->rbac->require((string)$_SESSION['user_id'], Permissions::PROGRAMS_VIEW);

        // Search + pagination (global standard)
        $rawQ    = isset($_GET['q']) ? trim((string)$_GET['q']) : ''; // Search param
        $search       = ($rawQ !== '') ? mb_strtolower($rawQ) : null; // Normalise search param
        $status = isset($_GET['status']) ? trim((string)$_GET['status']) : 'active'; // Status param
        $page    = max(1, (int)($_GET['pg'] ?? 1)); // Current page
        $perPage = defined('UI_PER_PAGE_DEFAULT') ? (int)UI_PER_PAGE_DEFAULT : 10; // Items per page
        $offset  = ($page - 1) * $perPage; // Offset for query

        [$rows, $total] = $this->model->getProgramsPage($search, $perPage, $offset, $status); // Db query

        $pager = [
            'total'   => $total,
            'pg'      => $page,
            'perpage' => $perPage,
            'baseUrl' => BASE_PATH . '/dashboard?page=programs',
            'query'   => $rawQ,
            'from'    => ($total === 0) ? 0 : ($offset + 1),
            'to'      => min($offset + $perPage, $total),
            'status'  => $status,
        ];

        $uid       = (string)($_SESSION['user_id'] ?? '');
        $canCreate = $this->rbac->has($uid, Permissions::PROGRAMS_CREATE);
        $canEdit   = $this->rbac->has($uid, Permissions::PROGRAMS_EDIT);
        $canDelete = $this->rbac->has($uid, Permissions::PROGRAMS_DELETE);

        // Preload dropdown options
        $colleges = $this->model->getCollegesList();                 // departments with is_college = TRUE
        $chairs   = (new UserModel($this->db))->listUsersByRole('Program Chair'); // adjust role name if needed

        // Render
        ob_start();
        extract(compact('pager','rows','canCreate','canEdit','canDelete','colleges','chairs'), EXTR_OVERWRITE);
        require __DIR__ . '/../Views/index.php';
        return (string)ob_get_clean();
    }

    /** Create program + optional chair assignment. */
    public function create(): void
    {
        $this->rbac->require((string)$_SESSION['user_id'], Permissions::PROGRAMS_CREATE);

        $data = [
            'program_name'  => trim((string)($_POST['program_name'] ?? '')),
            'department_id' => (int)($_POST['department_id'] ?? 0),
        ];
        $chairIdNo = trim((string)($_POST['chair_id_no'] ?? ''));

        try {
            if ($data['program_name'] === '' || $data['department_id'] <= 0) {
                throw new \InvalidArgumentException('Program name and department are required.');
            }

            $programId = $this->model->createProgram($data);

            if ($chairIdNo !== '') {
                try {
                    (new AssignmentsService($this->db))->setProgramChair((int)$programId, $chairIdNo);
                    FlashHelper::set('success', 'Program created. Chair assigned.');
                } catch (\DomainException $e) {
                    FlashHelper::set('warning', 'Program created, but chair not assigned: ' . $e->getMessage());
                }
            } else {
                FlashHelper::set('success', 'Program created.');
            }
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Create failed: ' . $e->getMessage());
        } finally {
            unset($_SESSION['st_cache'], $_SESSION['tb_cache']); // invalidate related caches
        }

        header('Location: ' . BASE_PATH . '/dashboard?page=programs');
        exit;
    }

    /** Update program + optional chair assignment (set or clear). */
    public function edit(): void
    {
        $this->rbac->require((string)$_SESSION['user_id'], Permissions::PROGRAMS_EDIT);

        $programId = (int)($_POST['program_id'] ?? 0);
        $data = [
            'program_name'  => trim((string)($_POST['program_name'] ?? '')),
            'department_id' => (int)($_POST['department_id'] ?? 0),
        ];
        $chairIdNo = trim((string)($_POST['chair_id_no'] ?? '')); // empty => clear

        try {
            if ($programId <= 0) {
                throw new \InvalidArgumentException('Invalid program id.');
            }
            if ($data['program_name'] === '' || $data['department_id'] <= 0) {
                throw new \InvalidArgumentException('Program name and department are required.');
            }

            $this->model->updateProgram($programId, $data);

            try {
                (new AssignmentsService($this->db))->setProgramChair($programId, $chairIdNo !== '' ? $chairIdNo : null);
                FlashHelper::set('success', 'Program updated.');
            } catch (\DomainException $e) {
                FlashHelper::set('warning', 'Program updated, but chair not assigned: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Update failed: ' . $e->getMessage());
        } finally {
            unset($_SESSION['st_cache'], $_SESSION['tb_cache']);
        }

        header('Location: ' . BASE_PATH . '/dashboard?page=programs');
        exit;
    }

    /** Delete program. */
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
        } finally {
            unset($_SESSION['st_cache'], $_SESSION['tb_cache']);
        }

        header('Location: ' . BASE_PATH . '/dashboard?page=programs');
        exit;
    }

    /** 
     * Return JSON list of Program Chairs for a given department.
     * GET /api/programs/chairs?department_id=123
     * RBAC: requires PROGRAMS_VIEW
     */
    public function apiChairs(): void
    {
        $this->rbac->require((string)$_SESSION['user_id'], \App\Config\Permissions::PROGRAMS_VIEW);

        header('Content-Type: application/json; charset=utf-8');

        $deptId = (int)($_GET['department_id'] ?? 0);
        if ($deptId <= 0) {
            echo json_encode(['chairs' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $users = (new \App\Models\UserModel($this->db))
                ->listChairsInDepartment($deptId); // must filter by department

            $chairs = array_map(static function ($u) {
                $lname = (string)($u['lname'] ?? '');
                $fname = (string)($u['fname'] ?? '');
                $mname = (string)($u['mname'] ?? '');
                $full  = trim($lname . ', ' . $fname . ($mname !== '' ? ' ' . $mname : ''));
                return [
                    'id_no'     => (string)$u['id_no'],
                    'full_name' => $full,
                ];
            }, $users ?? []);

            echo json_encode(['chairs' => $chairs], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['chairs' => []], JSON_UNESCAPED_UNICODE);
        }
    }
}
