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

<script defer src="<?= $ASSET_BASE ?>/assets/js/syllabi/Syllabi-Scaffold.js"></script>

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
              include $partialsDir . '/CollegeSection.php';
            } else {
              // program mode: reuse college section with one program group
              $programs = [
                ['program' => ($program ?? []), 'syllabi' => ($program_syllabi ?? [])]
              ];
              include $partialsDir . '/CollegeSection.php';
            }
          ?>
        </main><!-- /MAIN CLOSE -->

        <aside class="tb-flex-aside flex-shrink-0" id="sy-info-pane"><!-- ASIDE OPEN -->
          <div class="card">
            <div class="card-header"><strong>Details</strong></div>
            <div class="card-body">
              <div class="text-muted" id="sy-info-empty">Select a syllabus to see details.</div>
              <dl class="row mt-3 mb-0 d-none" id="sy-info">
                <dt class="col-4">Title</dt>
                <dd class="col-8" id="sy-i-title"></dd>
                <dt class="col-4">Program</dt>
                <dd class="col-8" id="sy-i-program"></dd>
                <dt class="col-4">Updated</dt>
                <dd class="col-8" id="sy-i-updated"></dd>
                <dt class="col-4">Status</dt>
                <dd class="col-8" id="sy-i-status"></dd>
                <dt class="col-4">Actions</dt>
                <dd class="col-8">
                  <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-primary" id="sy-open">Open</button>
                    <button class="btn btn-sm btn-outline-secondary" id="sy-duplicate" disabled>Duplicate</button>
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
    // We’ll show Create modal on college/program modes (it needs lists).
    if ($mode !== 'global-folders') {
      // Create modal needs:
      $colList  = $allColleges      ?? [];
      $progList = $programsOfCollege?? [];
      $courseList = $coursesOfProgram?? [];
      include $partialsDir . '/CreateModal.php';
      if (function_exists('renderSyllabiCreateModal')) {
        renderSyllabiCreateModal(
          $ASSET_BASE,
          $colList,
          $progList,
          $courseList,
          $esc
        );
      }
    }

    include $partialsDir . '/EditModal.php';
    include $partialsDir . '/DeleteModal.php';
  ?>

</div><!-- /CONTAINER CLOSE -->
