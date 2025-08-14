<?php
// root/app/helpers/FlashHelper.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class FlashHelper {
    /**
     * Set a flash message.
     * @param string $type    success | error | warning | info
     * @param string $message The message text
     */
    public static function set(string $type, string $message): void {
        $_SESSION['flash'] = [
            'type' => $type, 
            'message' => $message
        ];
    }

    /**
     * Get and clear the flash message.
     * @return array|null
     */
    public static function get(): ?array {
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']); // Clear after retrieval
            return $flash;
        }
        return null;
    }
}

/*
// Setting a flash message
FlashHelper::set('success', 'User created successfully!');
header("Location: " . BASE_PATH . "/accounts");
exit;

// Retrieving 
$flashMessage = FlashHelper::get();

// Displaying in an HTML view
<?php include __DIR__ . '/../components/FlashMessage.php'; ?>

*/