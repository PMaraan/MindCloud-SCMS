<?php // /app/Modules/Accounts/Views/partials/create_modal.php ?>
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true" aria-labelledby="createUserLabel">
  <div class="modal-dialog">
    <form method="post" action="/accounts/create" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createUserLabel">Create User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="mb-3">
          <label class="form-label">ID No.</label>
          <input name="id_no" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">First Name</label>
          <input name="fname" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Last Name</label>
          <input name="lname" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create</button>
      </div>
    </form>
  </div>
</div>
