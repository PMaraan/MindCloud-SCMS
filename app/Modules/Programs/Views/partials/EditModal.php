<?php // /app/Modules/Programs/Views/partials/EditModal.php
/** @var array $colleges */ ?>
<div class="modal fade" id="editProgramModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= BASE_PATH ?>/dashboard?page=programs&action=edit">
      <input type="hidden" name="program_id" id="progEditId">
      <div class="modal-header">
        <h5 class="modal-title">Edit Program</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div class="mb-3">
          <label class="form-label">Program name</label>
          <input type="text" name="program_name" id="progEditName" class="form-control" maxlength="255" required>
        </div>

        <div class="mb-3">
          <label class="form-label">College</label>
          <select name="department_id" id="progEditCollege" class="form-select" required>
            <option value="">— Select —</option>
            <?php foreach ($colleges as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Program Chair (optional)</label>
          <select name="chair_id_no" id="progEditChair" class="form-select">
            <option value="">— None —</option>
            <?php foreach (($chairs ?? []) as $u): ?>
              <option value="<?= htmlspecialchars((string)$u['id_no'], ENT_QUOTES) ?>">
                <?= htmlspecialchars((string)$u['id_no'], ENT_QUOTES) ?> — 
                <?= htmlspecialchars((string)$u['lname'] . ', ' . $u['fname'] . (empty($u['mname']) ? '' : ' ' . $u['mname']), ENT_QUOTES) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>
