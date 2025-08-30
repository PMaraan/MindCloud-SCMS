<?php
namespace App\Helpers;

/**
 * Very small helper to render module views with data.
 * Usage: View::render('Accounts', 'index', ['accounts' => $accounts]);
 */
final class View
{
    public static function render(string $module, string $view, array $data = []): void
    {
        $base = dirname(__DIR__) . "/Modules/{$module}/Views";
        $file = $base . '/' . ltrim($view, '/');
        if (!str_ends_with($file, '.php')) $file .= '.php';

        if (!is_file($file)) {
            http_response_code(500);
            exit("View not found: {$file}");
        }

        // Extract variables for the view safely
        extract($data, EXTR_SKIP);

        // Ensure CSRF token exists (forms in modals use it)
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        require $file;
    }
}
