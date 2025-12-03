<?php
// app/Modules/Departments/Views/index.php
// Expects: $rows, $pager, $canCreate, $canEdit, $canDelete, $deans
/** @var array $pager */
/** @var array $rows */
/** @var bool  $canCreate */
/** @var bool  $canEdit */
/** @var bool  $canDelete */
/** @var array $deans */
$globalPagination = dirname(__DIR__, 3) . '/Views/partials/Pagination.php';
?>
<?php
$status = $pager['status'] ?? 'active';
$base   = BASE_PATH . '/dashboard?page=departments';
function departments_url_with(string $base, array $qs): string {
    $q = [];
    foreach ($qs as $k => $v) $q[] = urlencode((string)$k) . '=' . urlencode((string)$v);
    return $base . '&' . implode('&', $q);
}
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Departments/Colleges</h2>

    <form class="d-flex" method="GET" action="<?= BASE_PATH ?>/dashboard">
      <input type="hidden" name="page" value="departments">
      <input type="hidden" name="pg" value="1"><!-- ensures new searches start at page 1 -->
      <input class="form-control me-2" type="search" name="q" placeholder="Search..." aria-label="Search"
            value="<?= htmlspecialchars($pager['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>

    <!-- Status Filter Buttons -->
    <div class="btn-group" role="group" aria-label="Filter">
      <a class="btn btn-outline-secondary<?= $status==='all'?' active':'' ?>"
         href="<?= htmlspecialchars(departments_url_with($base, ['status'=>'all','pg'=>1])) ?>">All</a>
      <a class="btn btn-outline-secondary<?= $status==='active'?' active':'' ?>"
         href="<?= htmlspecialchars(departments_url_with($base, ['status'=>'active','pg'=>1])) ?>">Active</a>
      <a class="btn btn-outline-secondary<?= $status==='archived'?' active':'' ?>"
         href="<?= htmlspecialchars(departments_url_with($base, ['status'=>'archived','pg'=>1])) ?>">Archived</a>
    </div>

    <?php if (!empty($canCreate)): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDepartmentsModal">
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

<?php
$jsPath = '/public/assets/js/departments.js';
$ver = @filemtime($_SERVER['DOCUMENT_ROOT'] . $jsPath) ?: '1';
?>
<script defer src="<?= BASE_PATH . $jsPath ?>?v=<?= urlencode((string)$ver) ?>"></script>

