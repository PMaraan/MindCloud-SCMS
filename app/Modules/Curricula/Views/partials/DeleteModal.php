<?php
// /app/Modules/Curricula/Views/partials/DeleteModal.php
/**
 * Curricula Delete Modal.
 *
 * Expects:
 * @var string $csrf CSRF token (optional; will fallback to $_SESSION['csrf_token'])
 *
 * Hidden fields populated by JS:
 *  - #delete-id
 *  - #delete-id-display
 *  - #delete-code-display
 *  - #delete-title-display
 */
?>
<div class="modal fade" id="DeleteModal" tabindex="-1" aria-hidden="true" aria-labelledby="DeleteLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= BASE_PATH ?>/dashboard?page=curricula&action=delete" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="DeleteLabel">Delete Curriculum</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <!-- CSRF (mirror Accounts modal style/name) -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf ?? ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        <!-- Primary key -->
        <input type="hidden" name="id" id="delete-id" value="">

        <p class="mb-0">
          Are you sure you want to delete
          the curriculum <strong id="delete-title-display">this curriculum</strong>
          (Code: <strong id="delete-code-display"></strong>,
          ID: <strong id="delete-id-display"></strong>)?
          This action cannot be undone.
        </p>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger rounded-3">Delete</button>
      </div>
    </form>
  </div>
</div>
