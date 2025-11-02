<?php
// /app/Modules/Syllabi/Views/partials/EditModal.php
/**
 * Placeholder Edit modal.
 */
$base = defined('BASE_PATH') ? BASE_PATH : '';
$csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<div class="modal fade" id="syEditModal" tabindex="-1" aria-hidden="true" aria-labelledby="syEditLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= $base ?>/dashboard?page=syllabi&action=update" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="syEditLabel">Edit Syllabus (Placeholder)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="syllabus_id" id="sy-edit-id" value="">
        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" class="form-control" name="title" id="sy-edit-title" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Course</label>
          <input type="text" class="form-control" name="course" id="sy-edit-course">
        </div>
        <div class="mb-3">
          <label class="form-label">Section</label>
          <input type="text" class="form-control" name="section" id="sy-edit-section">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>
