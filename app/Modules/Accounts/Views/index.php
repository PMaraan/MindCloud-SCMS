<?php
// /app/Modules/Accounts/Views/index.php
// Expects: $users, $pager, $canCreate, $canEdit, $canDelete

echo "DEBUG: accounts/index.php reached<br>";
var_dump(isset($users));
if (defined('APP_ENV') && APP_ENV === 'dev'):
?>
<!-- dev: rows = <?= isset($users) ? count($users) : -1 ?>, total = <?= isset($pager['total']) ? (int)$pager['total'] : -1 ?> -->
<?php endif; ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Accounts</h2>

    <form class="d-flex" method="GET" action="<?= BASE_PATH ?>/dashboard">
      <input type="hidden" name="page" value="accounts">
      <input
        class="form-control me-2"
        type="search"
        name="q"
        placeholder="Search users..."
        aria-label="Search"
        value="<?= htmlspecialchars($pager['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>

    <?php if (!empty($canCreate)): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
          + Create
      </button>
    <?php endif; ?>
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
      <?php include __DIR__ . '/partials/Pagination.php'; ?>
    </div>
  </div>

  <?php include __DIR__ . '/partials/AccountsTable.php'; ?>

  <div class="d-flex justify-content-end mt-3">
    <?php include __DIR__ . '/partials/Pagination.php'; ?>
  </div>

  <?php if (!empty($canCreate)) include __DIR__ . '/partials/CreateUserModal.php'; ?>
  <?php if (!empty($canEdit))   include __DIR__ . '/partials/EditUserModal.php'; ?>
  <?php if (!empty($canDelete)) include __DIR__ . '/partials/DeleteUserModal.php'; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  // Autofill edit modal
  document.querySelectorAll("[data-bs-target='#editUserModal']").forEach(btn => {
    btn.addEventListener("click", () => {
      const row = btn.closest("tr");
      document.getElementById("edit-id-no").value   = row.dataset.idNo || "";
      document.getElementById("edit-fname").value   = row.dataset.fname || "";
      document.getElementById("edit-mname").value   = row.dataset.mname || "";
      document.getElementById("edit-lname").value   = row.dataset.lname || "";
      document.getElementById("edit-email").value   = row.dataset.email || "";
      // role/college
      const roleSel = document.getElementById("edit-role");
      const collegeSel = document.getElementById("edit-college");
      if (roleSel) roleSel.value = row.dataset.roleId || "";
      if (collegeSel) collegeSel.value = row.dataset.collegeId || "";
    });
  });

  // Autofill delete modal
  document.querySelectorAll("[data-bs-target='#deleteUserModal']").forEach(btn => {
    btn.addEventListener("click", () => {
      const row = btn.closest("tr");
      document.getElementById("delete-id-no").value = row.dataset.idNo || "";
      document.getElementById("delete-username").innerText = (row.dataset.fname || '') + " " + (row.dataset.lname || '');
    });
  });
});
</script>
