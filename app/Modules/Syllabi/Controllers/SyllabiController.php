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
     * Index (list) – global pagination + search
     * URL: /dashboard?page=syllabi[&pg=1][&q=...]
     */
    public function index(): string
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABI_VIEW);

        $user      = $this->userModel->getUserProfile((string)$_SESSION['user_id']);
        $role      = (string)($user['role_name'] ?? '');
        $collegeId = isset($user['college_id']) ? (int)$user['college_id'] : null;
        $programId = isset($user['program_id']) ? (int)$user['program_id'] : null;

        $ASSET_BASE = (defined('BASE_PATH') ? BASE_PATH : '') . '/public';
        $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $PAGE_KEY = 'syllabi';

        // Global pagination pattern
        $pg      = isset($_GET['pg']) && ctype_digit((string)$_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
        $perpage = 12; // UI grid-ready; adjust later if needed
        $q       = trim((string)($_GET['q'] ?? ''));

        // Base URL must already contain '?'
        $baseUrl = (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=' . $PAGE_KEY;

        // Delegate to model (abstracted; you’ll implement real queries later)
        $list = $this->model->listSyllabi($role, $collegeId, $programId, $pg, $perpage, $q);
        $rows  = $list['rows']  ?? [];
        $total = $list['total'] ?? 0;
        $from  = $total ? (($pg - 1) * $perpage + 1) : 0;
        $to    = $total ? min($from + $perpage - 1, $total) : 0;

        $viewData = [
            'ASSET_BASE' => $ASSET_BASE,
            'esc'        => $esc,
            'PAGE_KEY'   => $PAGE_KEY,
            'user'       => $user,
            'role'       => $role,

            // Listing + pager
            'rows'   => $rows,
            'pager'  => [
                'total'   => $total,
                'pg'      => $pg,
                'perpage' => $perpage,
                'baseUrl' => $baseUrl,
                'query'   => $q,
                'from'    => $from,
                'to'      => $to,
            ],

            // RBAC toggles (will be used by partials)
            'canCreate' => $this->canCreate($role, $collegeId, $programId),
            'canEdit'   => $this->canEdit($role, $collegeId, $programId),
            'canDelete' => $this->canDelete($role, $collegeId, $programId),
        ];

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
