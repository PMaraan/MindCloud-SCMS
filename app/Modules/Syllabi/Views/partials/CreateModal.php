<?php
// /app/Modules/Syllabi/Views/partials/CreateModal.php
/**
 * Placeholder Create modal.
 * Uses csrf_token for now (per your current setup).
 */
$base = defined('BASE_PATH') ? BASE_PATH : '';
$csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<div class="modal fade" id="syCreateModal" tabindex="-1" aria-hidden="true" aria-labelledby="syCreateLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= $base ?>/dashboard?page=syllabi&action=create" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="syCreateLabel">New Syllabus (Placeholder)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" class="form-control" name="title" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Course</label>
          <input type="text" class="form-control" name="course">
        </div>
        <div class="mb-3">
          <label class="form-label">Section</label>
          <input type="text" class="form-control" name="section">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Create</button>
      </div>
    </form>
  </div>
</div>
