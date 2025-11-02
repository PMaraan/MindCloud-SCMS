<?php
// /app/Modules/Syllabi/Views/partials/DeleteModal.php
/**
 * Placeholder Delete modal.
 * Uses csrf_token for now (per your current setup).
 */
$base = defined('BASE_PATH') ? BASE_PATH : '';
$csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<div class="modal fade" id="syDeleteModal" tabindex="-1" aria-hidden="true" aria-labelledby="syDeleteLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= $base ?>/dashboard?page=syllabi&action=delete" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="syDeleteLabel">Delete Syllabus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="syllabus_id" id="sy-del-id" value="">
        <p class="mb-0">Are you sure you want to delete this syllabus?</p>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>
