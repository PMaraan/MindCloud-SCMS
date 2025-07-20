<!-- Edit User Modal -->
 <?php
    require_once __DIR__ . '/../../controllers/DataController.php';
    $db = new DataController();
 ?>
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="editRoleForm" action="/MindCloud-SCMS/public/api.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="editRoleModalLabel">Edit Role</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- don't make this hidden if id number is editable -->
          <input type="hidden" id="editRoleId" name="role_id">
          
          <div class="mb-3">
            <label for="editRoleName" class="form-label">Role Name</label>
            <input type="text" class="form-control" id="editRoleName" name="role_name">
          </div>
          <div class="mb-3">
            <label for="editRoleLevel" class="form-label required">Role Level</label>
            <input type="text" class="form-control" id="editRoleLevel" name="role_level" required>
          </div>
          
          
          
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary save-btn" id="setAccountChangesUsingID" name="action" value="setAccountChangesUsingID" data-action="setAccountChangesUsingID">
            Save changes
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
