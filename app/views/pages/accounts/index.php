<?php
// root/app/views/pages/accounts/index.php

echo "DEBUG: accounts/index.php reached<br>";
var_dump(isset($users));
?>
<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Accounts</h5>
    <form class="d-flex" method="GET" action="">
      <input 
        class="form-control me-2" 
        type="search" 
        name="q" 
        placeholder="Search users..." 
        aria-label="Search"
        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>
  </div>

  <div class="card-body">
    <?php include __DIR__ . '/AccountsTable.php'; ?>
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
