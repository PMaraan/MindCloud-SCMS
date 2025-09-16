<?php
// /app/Modules/Curricula/Views/index.php
/** @var array  $rows */
/** @var bool   $canCreate */
/** @var bool   $canEdit */
/** @var bool   $canDelete */
/** @var array  $pager */
/** @var string $csrf */
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Curricula</h2>

    <form class="d-flex" method="GET" action="<?= BASE_PATH ?>/dashboard">
      <input type="hidden" name="page" value="curricula">
      <input class="form-control me-2" type="search" name="q" placeholder="Search code/title..."
             value="<?= htmlspecialchars($pager['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>

    <?php if (!empty($canCreate)): // Create permission?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#CreateModal">
        + Create
      </button>
    <?php endif; ?>
  </div>

  <?php
    // Pagination (top)
    require dirname(__DIR__, 3) . '/Views/partials/Pagination.php';
  ?>

  <?php
    // Table
    require __DIR__ . '/partials/Table.php';
  ?>

  <?php
    // Pagination (bottom)
    require dirname(__DIR__, 3) . '/Views/partials/Pagination.php';
  ?>
</div>

<?php
  // Modals
  require __DIR__ . '/partials/CreateModal.php';
  require __DIR__ . '/partials/EditModal.php';
  require __DIR__ . '/partials/DeleteModal.php';
?>

<!-- External JS -->
<script src="<?= BASE_PATH ?>/public/assets/js/curricula.js"></script>
