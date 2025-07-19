    <?php
        //root/app/views/Accounts_includes/EditUserModal.php
    ?>

    <!-------------------Edit User Modal---------------------->
    <div class="modal" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Please fill in the following details</h6>
                    <!-- Input for ID Number  -->
                    <div class="mb-2">
                        <label class="form-label required">ID Number</label>
                        <input type="text" id="editIdNumber" class="form-control" required/>
                    </div>
                    <!-- Input for First Name -->
                    <div class="mb-2">
                        <label class="form-label required">First Name</label>
                        <input type="text" id="editFirstName" class="form-control">
                    </div>
                    <!-- Input for Middle Initial -->
                    <div class="mb-2">
                        <label class="form-label">Middle Initial</label>
                        <input type="text" id="editMiddleInitial" class="form-control">
                    </div>
                    <!-- Input for Last Name -->
                    <div class="mb-2">
                        <label class="form-label required">Last Name</label>
                        <input type="text" id="editLastName" class="form-control">
                    </div>              
                    <!-- Input for Email -->
                    <div class="mb-2">
                        <label class="form-label required">Email</label>
                        <input type="email" id="editEmail" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">College</label>
                        <select id="editCollege" class="form-select">
                        <option>CCS</option>
                        <option>CEA</option>
                        <option>CFAD</option>
                        </select>
                    </div>
                    <!-- Dropdown for selecting Role -->
                    <div class="mb-2">
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
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>