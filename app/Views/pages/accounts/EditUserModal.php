<?php
// /app/Views/pages/accounts/EditUserModal.php
// expects: $csrf, $roles, $colleges
?>
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_PATH ?>/dashboard?page=accounts&action=edit" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <div class="modal-header">
          <h5 class="modal-title">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit-id-no" name="id_no" required>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">First Name</label>
              <input type="text" id="edit-fname" name="fname" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Middle Name</label>
              <input type="text" id="edit-mname" name="mname" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Last Name</label>
              <input type="text" id="edit-lname" name="lname" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" id="edit-email" name="email" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Role</label>
              <select id="edit-role" name="role_id" class="form-select" required>
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
              <select id="edit-college" name="college_id" class="form-select">
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
          <button type="submit" class="btn btn-primary">Save changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
