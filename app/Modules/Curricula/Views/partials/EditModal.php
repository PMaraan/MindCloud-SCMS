<div class="modal fade" id="EditModal" tabindex="-1" aria-hidden="true" aria-labelledby="EditLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= BASE_PATH ?>/dashboard?page=curricula&action=edit" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="EditLabel">Edit Curriculum</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="id" id="edit-id">

        <div class="mb-3">
          <label class="form-label">Curriculum Code <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="curriculum_code" id="edit-curriculum_code" maxlength="50" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="title" id="edit-title" maxlength="150" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Effective Start <span class="text-danger">*</span></label>
          <input type="date" class="form-control" name="effective_start" id="edit-start" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Effective End</label>
          <input type="date" class="form-control" name="effective_end" id="edit-end">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Save changes</button>
      </div>
    </form>
  </div>
</div>
