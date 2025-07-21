    <?php
      //root/app/views/Roles_includes/CreateRoleModal.php
    ?>
    
    <!-- Button trigger for create user modal -->
     <div class="container container-fluid">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCollegeModal">
            Create College
        </button>
     </div>

    <!-------------------Create College Modal------------------------------>
    <div class="modal" id="createCollegeModal" tabindex="-1"><!--createRoleModal open-->
        <div class="modal-dialog"><!--modal-dialog open-->
            <div class="modal-content"><!--modal-content open-->
                <div class="modal-header"><!--modal-header open-->
                    <h5 class="modal-title">Create College</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div><!--modal-header close-->
                <form id="createCollegeForm"action="/MindCloud-SCMS/public/api.php" method="POST">
                <div class="modal-body"><!--modal-body open-->
                    <h6>Please fill in the following details:</h6>
                    
                    <!-- Input for College Short Name -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createCollegeShortName" class="form-label required">College Short Name</label>
                        <input type="text" id="createCollegeShortName" class="form-control" name="college_short_name" required>
                    </div><!-- mb-2 close -->
                    <!-- Input for College Name -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createCollegeName" class="form-label required">College Name</label>
                        <input type="text" id="createCollegeName" class="form-control" name="college_name" required>
                    </div><!-- mb-2 close -->
                    <!-- Input for College Dean -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createCollegeDean" class="form-label">College Dean</label>
                        <select id="createCollegeDean" class="form-select" name="college_dean">
                            <!-- College options go here -->
                            <option value="">NULL</option>
                            <?php
                                $deans = $db->getAllDeans(); // in the future, use datacontroller for safer handling
                                foreach ($deans as $dean):
                                    $fullName = $dean['mname'] ? 
                                        $dean['fname'] . " " . trim($dean['mname']) . " " . $dean['lname'] : 
                                        $dean['fname'] . " " . $dean['lname'];
                            ?>
                            <option value="<?= htmlspecialchars($dean['id_no']) ?>"><?= htmlspecialchars($fullName) ?></option>
                            <?php
                                endforeach;
                            ?>
                        </select>
                    </div><!-- mb-2 close -->


                </div><!--modal-body close-->
                <div class="modal-footer"><!-- modal-footer open -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="createCollegeBtn" type="submit" class="btn btn-primary create-role-btn" name="action" value="createCollege">Create College</button>
                </div><!-- modal-footer close -->
                </form>
            </div><!--modal-content close-->
        </div><!--modal-dialog close-->
    </div><!--createRoleModal close-->