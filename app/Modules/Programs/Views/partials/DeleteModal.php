<?php // /app/Modules/Programs/Views/partials/DeleteModal.php ?>
<div class="modal fade" id="deleteProgramModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= BASE_PATH ?>/dashboard?page=programs&action=delete">
      <input type="hidden" name="program_id" id="progDelId">
      <div class="modal-header">
        <h5 class="modal-title text-danger">Delete Program</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>You're about to delete <strong id="progDelName">this program</strong>. This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>
