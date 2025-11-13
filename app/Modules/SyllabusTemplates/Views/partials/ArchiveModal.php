<?php
// /app/Modules/SyllabusTemplates/Views/partials/ArchiveModal.php
/**
 * Archive / Unarchive confirmation modal (UI-only). JS will set the title and handle confirm.
 */
?>
<div class="modal fade" id="tbArchiveModal" tabindex="-1" aria-hidden="true" aria-labelledby="tbArchiveLabel" data-no-reset>
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tbArchiveLabel">Archive Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="tb-archive-body">Are you sure you want to archive this template:</p>
        <p class="fw-semibold" id="tb-archive-title">—</p>
        <p class="text-muted small mb-0">You can unarchive it later from this same control.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="tb-archive-confirm" class="btn btn-warning">Yes, archive</button>
      </div>
    </div>
  </div>
</div>