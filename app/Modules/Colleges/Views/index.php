<?php /* app/Modules/Colleges/Views/index.php */ ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Colleges</h2>

    <form class="d-flex" method="GET" action="<?= BASE_PATH ?>/dashboard">
      <input type="hidden" name="page" value="colleges">
      <input class="form-control me-2" type="search" name="q" placeholder="Search..." aria-label="Search"
             value="<?= htmlspecialchars($pager['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>

    <?php if (!empty($canCreate)): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCollegesModal">
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
      Showing <strong><?= $from ?></strong>-<strong><?= $to ?></strong> of <strong><?= (int)$pager['total'] ?></strong>
      <?php if (!empty($pager['query'])): ?>
        for “<?= htmlspecialchars($pager['query'], ENT_QUOTES, 'UTF-8') ?>”
      <?php endif; ?>
    </div>
    <div>
      <?php include __DIR__ . '/partials/Pagination.php'; ?>
    </div>
  </div>

  <?php include __DIR__ . '/partials/Table.php'; ?>

  <div class="d-flex justify-content-end mt-3">
    <?php include __DIR__ . '/partials/Pagination.php'; ?>
  </div>


  <?php if (!empty($canCreate)) include __DIR__ . '/partials/CreateModal.php'; ?>
  <?php if (!empty($canEdit))   include __DIR__ . '/partials/EditModal.php'; ?>
  <?php if (!empty($canDelete)) include __DIR__ . '/partials/DeleteModal.php'; ?>
</div>

<?php
$jsPath = '/public/assets/js/colleges.js';
$ver = @filemtime($_SERVER['DOCUMENT_ROOT'] . $jsPath) ?: '1';
?>
<script defer src="<?= BASE_PATH . $jsPath ?>?v=<?= urlencode((string)$ver) ?>"></script>

