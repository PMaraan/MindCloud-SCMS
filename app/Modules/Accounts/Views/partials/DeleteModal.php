<?php // /app/Modules/Accounts/Views/partials/DeleteModal.php ?>
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true" aria-labelledby="deleteUserLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= BASE_PATH ?>/dashboard?page=accounts&action=delete" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteUserLabel">Delete User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        
        <input type="hidden" name="id_no" id="delete-id-no" value="">

        <p class="mb-0">
          Are you sure you want to delete
          <strong id="delete-username">this account</strong>
          with ID No. <strong id="delete-idno-display"></strong>?
          This action cannot be undone.
        </p>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>
