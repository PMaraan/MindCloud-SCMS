<?php
/** File: /app/Modules/Colleges/Views/partials/DeleteModal.php
 * 
 * Colleges — Delete Modal
 *
 * Purpose:
 *   Delete an existing College entry.
 *
 * Expects the opener button to provide (via data-*):
 *   - data-id            (college_id)
 *   - data-short_name    (college short name)
 *   - data-college_name  (college full name)
 *
 * JS populates:
 *   - [name="id"] (hidden)
 *   - #delete-college-id-display.js-del-id
 *   - #delete-college-short.js-del-short
 *   - #delete-college-name.js-del-name
 *
 * CSRF:
 *   - Uses \App\Helpers\CsrfHelper::inputField() which emits name="csrf_token".
 */
?>
<div class="modal fade" id="deleteCollegesModal" tabindex="-1" aria-hidden="true" aria-labelledby="deleteCollegeLabel">
  <div class="modal-dialog">
    <form method="POST" action="<?= BASE_PATH ?>/dashboard?page=colleges&action=delete" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteCollegeLabel">Delete College</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <!-- CSRF Protection (standardized location: inside modal-body) -->
        <?= \App\Helpers\CsrfHelper::inputField() ?>

        <input type="hidden" name="id" id="delete-college-id" value="">

        <p class="mb-0">
          Are you sure you want to delete college
          <strong id="delete-college-short" class="js-del-short">this college</strong>
          — <strong id="delete-college-name" class="js-del-name"></strong>
          (ID <strong id="delete-college-id-display" class="js-del-id"></strong>)?
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
