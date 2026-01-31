<!-- Delete College Modal -->
<div class="modal fade" id="deleteCollegeModal" tabindex="-1" aria-labelledby="deleteCollegeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <form id="deleteCollegeForm" action="/MindCloud-SCMS/public/api.php" method="POST">

        <div class="modal-header">
          <h5 class="modal-title" id="deleteCollegeModalLabel">Delete College</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- store college for deletion -->
          <input type="hidden" id="deleteCollegeId" name="college_id">
          <h6>Caution!</h6>
          <p id="deleteModalMessage">Are you sure you want to delete college?</p>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary delete-btn" id="deleteCollegeBtn" name="action" value="deleteCollege" data-action="deleteCollege">
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
