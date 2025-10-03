<?php
/** File: /app/Modules/Departments/Views/partials/DeleteModal.php
 *
 * Departments — Delete Modal
 *
 * Purpose:
 *   Delete an existing Department entry.
 *
 * Expects the opener button to provide (via data-*):
 *   - data-id            (department_id)
 *   - data-short_name    (department short name)
 *   - data-department_name  (department full name)
 *
 * JS populates:
 *   - [name="id"] (hidden)
 *   - #delete-department-id-display.js-del-id
 *   - #delete-department-short.js-del-short
 *   - #delete-department-name.js-del-name
 *
 * CSRF:
 *   - Uses \App\Helpers\CsrfHelper::inputField() which emits name="csrf_token".
 */
?>
<div class="modal fade" id="deleteDepartmentsModal" tabindex="-1" aria-hidden="true" aria-labelledby="deleteDepartmentLabel">
  <div class="modal-dialog">
    <form method="POST" action="<?= BASE_PATH ?>/dashboard?page=departments&action=delete" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteDepartmentLabel">Delete Department</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <!-- CSRF Protection (standardized location: inside modal-body) -->
        <?= \App\Helpers\CsrfHelper::inputField() ?>

        <input type="hidden" name="id" id="delete-department-id" value="">

        <p class="mb-0">
          Are you sure you want to delete department
          <strong id="delete-department-short" class="js-del-short">this department</strong>
          — <strong id="delete-department-name" class="js-del-name"></strong>
          (ID <strong id="delete-department-id-display" class="js-del-id"></strong>)?
          This action cannot be undone.
        </p>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>
