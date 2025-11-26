<?php
// /app/Modules/Syllabi/Views/partials/ArchiveModal.php
/**
 * Archive / Unarchive confirmation modal (UI-only). JS will set the title and handle confirm.
 */
?>
<div class="modal fade" id="syArchiveModal" tabindex="-1" aria-labelledby="syArchiveLabel" aria-hidden="true" data-no-reset>
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="syArchiveLabel">Archive Syllabus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="sy-archive-body">Are you sure you want to archive this syllabus?</p>
        <p class="fw-semibold" id="sy-archive-title">—</p>
        <p class="text-muted small mb-0">You can unarchive it later from this same control.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" id="sy-archive-confirm">Yes, archive</button>
      </div>
    </div>
  </div>
</div>