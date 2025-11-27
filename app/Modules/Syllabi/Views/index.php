<?php
// /app/Modules/Syllabi/Views/index.php
/**
 * View: Syllabi – aligned with Syllabus Templates UX
 *
 * Modes:
 *  - global-folders : show colleges list (FoldersList)
 *  - college        : show college-wide + per-program sections
 *  - program        : show one program under a college
 *
 * Provided by controller:
 *  $mode
 *  $ASSET_BASE, $esc, $user, $role
 *  $PAGE_KEY='syllabi'
 *  When $mode==='global-folders': $colleges
 *  When $mode==='college': $college, $general, $programs, $showBackToFolders?, $allColleges, $programsOfCollege, $coursesOfProgram
 *  When $mode==='program': $college, $general, $program, $program_syllabi
 *
 * Reuses your Syllabi partials (FoldersList, CollegeSection, ProgramSections, Grid, CreateModal, etc.)
 */
$base = defined('BASE_PATH') ? BASE_PATH : '';
$PAGE_KEY = 'syllabi';
?>
<link rel="stylesheet" href="<?= $ASSET_BASE ?>/assets/css/TemplateBuilder-Scaffold.css">

<script>
  // Make BASE_PATH available to the scaffold JS
  window.BASE_PATH = "<?= $esc($base) ?>";
</script>

<script type="module" src="<?= $esc($ASSET_BASE) ?>/assets/js/syllabi/Syllabi-Scaffold.js?v=<?= time() ?>"></script>

<div><!-- CONTAINER OPEN -->

  <!-- PAGE HEADER -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-0">Syllabi</h2>
      <div class="text-muted small">
        <?= $esc(
          $mode === 'global-folders' ? 'Select a college folder to browse syllabi.'
          : ($mode === 'college' ? 'All college syllabi + per-program sections.'
          : 'College syllabi + selected program syllabi.')
        ) ?>
      </div>
    </div>

    <div class="d-flex gap-2">
      <?php if (!empty($showBackToFolders)): ?>
        <a class="btn btn-outline-secondary" href="<?= $esc($base.'/dashboard?page='.$PAGE_KEY) ?>">
          <i class="bi bi-arrow-left"></i> All Colleges
        </a>
      <?php endif; ?>

      <?php if (!empty($canCreate)): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#syCreateModal">
          <i class="bi bi-file-earmark-plus"></i> New Syllabus
        </button>
      <?php endif; ?>
    </div>
  </div>
  <!-- /PAGE HEADER -->

  <!-- CSRF token for JS actions (duplicate, etc.) -->
  <span id="sy-csrf" data-token="<?= $esc($_SESSION['csrf_token'] ?? '') ?>" hidden></span>

  <?php
    $partialsDir = __DIR__ . '/partials';

    if ($mode === 'global-folders') {
      include $partialsDir . '/FoldersList.php';

    } else {
      // FLEX LAYOUT: MAIN + ASIDE
      ?>
      <div class="tb-flex-wrap d-block d-md-flex gap-3 align-items-start"><!-- FLEX WRAP OPEN -->

        <main class="tb-flex-main flex-grow-1"><!-- MAIN OPEN -->
          <?php
            if ($mode === 'college') {
              $accordions = $accordions ?? [];
              include $partialsDir . '/CollegeSection.php';
            } else {
              $accordions = [
                ['key' => 'general', 'label' => 'All College Syllabi', 'syllabi' => $general ?? []],
                [
                  'key'     => 'program-' . ($program['program_id'] ?? '0'),
                  'label'   => $program['program_name'] ?? 'Program',
                  'program' => $program ?? [],
                  'syllabi' => $program_syllabi ?? [],
                ]
              ];
              include $partialsDir . '/CollegeSection.php';
            }
          ?>
        </main><!-- /MAIN CLOSE -->

        <!-- Details Pane ASIDE -->
        <aside class="tb-flex-aside flex-shrink-0" id="sy-info-pane"><!-- ASIDE OPEN -->
          <div class="card">
            <div class="card-header"><strong>Details</strong></div>
            <div class="card-body">

              <div id="sy-info-empty" class="text-center text-muted py-5">
                <p>Select a syllabus to see its details here.</p>
              </div>

              <dl id="sy-info" class="row d-none align-items-center mb-0">
                <dt class="col-4" id="sy-i-title-label">Title</dt>
                <dd class="col-8" id="sy-i-title"></dd>

                <dt class="col-4" id="sy-i-program-label">Program</dt>
                <dd class="col-8" id="sy-i-program"></dd>

                <dt class="col-4" id="sy-i-college-label">College</dt>
                <dd class="col-8" id="sy-i-college"></dd>

                <dt class="col-4" id="sy-i-updated-label">Updated</dt>
                <dd class="col-8" id="sy-i-updated"></dd>

                <dt class="col-4" id="sy-i-status-label">Status</dt>
                <dd class="col-8" id="sy-i-status"></dd>

                <dt class="col-4">Actions</dt>
                <dd class="col-8">
                  <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-sm btn-primary" id="sy-open" type="button">Open in Editor</button>
                    <button class="btn btn-sm btn-secondary d-none"
                            id="sy-edit"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#syEditModal">
                      Edit Details
                    </button>
                    <button class="btn btn-sm btn-warning d-none"
                            id="sy-archive"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#syArchiveModal">
                      Archive
                    </button>
                    <button class="btn btn-sm btn-danger d-none"
                            id="sy-delete"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#syDeleteModal">
                      Delete
                    </button>
                  </div>
                </dd>
              </dl>
            </div>
          </div>
        </aside><!-- /ASIDE CLOSE -->

      </div><!-- FLEX WRAP CLOSE -->
      <?php
    }

    // CREATE / EDIT / DELETE MODALS
    // We’ll show Create modal on college mode (it needs lists).
    if ($mode !== 'global-folders' && !empty($canCreate)) {
      // Create modal needs:
      $colleges = $colleges ?? [];
      $programs = $programs ?? [];
      $courses  = $courses  ?? [];
      include $partialsDir . '/CreateModal.php';
      if (function_exists('renderSyllabiCreateModal')) {
        renderSyllabiCreateModal(
          $ASSET_BASE,
          $colleges,
          $programs,
          $courses,
          $esc,
        );
      }
    }

    // Show Edit modal only on college mode (it needs lists).
    if ($mode !== 'global-folders' && !empty($canEdit)) {
      // Edit modal needs:
      $colleges = $colleges ?? [];
      $programs = $programs ?? [];
      $courses  = $courses  ?? [];
      include $partialsDir . '/EditModal.php';
      if (function_exists('renderSyllabiEditModal')) {
        renderSyllabiEditModal(
          $ASSET_BASE,
          $colleges,
          $programs,
          $courses,
          $esc,
          $lockCollege ?? false,
          $college ?? []
        );
      }
    }

    // Archive modal (render only when allowed)
    if ($mode !== 'global-folders' && !empty($canDelete)) {
      include $partialsDir . '/ArchiveModal.php';
      if (function_exists('renderSyllabiArchiveModal')) {
        renderSyllabiArchiveModal(
          $ASSET_BASE,
          $esc
        );
      }
    }

    // Delete modal (render only when allowed)
    if ($mode !== 'global-folders' && !empty($canDelete)) {
      include $partialsDir . '/DeleteModal.php';
      if (function_exists('renderSyllabiDeleteModal')) {
        renderSyllabiDeleteModal(
          $ASSET_BASE,
          $esc
        );
      }
    }
  ?>

  <?php
  $flash = \App\Helpers\FlashHelper::get();
  if ($flash):
?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    if (window.showFlashMessage) {
      window.showFlashMessage('<?= $esc($flash['message']) ?>', '<?= $esc($flash['type']) ?>');
    }
  });
</script>
<?php endif; ?>
  <span id="sy-csrf" data-token="<?= $esc($_SESSION['csrf_token'] ?? '') ?>" hidden></span>
  <script>
    window.SY_PERMS = {
      canCreate: <?= !empty($canCreate) ? 'true' : 'false' ?>,
      canEdit: <?= !empty($canEdit) ? 'true' : 'false' ?>,
      canArchive: <?= !empty($canArchive) ? 'true' : 'false' ?>,
      canDelete: <?= !empty($canDelete) ? 'true' : 'false' ?>
    };
  </script>
</div><!-- /CONTAINER CLOSE -->
