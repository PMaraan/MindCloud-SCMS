<?php
// filepath: c:\xampp\htdocs\MindCloud-SCMS\app\Modules\Syllabi\Views\partials\DeleteModal.php
$csrf = $esc($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<div class="modal fade" id="syDeleteModal" tabindex="-1" aria-labelledby="syDeleteLabel" aria-hidden="true" data-no-reset>
  <div class="modal-dialog">
    <form method="post" action="<?= $base ?>/dashboard?page=syllabi&action=delete" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="syDeleteLabel">Delete Syllabus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="syllabus_id" id="sy-del-id" value="">
        <p>Are you sure you want to <strong>permanently delete</strong> this syllabus?</p>
        <p class="fw-semibold" id="sy-delete-title">—</p>
        <p class="text-danger small mb-0">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="sy-delete-confirm">Yes, delete</button>
      </div>
    </form>
  </div>
</div>
