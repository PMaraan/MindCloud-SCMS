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
<?php
$status = $pager['status'] ?? 'active';
$base   = BASE_PATH . '/dashboard?page=programs';
function programs_url_with(string $base, array $qs): string {
    $q = [];
    foreach ($qs as $k => $v) $q[] = urlencode((string)$k) . '=' . urlencode((string)$v);
    return $base . '&' . implode('&', $q);
}
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

        <!-- Status Filter Buttons -->
        <div class="btn-group" role="group" aria-label="Filter">
        <a class="btn btn-outline-secondary<?= $status==='all'?' active':'' ?>"
            href="<?= htmlspecialchars(programs_url_with($base, ['status'=>'all','pg'=>1])) ?>">All</a>
        <a class="btn btn-outline-secondary<?= $status==='active'?' active':'' ?>"
            href="<?= htmlspecialchars(programs_url_with($base, ['status'=>'active','pg'=>1])) ?>">Active</a>
        <a class="btn btn-outline-secondary<?= $status==='archived'?' active':'' ?>"
            href="<?= htmlspecialchars(programs_url_with($base, ['status'=>'archived','pg'=>1])) ?>">Archived</a>
        </div>

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
// Cache-bust Programs JS file (env-aware: prefers prod path, falls back to dev)
$relProd = '/assets/js/programs.js';           // production URL (no /public in URL)
$relDev  = '/public/assets/js/programs.js';    // development URL (with /public)

$docroot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

// Pick URL based on which file exists at the document root
if ($docroot && is_file($docroot . $relProd)) {
    $urlJsPath = $relProd;
} elseif ($docroot && is_file($docroot . $relDev)) {
    $urlJsPath = $relDev;
} else {
    // Default to dev path if detection fails
    $urlJsPath = $relDev;
}

// Version from actual file on disk
$ver = @filemtime($docroot . $urlJsPath) ?: '1';
?>
<script>window.BASE_PATH = '<?= BASE_PATH ?>';</script>
<script src="<?= BASE_PATH . $urlJsPath ?>?v=<?= urlencode((string)$ver) ?>"></script>
