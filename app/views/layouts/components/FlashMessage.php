<?php if (!empty($flashMessage)): ?>

<div class="alert alert-<?= htmlspecialchars($flashMessage['type']) ?> alert-dismissible fade show" 
    role="alert"
    style="position: fixed; top: 20px; right: 20px; z-index: 1050; min-width: 300px;"
>
    <?= htmlspecialchars($flashMessage['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<?php endif; ?>
