<?php
/** File: /app/Modules/Departments/Views/partials/CreateModal.php
 *
 * Departments — Create Modal
 *
 * Expects:
 *   - $deans : array<int, array{id_no:string,fname?:string,mname?:string,lname?:string}>
 */
?>
<div class="modal fade" id="createDepartmentsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_PATH ?>/dashboard?page=departments&action=create" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title">Create Department</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body row g-3">
          <?= \App\Helpers\CsrfHelper::inputField() ?>

          <div class="col-12">
            <label class="form-label">Short Name <span class="text-danger">*</span></label>
            <input class="form-control" name="short_name" maxlength="10" required>
          </div>

          <div class="col-12">
            <label class="form-label">Department Name <span class="text-danger">*</span></label>
            <input class="form-control" name="department_name" maxlength="100" required>
          </div>

          <!-- NEW: Is College -->
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="create-is-college" name="is_college" value="1"
                     aria-controls="create-dean-group" aria-expanded="false">
              <label class="form-check-label" for="create-is-college">
                This department is a <strong>College</strong>
              </label>
            </div>
            <div class="form-text">Only colleges can have an assigned Dean.</div>
          </div>

          <!-- NEW: Dean group (hidden & disabled until is_college is checked) -->
          <div class="col-12" id="create-dean-group" hidden>
            <label class="form-label">Dean (optional)</label>
            <select class="form-select" name="dean_id_no" disabled>
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
          <button class="btn btn-primary" type="submit">Create</button>
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
