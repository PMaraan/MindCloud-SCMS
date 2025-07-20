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
                <form id="createUserForm"action="/MindCloud-SCMS/public/api.php" method="POST">
                <div class="modal-body"><!--modal-body open-->
                    <h6>Please fill in the following details:</h6>
                    
                    <!-- Input for ID Number -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createIdNumber" class="form-label required">ID Number</label>
                        <input type="text" id="createIdNumber" class="form-control" name="id_no" required>
                    </div><!-- mb-2 close -->
                    <!-- Input for First Name -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createFirstName" class="form-label required">First Name</label>
                        <input type="text" id="createFirstName" class="form-control" name="fname" required>
                    </div><!-- mb-2 close -->
                    <!-- Input for Middle Initial -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createMiddleInitial" class="form-label">Middle Initial</label>
                        <input type="text" id="createMiddleInitial" class="form-control" name="mname">
                    </div><!-- mb-2 close -->
                    <!-- Input for Last Name -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createLastName" class="form-label required">Last Name</label>
                        <input type="text" id="createLastName" class="form-control" name="lname" required>
                    </div><!-- mb-2 close -->
                    <!-- Input for Email -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createEmail" class="form-label required">Email</label>
                        <input type="email" id="createEmail" class="form-control" name="email" required>
                    </div><!-- mb-2 close -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createCollege" class="form-label">College</label>
                        <select id="createCollege" class="form-select" name="college_short_name">
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
                    </div><!-- mb-2 close -->
                    <!-- Dropdown for selecting Role -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createRole" class="form-label required">Role</label>
                        <select id="createRole" class="form-select" name="role_name" required>
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
                    </div><!-- mb-2 close -->
                    
                </div><!--modal-body close-->
                <div class="modal-footer"><!-- modal-footer open -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="createAccountBtn" type="submit" class="btn btn-primary create-btn" name="action" value="createUser">Create Account</button>
                </div><!-- modal-footer close -->
                </form>
            </div><!--modal-content close-->
        </div><!--modal-dialog close-->
    </div><!--createUserModal close-->