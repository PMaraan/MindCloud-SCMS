<?php /* app/Modules/College/Views/partials/EditModal.php */ ?>
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_PATH ?>/dashboard?page=colleges&action=edit" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
        <input type="hidden" name="id">

        <div class="modal-header">
          <h5 class="modal-title">Edit College</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body row g-3">
          <div class="col-12">
            <label class="form-label">Short Name <span class="text-danger">*</span></label>
            <input class="form-control" name="short_name" maxlength="10" required>
          </div>
          <div class="col-12">
            <label class="form-label">College Name <span class="text-danger">*</span></label>
            <input class="form-control" name="college_name" maxlength="100" required>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">Save changes</button>
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
