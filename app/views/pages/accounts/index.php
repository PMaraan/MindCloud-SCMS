<?php
// root/app/views/pages/accounts/index.php
// Expects: $users, $pager, $canEdit, $canDelete

echo "DEBUG: accounts/index.php reached<br>";
var_dump(isset($users));
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Accounts</h2>
    <form class="d-flex" method="GET" action="">
      <input
        class="form-control me-2"
        type="search"
        name="q"
        placeholder="Search users..."
        aria-label="Search"
        value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">
      <?php
        $from = $pager['total'] === 0 ? 0 : (($pager['page'] - 1) * $pager['perPage'] + 1);
        $to   = min($pager['total'], $pager['page'] * $pager['perPage']);
      ?>
      Showing <?= $from ?>-<?= $to ?> of <?= (int)$pager['total'] ?>
      <?php if (!empty($pager['query'])): ?>
        for “<?= htmlspecialchars($pager['query'], ENT_QUOTES, 'UTF-8') ?>”
      <?php endif; ?>
    </div>
    <div>
      <?php include __DIR__ . '/Pagination.php'; ?>
    </div>
  </div>

  <?php include __DIR__ . '/AccountsTable.php'; ?>

  <div class="d-flex justify-content-end mt-3">
    <?php include __DIR__ . '/Pagination.php'; ?>
  </div>
</div>

<?php //if ($this->userHasPermission('edit_accounts')): ?>
  <?php //include __DIR__ . '/EditUserModal.php'; ?>
<?php //endif; ?>

<?php //if ($this->userHasPermission('delete_accounts')): ?>
  <?php //include __DIR__ . '/DeleteUserModal.php'; ?>
<?php //endif; ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
  // Autofill edit modal
  document.querySelectorAll("[data-bs-target='#editUserModal']").forEach(btn => {
    btn.addEventListener("click", () => {
      const row = btn.closest("tr");
      document.getElementById("edit-id-no").value = row.dataset.idNo;
      document.getElementById("edit-fname").value = row.dataset.fname;
      document.getElementById("edit-mname").value = row.dataset.mname;
      document.getElementById("edit-lname").value = row.dataset.lname;
      document.getElementById("edit-email").value = row.dataset.email;
      // add more fields as needed
    });
  });

  // Autofill delete modal
  document.querySelectorAll("[data-bs-target='#deleteUserModal']").forEach(btn => {
    btn.addEventListener("click", () => {
      const row = btn.closest("tr");
      document.getElementById("delete-id-no").value = row.dataset.idNo;
      document.getElementById("delete-username").innerText = row.dataset.fname + " " + row.dataset.lname;
    });
  });
});
</script>
