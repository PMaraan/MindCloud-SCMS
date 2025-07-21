<!-- Edit User Modal -->
 <?php
    require_once __DIR__ . '/../../controllers/DataController.php';
    $db = new DataController();
 ?>
<div class="modal fade" id="editCollegeModal" tabindex="-1" aria-labelledby="editCollegeModalLabel" aria-hidden="true"><!-- edit college modal open -->
  <div class="modal-dialog modal-dialog-centered"><!-- modal-dialog open -->
    <div class="modal-content"><!-- modal-content open -->
      <form id="editCollegeForm" action="/MindCloud-SCMS/public/api.php" method="POST"><!-- form open -->
        <div class="modal-header"><!-- modal-header open -->
          <h5 class="modal-title" id="editRoleModalLabel">Edit Role</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div><!-- modal-header close -->

        <div class="modal-body"><!-- modal-body open -->
          <!-- fields to submit to the database -->          
          <!-- don't make this hidden if id number is editable -->
          <input type="hidden" id="editCollegeId" name="college_id" required>
          
          <div class="mb-3">
            <label for="editCollegeShortName" class="form-label required">College Short Name</label>
            <input type="text" class="form-control" id="editCollegeShortName" name="college_short_name" required>
          </div>

          <div class="mb-3">
            <label for="editCollegeName" class="form-label required">College Name</label>
            <input type="text" class="form-control" id="editCollegeName" name="college_name" required>
          </div>
          
          <div class="mb-3">
            <label for="editDeanName" class="form-label required">Dean</label>
            <select id="editDeanName" class="form-select" name="college_dean" required>
                <!-- College options go here -->
                <option value="">NULL</option>
                <?php
                    $deans = $db->getAllDeans(); // in the future, use datacontroller for safer handling
                    echo "Deans: " . print_r($deans);
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
          </div>
          
          
        </div><!-- modal-body close -->

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary edit-college-btn" id="setCollegeInfo" name="action" value="setCollegeInfo" data-action="setCollegeInfo">
            Save changes
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form><!-- form close -->
    </div><!-- modal-content close -->
  </div><!-- modal-dialog close -->
</div><!-- edit college modal close -->
