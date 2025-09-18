<?php
// /app/Modules/Courses/Views/index.php
/**
 * Courses Module – Index View
 *
 * Renders the Courses list page, including search, pagination (top/bottom),
 * the table, and CRUD modals.
 *
 * Expected variables (extracted in CoursesController::index()):
 *
 * @var array<int, array{
 *     course_id:int,
 *     course_code:string,
 *     course_name:string,
 *     college_id:int|null,
 *     college_short:string|null,
 *     curricula:string|null,        // comma-separated curriculum codes for display
 *     curricula_ids:string|null     // comma-separated curriculum_id values for JS preselect
 * }> $rows
 *
 * @var array{
 *     pg:int,                       // current page number
 *     perpage:int,                  // items per page
 *     total:int,                    // total rows across all pages
 *     baseUrl:string,               // e.g. BASE_PATH . '/dashboard?page=courses'
 *     query?:string,                // raw search query for display/links
 *     from?:int,                    // first item index shown on this page
 *     to?:int,                      // last item index shown on this page
 *     extra?:array<string,string>   // extra query params to append to page links
 * } $pager
 *
 * @var bool $canCreate
 * @var bool $canEdit
 * @var bool $canDelete
 *
 * @var array<int, array{college_id:int, short_name:string}> $colleges
 * @var array<int, array{curriculum_id:int, curriculum_code:string, curriculum_title:string}> $curricula
 *
 * @var string $csrf  CSRF token for forms
 *
 * Includes:
 * - Pagination partial: /app/Views/partials/Pagination.php
 * - Table partial:      /app/Modules/Courses/Views/partials/Table.php
 * - Modals:             /app/Modules/Courses/Views/partials/{CreateModal,EditModal,DeleteModal}.php
 *
 * Source of variables: App\Modules\Courses\Controllers\CoursesController::index()
 */
$globalPagination = dirname(__DIR__, 3) . '/Views/partials/Pagination.php';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Courses</h2>

    <form class="d-flex" method="GET" action="<?= BASE_PATH ?>/dashboard">
      <input type="hidden" name="page" value="courses">
      <input class="form-control me-2" type="search" name="q" placeholder="Search..."
             value="<?= htmlspecialchars((string)($pager['query'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>

    <?php if (!empty($canCreate)): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#CreateModal">
        + Create
      </button>
    <?php endif; ?>
  </div>

  <?php if (!empty($_SESSION['flash'])): 
        $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= $f['type']==='danger'?'danger':htmlspecialchars($f['type']) ?> alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($f['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php
    // Pagination (top)
    include $globalPagination;
  ?>

  <?php require __DIR__ . '/partials/Table.php'; ?>

  <?php
    // Pagination (bottom)
    include $globalPagination;
  ?>

</div>

<?php
  include __DIR__ . '/partials/CreateModal.php';
  include __DIR__ . '/partials/EditModal.php';
  include __DIR__ . '/partials/DeleteModal.php';
?>

<?php
$jsPath = '/public/assets/js/programs.js';
$ver = @filemtime($_SERVER['DOCUMENT_ROOT'] . $jsPath) ?: '1';
?>
<script defer src="<?= BASE_PATH . $jsPath ?>?v=<?= urlencode((string)$ver) ?>"></script>