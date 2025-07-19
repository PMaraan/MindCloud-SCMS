    <?php
      //root/app/views/Accounts_includes/CreateUserModal.php
    ?>
    
    <!-- Button trigger for create user modal -->
     <div class="container container-fluid">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            Create Account
        </button>
     </div>

    <!-------------------Create User Modal------------------------------>
    <div class="modal" id="createUserModal" tabindex="-1"><!--createUserModal open-->
        <div class="modal-dialog"><!--modal-dialog open-->
            <div class="modal-content"><!--modal-content open-->
                <div class="modal-header"><!--modal-header open-->
                    <h5 class="modal-title">Create Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div><!--modal-header close-->
                <div class="modal-body"><!--modal-body open-->
                    <h6>Please fill in the following details:</h6>
                    <!-- Input for ID Number -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label class="form-label required">ID Number</label>
                        <input type="text" id="editIdNumber" class="form-control" required>
                    </div><!-- mb-2 close -->
                    <!-- Input for First Name -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label class="form-label required">First Name</label>
                        <input type="text" id="editFirstName" class="form-control">
                    </div><!-- mb-2 close -->
                    <!-- Input for Middle Initial -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label class="form-label">Middle Initial</label>
                        <input type="text" id="editMiddleInitial" class="form-control">
                    </div><!-- mb-2 close -->
                    <!-- Input for Last Name -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label class="form-label required">Last Name</label>
                        <input type="text" id="editLastName" class="form-control">
                    </div><!-- mb-2 close -->
                    <!-- Input for Email -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label class="form-label required">Email</label>
                        <input type="email" id="editEmail" class="form-control">
                    </div><!-- mb-2 close -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label class="form-label">College</label>
                        <select id="editRole" class="form-select">
                            <option>CCS</option>
                            <option>CEA</option>
                            <option>CFAD</option>
                        </select>
                    </div><!-- mb-2 close -->
                    <!-- Dropdown for selecting Role -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label class="form-label">Role</label>
                        <select id="editRole" class="form-select">
                            <option>Professor</option>
                            <option>Chair</option>
                            <option>College Secretary</option>
                            <option>Dean</option>
                            <option>Secretary</option>
                            <option>Admin</option>
                            <option>Superadmin</option>
                        </select>
                    </div><!-- mb-2 close -->
                </div><!--modal-body close-->
                <div class="modal-footer"><!-- modal-footer open -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Create Account</button>
                </div><!-- modal-footer close -->
            </div><!--modal-content close-->
        </div><!--modal-dialog close-->
    </div><!--createUserModal close-->