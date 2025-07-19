<!-- Edit User Modal -->
 <?php
    require_once __DIR__ . '/../../controllers/DataController.php';
    $db = new DataController();
 ?>
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="editUserForm" action="update_user.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- don't make this hidden if id number is editable -->
          <input type="hidden" id="editIdNumber" name="id_no">

          <div class="mb-3">
            <label for="editFirstName" class="form-label required">First Name</label>
            <input type="text" class="form-control" id="editFirstName" name="fname" required>
          </div>
          <div class="mb-3">
            <label for="editMiddleInitial" class="form-label">Middle Name</label>
            <input type="text" class="form-control" id="editMiddleInitial" name="mname">
          </div>
          <div class="mb-3">
            <label for="editLastName" class="form-label required">Last Name</label>
            <input type="text" class="form-control" id="editLastName" name="lname" required>
          </div>
          <div class="mb-3">
            <label for="editEmail" class="form-label required">Email</label>
            <input type="email" class="form-control" id="editEmail" name="email" required>
          </div>
          <div class="mb-3">
            <label for="editCollege" class="form-label required">College</label>
            <select class="form-select" id="editCollege" name="college_id" required>
                <!-- College options go here -->
                <option value="">NULL</option>
                <?php
                    $colleges = $db->getAllCollegeShortNames();
                    foreach ($colleges as $college):
                ?>
                <option value="<?= htmlspecialchars($college['short_name']) ?>"><?= htmlspecialchars($college['short_name']) ?></option>
                <?php
                    endforeach;
                ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="editRole" class="form-label required">Role</label>
            <select class="form-select" id="editRole" name="role_id" required>
                <option value="">NULL</option>
                <!-- Role options go here -->
                 <?php 
                    $roles = $db->getAllRoleNames();
                    foreach($roles as $role):
                        $role_name = $role['role_name'];
                ?>
                     <option value="<?= htmlspecialchars($role_name) ?>"><?= htmlspecialchars($role_name) ?></option>
                <?php
                    endforeach;
                 ?>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
