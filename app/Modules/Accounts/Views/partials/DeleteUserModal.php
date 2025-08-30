<?php // /app/Modules/Accounts/Views/partials/delete_modal.php ?>
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true" aria-labelledby="deleteUserLabel">
  <div class="modal-dialog">
    <form method="post" action="/accounts/delete" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteUserLabel">Delete User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="id" id="delete-id" value="">
        <p class="mb-0">Are you sure you want to delete <strong id="delete-name">this account</strong>?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('show.bs.modal', function (e) {
  if (e.target.id === 'deleteAccountModal') {
    const btn = e.relatedTarget;
    document.getElementById('delete-id').value = btn?.dataset.id ?? '';
    document.getElementById('delete-name').textContent = btn?.dataset.name ?? 'this account';
  }
});
</script>
