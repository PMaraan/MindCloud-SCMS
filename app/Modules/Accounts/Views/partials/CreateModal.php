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
          <label for="create-id-no" class="form-label required">ID No.</label>
          <input id="create-id-no" name="id_no" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="create-fname" class="form-label required">First Name</label>
          <input id="create-fname" name="fname" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="create-mname" class="form-label">Middle Name (optional)</label>
          <input id="create-mname" name="mname" class="form-control">
        </div>

        <div class="mb-3">
          <label for="create-lname" class="form-label required">Last Name</label>
          <input id="create-lname" name="lname" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="create-email" class="form-label required">Email</label>
          <input type="email" id="create-email" name="email" class="form-control" required>
        </div>
        <!-- Role Selection -->
        <div class="mb-3">
          <label for="create-role" class="form-label required">Role</label>
          <select id="create-role" name="role_id" class="form-select" required>
            <option value="" selected disabled>— Select —</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['role_id'] ?>">
                <?= htmlspecialchars((string)$r['role_name'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- College Selection -->
        <div class="mb-3">
          <label for="create-college" class="form-label<?= empty($isAAO) ? ' required' : '' ?>">
            College<?= !empty($isAAO) ? ' (optional)' : '' ?>
          </label>
          <select id="create-college" name="department_id" class="form-select" <?= empty($isAAO) ? 'required' : '' ?>>
            <option value="">— None —</option>
            <?php foreach ($colleges as $c): ?>
              <option value="<?= (int)$c['department_id'] ?>">
                <?= htmlspecialchars((string)($c['short_name'] ?: $c['college_name']), ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Status Selection -->
        <div class="mb-3">
          <label for="create-status" class="form-label required">Status</label>
          <select id="create-status" name="status" class="form-select" required>
            <option value="active" selected>Active</option>
            <option value="password_reset_required">Reset Required</option>
            <option value="archived">Archived</option>
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
