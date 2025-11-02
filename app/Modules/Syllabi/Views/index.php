<?php
// /app/Modules/Syllabi/Views/index.php
/**
 * View: Syllabi Index
 *
 * Vars:
 * - $ASSET_BASE, $esc, $PAGE_KEY, $user, $role
 * - $rows: array of syllabi (abstract for now)
 * - $pager: ['total','pg','perpage','baseUrl','query','from','to']
 * - $canCreate, $canEdit, $canDelete
 *
 * Notes:
 * - Uses PAGE_KEY='syllabi'
 * - Includes global pagination partial at top & bottom (when available in your project)
 * - Partials here are placeholders (to be replaced by your real UIs later)
 */
$base = defined('BASE_PATH') ? BASE_PATH : '';
?>

<div><!-- CONTAINER OPEN -->

  <!-- PAGE HEADER -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-0">Syllabi</h2>
      <div class="text-muted small">Browse, create, and manage syllabi. Open to edit in RTEditor later.</div>
    </div>

    <div class="d-flex gap-2">
      <?php if (!empty($canCreate)): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#syCreateModal">
          <i class="bi bi-file-earmark-plus"></i> New Syllabus
        </button>
      <?php endif; ?>
    </div>
  </div>
  <!-- /PAGE HEADER -->

  <!-- SEARCH -->
  <form method="get" class="row g-2 align-items-center mb-3">
    <input type="hidden" name="page" value="<?= $esc($PAGE_KEY) ?>">
    <input type="hidden" name="pg" value="1"><!-- new queries start on page 1 -->
    <div class="col-sm-6 col-md-4">
      <input type="text" class="form-control" name="q" value="<?= $esc($pager['query'] ?? '') ?>" placeholder="Search…">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Search</button>
    </div>
  </form>

  <!-- PAGINATION (TOP) -->
  <?php
    if (file_exists(dirname(__DIR__, 3) . '/Views/partials/Pagination.php')) {
      $pager = $pager ?? null; // keep var in scope
      include dirname(__DIR__, 3) . '/Views/partials/Pagination.php';
    }
  ?>

  <!-- TABLE / GRID -->
  <?php
    // Placeholder partial – replace with your real markup later
    $partialsDir = __DIR__ . '/partials';
    include $partialsDir . '/Table.php';
  ?>

  <!-- PAGINATION (BOTTOM) -->
  <?php
    if (file_exists(dirname(__DIR__, 3) . '/Views/partials/Pagination.php')) {
      $pager = $pager ?? null;
      include dirname(__DIR__, 3) . '/Views/partials/Pagination.php';
    }
  ?>

  <!-- CREATE / EDIT / DELETE MODALS (placeholders for now) -->
  <?php
    include $partialsDir . '/CreateModal.php';
    include $partialsDir . '/EditModal.php';
    include $partialsDir . '/DeleteModal.php';
  ?>

</div><!-- /CONTAINER CLOSE -->
