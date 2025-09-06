<?php /* DeleteModal */ ?>
<div class="modal fade" id="DeleteModal" tabindex="-1" aria-hidden="true" aria-labelledby="DeleteCourseLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= BASE_PATH ?>/dashboard?page=courses&action=delete" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="DeleteCourseLabel">Delete Course</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" id="delete-id" name="id">
        <p class="mb-0">You are about to delete: <strong id="delete-course-label">—</strong></p>
        <p class="text-muted small mb-0">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger" type="submit">Delete</button>
      </div>
    </form>
  </div>
</div>
