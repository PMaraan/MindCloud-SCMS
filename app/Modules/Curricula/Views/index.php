<?php
// /app/Modules/Curricula/Views/index.php
/** @var array $rowsVar */
/** @var bool $canCreateVar */
/** @var bool $canEditVar */
/** @var bool $canDeleteVar */
/** @var array $pagerVar */
/** @var string $csrf */
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Curricula</h2>

    <form class="d-flex" method="GET" action="<?= BASE_PATH ?>/dashboard">
      <input type="hidden" name="page" value="curricula">
      <input class="form-control me-2" type="search" name="q" placeholder="Search code/title..."
             value="<?= htmlspecialchars($pagerVar['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>

    <?php if (!empty($canCreateVar)): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#CreateModal">
        + Create
      </button>
    <?php endif; ?>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">
      <?php
        $from = $pagerVar['total'] === 0 ? 0 : (($pagerVar['page'] - 1) * $pagerVar['limit'] + 1);
        $to   = min($pagerVar['page'] * $pagerVar['limit'], $pagerVar['total']);
      ?>
      Showing <?= (int)$from ?>–<?= (int)$to ?> of <?= (int)$pagerVar['total'] ?>
    </div>
  </div>

  <?php
    // Pagination (top)
    $pager = $pagerVar;
    require dirname(__DIR__, 3) . '/Views/partials/Pagination.php';
  ?>

  <?php
    $rows = $rowsVar;
    $canEdit  = $canEditVar;
    $canDelete = $canDeleteVar;
    require __DIR__ . '/partials/Table.php';
  ?>

  <?php
    // Pagination (bottom)
    $pager = $pagerVar;
    require dirname(__DIR__, 3) . '/Views/partials/Pagination.php';
  ?>
</div>

<?php
  // Modals
  $canCreate = $canCreateVar;
  require __DIR__ . '/partials/CreateModal.php';
  require __DIR__ . '/partials/EditModal.php';
  require __DIR__ . '/partials/DeleteModal.php';
?>

<!-- External JS -->
<script src="<?= BASE_PATH ?>/public/assets/js/curricula.js"></script>
