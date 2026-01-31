<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <form id="deleteUserForm" action="/MindCloud-SCMS/public/api.php" method="POST">

        <div class="modal-header">
          <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- store id number for deletion -->
          <input type="hidden" id="deleteIdNumber" name="id_no">
          <input type="hidden" id="deleteRoleId" name="role_id">
          <h6>Caution!</h6>
          <p id="deleteModalMessage">Are you sure you want to delete user?</p>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary delete-btn" id="deleteUserBtn" name="action" value="deleteAccount" data-action="deleteAccount">
            Yes
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            No
          </button>
        </div>

      </form>

    </div>
  </div>
</div>
