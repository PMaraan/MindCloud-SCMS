    <?php
      //root/app/views/Roles_includes/CreateRoleModal.php
    ?>
    
    <!-- Button trigger for create user modal -->
    <div class="container mt-3 mb-3 px-0 d-flex justify-content-end">
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
    Create Role
  </button>
</div>


    <!-------------------Create Role Modal------------------------------>
    <div class="modal" id="createRoleModal" tabindex="-1"><!--createRoleModal open-->
        <div class="modal-dialog"><!--modal-dialog open-->
            <div class="modal-content"><!--modal-content open-->
                <div class="modal-header"><!--modal-header open-->
                    <h5 class="modal-title">Create Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div><!--modal-header close-->
                <form id="createRoleForm"action="/MindCloud-SCMS/public/api.php" method="POST">
                <div class="modal-body"><!--modal-body open-->
                    <h6>Please fill in the following details:</h6>
                    
                    <!-- Input for Role Name -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createRoleName" class="form-label required">Role Name</label>
                        <input type="text" id="createRoleName" class="form-control" name="role_name" required>
                    </div><!-- mb-2 close -->
                    <!-- Input for Level -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createRoleLevel" class="form-label required">Role Level</label>
                        <input type="text" id="createRoleLevel" class="form-control" name="role_level" required>
                    </div><!-- mb-2 close -->                    
                    
                </div><!--modal-body close-->
                <div class="modal-footer"><!-- modal-footer open -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="createRoleBtn" type="submit" class="btn btn-primary create-role-btn" name="action" value="createRole">Create Role</button>
                </div><!-- modal-footer close -->
                </form>
            </div><!--modal-content close-->
        </div><!--modal-dialog close-->
    </div><!--createRoleModal close-->