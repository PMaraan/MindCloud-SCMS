<?php
// root/app/views/pages/accounts/EditUserModal.php
?>
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_PATH ?>/api/accounts/edit">
        <div class="modal-header">
          <h5 class="modal-title">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit-id-no" name="id_no">
          <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" id="edit-fname" name="fname" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Middle Name</label>
            <input type="text" id="edit-mname" name="mname" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" id="edit-lname" name="lname" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" id="edit-email" name="email" class="form-control">
          </div>
          <!-- Add role, college, etc. -->
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
