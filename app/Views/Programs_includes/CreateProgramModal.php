    <?php
      //root/app/views/Programs_includes/CreateProgramModal.php
    ?>
    
    <!-- Button trigger for create user modal -->
     <div class="container mt-3 mb-3 px-0 d-flex justify-content-end">
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProgramModal">
    Create Program
  </button>
</div>


    <!-------------------Create Program Modal------------------------------>
    <div class="modal" id="createProgramModal" tabindex="-1"><!--createProgramModal open-->
        <div class="modal-dialog"><!--modal-dialog open-->
            <div class="modal-content"><!--modal-content open-->
                <div class="modal-header"><!--modal-header open-->
                    <h5 class="modal-title">Create Program</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div><!--modal-header close-->
                <form id="createProgramForm"action="/MindCloud-SCMS/public/api.php" method="POST">
                <div class="modal-body"><!--modal-body open-->
                    <h6>Please fill in the following details:</h6>
                    
                    <!-- Input for Program ID -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createProgramId" class="form-label required">Program ID</label>
                        <input type="text" id="createProgramId" class="form-control" name="program_id" required>
                    </div><!-- mb-2 close -->
                    <!-- Input for Program Name -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createProgramName" class="form-label required">Program Name</label>
                        <input type="text" id="createProgramName" class="form-control" name="program_name" required>
                    </div><!-- mb-2 close -->
                    <!-- Input for College ID -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createCollegeId" class="form-label required">Program Name</label>
                        <input type="text" id="createCollegeId" class="form-control" name="college_id" required>
                    </div><!-- mb-2 close -->
                    <!-- Input for Program Chair -->
                    <div class="mb-2"><!-- mb-2 open -->
                        <label for="createProgramChair" class="form-label">Program Chair</label>
                        <select id="createProgramChair" class="form-select" name="program_chair">
                            <!-- College options go here -->
                            <option value="">NULL</option>
                            <?php
                                $chairs = $db->getAllChairs(); // in the future, use datacontroller for safer handling
                                foreach ($chairs as $chair):
                                    $fullName = $chair['mname'] ? 
                                        $chair['fname'] . " " . trim($chair['mname']) . " " . $chair['lname'] : 
                                        $chair['fname'] . " " . $chair['lname'];
                            ?>
                            <option value="<?= htmlspecialchars($chair['id_no']) ?>"><?= htmlspecialchars($fullName) ?></option>
                            <?php
                                endforeach;
                            ?>
                        </select>
                    </div><!-- mb-2 close -->


                </div><!--modal-body close-->
                <div class="modal-footer"><!-- modal-footer open -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="createCollegeBtn" type="submit" class="btn btn-primary create-program-btn" name="action" value="createCollege">Create College</button>
                </div><!-- modal-footer close -->
                </form>
            </div><!--modal-content close-->
        </div><!--modal-dialog close-->
    </div><!--createProgramModal close-->