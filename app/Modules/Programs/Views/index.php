<?php
// app/Modules/Programs/Views/index.php
/** @var array $pager */
/** @var array $rows */
/** @var bool  $canCreate */
/** @var bool  $canEdit */
/** @var bool  $canDelete */
/** @var array $colleges */
$globalPagination = dirname(__DIR__, 3) . '/Views/partials/Pagination.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Programs</h2>

        <form class="d-flex" method="GET" action="<?= BASE_PATH ?>/dashboard">
        <input type="hidden" name="page" value="programs">
        <input class="form-control me-2" type="search" name="q" placeholder="Search..."
                value="<?= htmlspecialchars($pager['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
        </form>

        <?php if (!empty($canCreate)): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProgramModal">
            + Create
        </button>
        <?php endif; ?>
    </div>

    <?php
        // Pagination (top)
        include $globalPagination;
    ?>

    <?php require __DIR__ . '/partials/Table.php'; ?>

    <?php
        // Pagination (bottom)
        include $globalPagination;
    ?>

<?php if (!empty($canCreate)) require __DIR__ . '/partials/CreateModal.php'; ?>
<?php if (!empty($canEdit))   require __DIR__ . '/partials/EditModal.php'; ?>
<?php if (!empty($canDelete)) require __DIR__ . '/partials/DeleteModal.php'; ?>

<?php
// Cache-bust Programs JS file
$jsPath = '/public/assets/js/programs.js';
$ver    = @filemtime($_SERVER['DOCUMENT_ROOT'] . $jsPath) ?: '1';
?>
<script src="<?= BASE_PATH . $jsPath ?>?v=<?= urlencode((string)$ver) ?>"></script>

