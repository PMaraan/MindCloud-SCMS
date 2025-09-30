<?php
/** File: /app/Modules/Departments/Views/partials/EditModal.php
 *
 * Departments — Edit Modal
 *
 * Purpose:
 *   Edit an existing Department entry.
 *
 * Expects (from controller):
 *   - $deans : array<int, array{id_no:string,fname?:string,mname?:string,lname?:string}>
 *
 * JS populates:
 *   - [name="id"] (hidden)
 *   - [name="short_name"], [name="department_name"], [name="dean_id_no"]
 *
 * CSRF:
 *   - Uses \App\Helpers\CsrfHelper::inputField() which emits name="csrf_token".
 *
 * Notes:
 *   - All form fields (including hidden id & CSRF) are standardized to appear inside the modal body.
 */
?>
<div class="modal fade" id="editDepartmentsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_PATH ?>/dashboard?page=departments&action=edit" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title">Edit Department</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body row g-3">
          <!-- CSRF Protection (standardized location: inside modal-body) -->
          <?= \App\Helpers\CsrfHelper::inputField() ?>

          <!-- Hidden primary key (standardized location: inside modal-body) -->
          <input type="hidden" name="id">

          <div class="col-12">
            <label class="form-label">Short Name <span class="text-danger">*</span></label>
            <input class="form-control" name="short_name" maxlength="10" required>
          </div>

          <div class="col-12">
            <label class="form-label">Department Name <span class="text-danger">*</span></label>
            <input class="form-control" name="department_name" maxlength="100" required>
          </div>

          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="edit-is-college" name="is_college" value="1">
              <label class="form-check-label" for="edit-is-college">
                This department is a <strong>College</strong>
              </label>
            </div>
            <div class="form-text">
              Only <em>colleges</em> can have an assigned Dean.
            </div>
          </div>

          <div class="col-12" id="edit-dean-group">
            <label class="form-label">Dean (optional)</label>
            <select class="form-select" name="dean_id_no">
              <option value="">— None —</option>
              <?php foreach (($deans ?? []) as $u):
                $fn = trim(($u['fname'] ?? '') . ' ' . ($u['mname'] ?? '') . ' ' . ($u['lname'] ?? ''));
              ?>
                <option value="<?= htmlspecialchars((string)$u['id_no'], ENT_QUOTES) ?>">
                  <?= htmlspecialchars((string)$u['id_no'], ENT_QUOTES) ?> — <?= htmlspecialchars($fn, ENT_QUOTES) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">Save changes</button>
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
