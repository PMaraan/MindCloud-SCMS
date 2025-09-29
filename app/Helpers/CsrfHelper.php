<?php
// /app/Helpers/CsrfHelper.php
declare(strict_types=1);

namespace App\Helpers;

/**
 * CSRF protection helper.
 *
 * Responsibilities:
 *  - Boot: ensure a session token exists for the user's session.
 *  - token(): read the current session token.
 *  - inputField(): render a hidden input for forms.
 *  - assertOrRedirect(): validate POST token; on failure flash and redirect; on success rotate token.
 *  - rotate(): rotate to a fresh token (used automatically after successful validation).
 *
 * Notes:
 *  - Backward compatible: accepts POST names 'csrf' and 'csrf_token'.
 *  - Uses hash_equals() for timing-safe comparison.
 */
final class CsrfHelper
{
    /** Create a session token if missing. Call this once in bootstrap after session_start(). */
    public static function boot(): void
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /** Current session token (empty string if not set). */
    public static function token(): string
    {
        return (string)($_SESSION['csrf_token'] ?? '');
    }

    /**
     * Render a hidden input field for CSRF.
     * Default name is 'csrf' to match your controllers; accepts override.
     */
    public static function inputField(string $name = 'csrf'): string
    {
        $val = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        $nm  = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="' . $nm . '" value="' . $val . '">';
    }

    /**
     * Validate CSRF for POST actions; on failure, flash 'danger' and redirect.
     * On success, rotates the token for improved security (one-time token per POST).
     *
     * @param string $redirectUrl Absolute/BASE_PATH url to redirect on failure.
     */
    public static function assertOrRedirect(string $redirectUrl): void
    {
        // Read POST token, supporting both 'csrf' and temporary 'csrf_token'
        $token = (string)($_POST['csrf'] ?? ($_POST['csrf_token'] ?? ''));
        $sess  = (string)($_SESSION['csrf_token'] ?? '');

        $ok = ($token !== '' && $sess !== '' && hash_equals($sess, $token));
        if (!$ok) {
            // Lazy import to avoid circulars
            \App\Helpers\FlashHelper::set('danger', 'Invalid CSRF token.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        // Rotate after successful validation
        self::rotate();
    }

    /** Rotate the session CSRF token. */
    public static function rotate(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
