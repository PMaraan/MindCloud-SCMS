<?php
// Always make sure session is started before using flash
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/app/helpers/FlashHelper.php';
$flashMessage = FlashHelper::get();

if (!empty($flashMessage)): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;">
        <div class="toast align-items-center text-bg-<?= htmlspecialchars($flashMessage['type']) ?> border-0" 
             role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <?= htmlspecialchars($flashMessage['message']) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                        data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.toast').forEach(toastEl => {
            new bootstrap.Toast(toastEl, { delay: 3000 }).show();
        });
    });
    </script>
<?php endif; ?>
