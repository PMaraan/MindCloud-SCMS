<?php
// /app/Modules/Accounts/Views/index.php
/**
 * Accounts module – index view.
 *
 * Expects (from controller):
 * - array  $users      Rows to render in the table (each row has id_no, names, email, role, department, etc.)
 * - array  $pager      Global paginator contract:
 *                      ['total','pg','perpage','baseUrl','query'?, 'extra'?, 'from'?, 'to'?]
 * - bool   $canCreate  Gate for rendering Create modal trigger
 * - bool   $canEdit    Gate for rendering Edit modal
 * - bool   $canDelete  Gate for rendering Delete modal
 * - array  $roles      For Create/Edit modals (role_id, role_name)
 * - array  $colleges   For Create/Edit modals (department_id, short_name/college_name)
 * - string $csrf       CSRF token to embed in forms
 *
 * @var array  $users
 * @var array  $pager
 * @var bool   $canCreate
 * @var bool   $canEdit
 * @var bool   $canDelete
 * @var array  $roles
 * @var array  $colleges
 * @var string $csrf
 */

$globalPagination = dirname(__DIR__, 3) . '/Views/partials/Pagination.php';

if (defined('APP_ENV') && APP_ENV === 'dev') {
  echo "DEBUG: accounts/index.php reached<br>";
  var_dump(isset($users));
  echo "<!-- dev: rows = " . (isset($users) ? count($users) : -1) . ", total = " . (isset($pager['total']) ? (int)$pager['total'] : -1) . " -->";
}
?>

<?php
$status = $pager['status'] ?? 'active';
$base   = BASE_PATH . '/dashboard?page=accounts';
function accounts_url_with(string $base, array $qs): string {
    $q = [];
    foreach ($qs as $k => $v) $q[] = urlencode((string)$k) . '=' . urlencode((string)$v);
    return $base . '&' . implode('&', $q);
}
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Accounts</h2>

    <form class="d-flex" method="GET" action="<?= BASE_PATH ?>/dashboard">
      <input type="hidden" name="page" value="accounts">
      <input
        class="form-control me-2"
        type="search"
        name="q"
        placeholder="Search..."
        aria-label="Search"
        value="<?= htmlspecialchars($pager['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>

    <!-- Status Filter Buttons -->
    <div class="btn-group" role="group" aria-label="Filter">
      <a class="btn btn-outline-secondary<?= $status==='all'?' active':'' ?>"
         href="<?= htmlspecialchars(accounts_url_with($base, ['status'=>'all','pg'=>1])) ?>">All</a>
      <a class="btn btn-outline-secondary<?= $status==='active'?' active':'' ?>"
         href="<?= htmlspecialchars(accounts_url_with($base, ['status'=>'active','pg'=>1])) ?>">Active</a>
      <a class="btn btn-outline-secondary<?= $status==='password_reset_required'?' active':'' ?>"
         href="<?= htmlspecialchars(accounts_url_with($base, ['status'=>'password_reset_required','pg'=>1])) ?>">Reset Required</a>
      <a class="btn btn-outline-secondary<?= $status==='archived'?' active':'' ?>"
         href="<?= htmlspecialchars(accounts_url_with($base, ['status'=>'archived','pg'=>1])) ?>">Archived</a>
    </div>

    <!-- Create User Button -->
    <?php if (!empty($canCreate)): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        + Create
      </button>
    <?php endif; ?>
  </div>

  <?php
    // Pagination (top)
    include $globalPagination;
  ?>

  <?php include __DIR__ . '/partials/Table.php'; ?>

  <?php
    // Pagination (bottom)
    include $globalPagination;
  ?>

  <?php if (!empty($canCreate)) include __DIR__ . '/partials/CreateModal.php'; ?>
  <?php if (!empty($canEdit))   include __DIR__ . '/partials/EditModal.php'; ?>
  <?php if (!empty($canDelete)) include __DIR__ . '/partials/DeleteModal.php'; ?>
</div>

<script src="<?= BASE_PATH ?>/public/assets/js/accounts.js" defer></script>
