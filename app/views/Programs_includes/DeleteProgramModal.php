<!-- Delete Program Modal -->
<div class="modal fade" id="deleteProgramModal" tabindex="-1" aria-labelledby="deleteProgramModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <form id="deleteProgramForm" action="/MindCloud-SCMS/public/api.php" method="POST">

        <div class="modal-header">
          <h5 class="modal-title" id="deleteProgramModalLabel">Delete Program</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- store program for deletion -->
          <input type="hidden" id="deleteProgramId" name="program_id">
          <h6>Caution!</h6>
          <p id="deleteModalMessage">Are you sure you want to delete program?</p>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary delete-btn" id="deleteProgramBtn" name="action" value="deleteProgram" data-action="deleteProgram">
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
