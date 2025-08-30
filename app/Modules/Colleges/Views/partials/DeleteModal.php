<?php /* app/Modules/Colleges/Views/partials/DeleteModal.php */ ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_PATH ?>/dashboard?page=colleges&action=delete">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
        <input type="hidden" name="id">

        <div class="modal-header">
          <h5 class="modal-title">Delete College</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <p class="mb-3">Are you sure you want to delete this record?</p>

          <ul class="list-group mb-2">
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span>ID</span>
              <strong class="js-del-id"></strong>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span>Short Name</span>
              <strong class="js-del-short"></strong>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span>College Name</span>
              <strong class="js-del-name"></strong>
            </li>
          </ul>

          <div class="alert alert-warning mb-0" role="alert">
            This action cannot be undone.
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-danger" type="submit">Delete</button>
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
