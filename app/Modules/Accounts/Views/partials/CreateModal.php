<?php // /app/Modules/Accounts/Views/partials/CreateModal.php ?>
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true" aria-labelledby="createUserLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= BASE_PATH ?>/dashboard?page=accounts&action=create" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createUserLabel">Create User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <!-- Match controller expectation -->
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <div class="mb-3">
          <label class="form-label">ID No.</label>
          <input name="id_no" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">First Name</label>
          <input name="fname" class="form-control" required>
        </div>

        <!-- NEW: Middle Name (optional) -->
        <div class="mb-3">
          <label class="form-label">Middle Name (optional)</label>
          <input name="mname" class="form-control">
        </div>

        <div class="mb-3">
          <label class="form-label">Last Name</label>
          <input name="lname" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Role</label>
          <select name="role_id" class="form-select" required>
            <option value="" selected disabled>— Select —</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['role_id'] ?>">
                <?= htmlspecialchars((string)$r['role_name'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">College (optional)</label>
          <select name="department_id" class="form-select">
            <option value="">— None —</option>
            <?php foreach ($colleges as $c): ?>
              <option value="<?= (int)$c['department_id'] ?>">
                <?= htmlspecialchars((string)($c['short_name'] ?: $c['college_name']), ENT_QUOTES, 'UTF-8') ?>
              </option>
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
