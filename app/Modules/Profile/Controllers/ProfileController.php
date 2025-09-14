<?php
// /app/Modules/Profile/Controllers/ProfileController.php
declare(strict_types=1);

namespace App\Modules\Profile\Controllers;

use App\Interfaces\StorageInterface;
use App\Modules\Profile\Models\ProfileModel;

final class ProfileController
{
    private StorageInterface $db;
    private ProfileModel $model;

    public function __construct(StorageInterface $db)
    {
        // Session already started in bootstrap.php
        $this->db = $db;
        $this->model = new ProfileModel($db);
    }

    /**
     * GET /profile
     * Private page with Topbar only (no Sidebar).
     * Reads basic profile from DB and resolves avatar with safe fallback.
     */
    public function render(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }

        $idNo = (string)$_SESSION['user_id'];

        // Read from DB (no avatar column assumed yet)
        $row = $this->model->getByIdNo($idNo) ?? [];

        // If you later add $row['avatar'], this function will auto-resolve it.
        $avatarUrl = $this->resolveAvatarUrl($row['avatar'] ?? null);

        $profile = [
            'id_no'   => $row['id_no']  ?? $idNo,
            'fname'   => $row['fname']  ?? 'Juan',
            'mname'   => $row['mname']  ?? 'Santos',
            'lname'   => $row['lname']  ?? 'Dela Cruz',
            'email'   => $row['email']  ?? 'juan.delacruz@example.com',
            'avatar'  => $avatarUrl,

            // Display-only placeholders for now; you can fill from DB later
            'role'    => 'Faculty',
            'college' => '',
            'program' => '',
        ];

        ob_start();
        $profileData = $profile;
        require __DIR__ . '/../Views/index.php';
        $contentHtml = ob_get_clean();

        require dirname(__DIR__, 3) . '/Views/layouts/TopbarOnlyLayout.php';
    }

    /**
     * Resolve any DB-stored avatar path to a public URL; fall back to default SVG.
     * Accepts either full URLs, absolute app paths (/uploads/avatars/x.png), or relative paths.
     */
    private function resolveAvatarUrl(?string $dbValue): string
    {
        $v = trim((string)$dbValue);

        // No value -> default SVG under /public/assets
        if ($v === '') {
            return BASE_PATH . '/public/assets/images/user-default.svg';
        }

        // Full external URL
        if (preg_match('#^https?://#i', $v) === 1) {
            return $v;
        }

        // Absolute app path from DB
        // If DB stored '/public/uploads/..' -> keep it; otherwise prefix '/public'
        if (strlen($v) > 0 && $v[0] === '/') {
            if (str_starts_with($v, '/public/')) {
                return BASE_PATH . $v;
            }
            return BASE_PATH . '/public' . $v;
        }

        // Relative path (e.g., 'uploads/avatars/abc.png' or 'assets/...'):
        // Treat as relative to /public
        return BASE_PATH . '/public/' . ltrim($v, '/');
    }
}