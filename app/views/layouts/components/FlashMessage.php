<?php if (!empty($flashMessage)): ?>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;">
    <div id="liveToast" class="toast align-items-center text-bg-<?= htmlspecialchars($flashMessage['type']) ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <?= htmlspecialchars($flashMessage['message']) ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toastEl = document.getElementById('liveToast');
    if (toastEl) {
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }
});
</script>

<?php endif; ?>

<?php // how to include: include $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/app/views/layouts/components/FlashMessage.php'; ?>
