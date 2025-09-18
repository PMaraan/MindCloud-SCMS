<?php // /app/Modules/Programs/Views/partials/CreateModal.php
/** @var array $colleges */ ?>
<div class="modal fade" id="createProgramModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= BASE_PATH ?>/dashboard?page=programs&action=create">
      <div class="modal-header">
        <h5 class="modal-title">Create Program</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Program name</label>
          <input type="text" name="program_name" class="form-control" maxlength="255" required>
        </div>
        <div class="mb-3">
          <label class="form-label">College</label>
          <select name="college_id" class="form-select" required>
            <option value="">— Select —</option>
            <?php foreach ($colleges as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create</button>
      </div>
    </form>
  </div>
</div>
