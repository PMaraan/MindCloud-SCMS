<?php
/**
 * /app/Modules/SyllabusTemplates/Views/partials/DeleteModal.php
 * Delete confirmation modal (UI-only). JS will set the title and handle confirm.
 */
?>
<div class="modal fade" id="tbDeleteModal" tabindex="-1" aria-hidden="true" aria-labelledby="tbDeleteLabel" data-no-reset>
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tbDeleteLabel">Delete Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to <strong>permanently delete</strong> the template:</p>
        <p class="fw-semibold" id="tb-delete-title">—</p>
        <p class="text-danger small mb-0">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="tb-delete-confirm" class="btn btn-danger">Yes, delete</button>
      </div>
    </div>
  </div>
</div>
