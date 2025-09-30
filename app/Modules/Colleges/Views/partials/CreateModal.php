<?php
/** File: /app/Modules/Colleges/Views/partials/CreateModal.php
 * 
 * Colleges — Create Modal
 * 
 *  Purpose:
 *   Create new College entry; CSRF inside .modal-body.
 *
 * Expects (from controller):
 *   - $deans : array<int, array{id_no:string,fname?:string,mname?:string,lname?:string}>
 *
 * CSRF:
 *   - Uses \App\Helpers\CsrfHelper::inputField() which emits name="csrf_token"
 *     and value from $_SESSION['csrf_token'].
 *
 * Notes:
 *   - All form fields (including CSRF) are standardized to appear inside the modal body.
 *   - Keep autocomplete="off" to avoid stale values in modals.
 */
?>
<div class="modal fade" id="createCollegesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_PATH ?>/dashboard?page=colleges&action=create" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title">Create College</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body row g-3">
          <!-- CSRF Protection (standardized location: inside modal-body) -->
          <?= \App\Helpers\CsrfHelper::inputField() ?>

          <div class="col-12">
            <label class="form-label">Short Name <span class="text-danger">*</span></label>
            <input class="form-control" name="short_name" maxlength="10" required placeholder="e.g., CCS">
          </div>

          <div class="col-12">
            <label class="form-label">College Name <span class="text-danger">*</span></label>
            <input class="form-control" name="college_name" maxlength="100" required>
          </div>

          <div class="col-12">
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
          <button class="btn btn-primary" type="submit">Create</button>
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
