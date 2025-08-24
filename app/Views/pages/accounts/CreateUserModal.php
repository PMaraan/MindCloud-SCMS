<?php
// root/app/views/pages/accounts/CreateUserModal.php
// expects: $roles, $colleges, $csrf
?>
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_PATH ?>/dashboard?page=accounts&action=create" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="createUserModalLabel">Create User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">ID No</label>
              <input type="text" name="id_no" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">First name</label>
              <input type="text" name="fname" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Middle name</label>
              <input type="text" name="mname" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Last name</label>
              <input type="text" name="lname" class="form-control" required>
            </div>
            <div class="col-md-8">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" minlength="6" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Role</label>
              <select name="role_id" class="form-select" required>
                <option value="">— Select —</option>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= (int)$r['role_id'] ?>">
                    <?= htmlspecialchars((string)$r['role_name'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">College (optional)</label>
              <select name="college_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($colleges as $c): ?>
                  <option value="<?= (int)$c['college_id'] ?>">
                    <?= htmlspecialchars((string)($c['short_name'] ?: $c['college_name']), ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Create</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
