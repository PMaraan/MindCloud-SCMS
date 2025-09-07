<div class="modal fade" id="DeleteModal" tabindex="-1" aria-hidden="true" aria-labelledby="DeleteLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= BASE_PATH ?>/dashboard?page=curricula&action=delete" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="DeleteLabel">Delete Curriculum</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="id" id="delete-id">

        <p class="mb-1">You are about to delete:</p>
        <div class="alert alert-warning">
          <div><strong>ID:</strong> <span id="delete-id-display">—</span></div>
          <div><strong>Code:</strong> <span id="delete-code-display">—</span></div>
          <div><strong>Title:</strong> <span id="delete-title-display">—</span></div>
        </div>
        <p class="mb-0">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger" type="submit">Delete</button>
      </div>
    </form>
  </div>
</div>
