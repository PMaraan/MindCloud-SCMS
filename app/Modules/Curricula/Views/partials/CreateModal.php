<?php if (!empty($canCreate)): ?>
<div class="modal fade" id="CreateModal" tabindex="-1" aria-hidden="true" aria-labelledby="CreateLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= BASE_PATH ?>/dashboard?page=curricula&action=create" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="CreateLabel">Create Curriculum</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="mb-3">
          <label class="form-label">Curriculum Code <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="curriculum_code" maxlength="50" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="title" maxlength="150" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Effective Start <span class="text-danger">*</span></label>
          <input type="date" class="form-control" name="effective_start" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Effective End</label>
          <input type="date" class="form-control" name="effective_end">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Create</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
