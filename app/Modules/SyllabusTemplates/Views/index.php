<?php
// /app/Modules/SyllabusTemplates/Views/index.php
/** vars:
 * $mode: 'system-folders' | 'college' | 'program'
 * $ASSET_BASE, $esc, $user, $role
 * when $mode==='system-folders': $colleges
 * when $mode==='college': $college, $general, $programs, optional: $showBackToFolders, $canCreateGlobal, $canCreateCollege, $allColleges, $programsOfCollege
 * when $mode==='program': $college, $general, $program, $program_templates, $canCreateProgram
 */
$base = defined('BASE_PATH') ? BASE_PATH : '';
$PAGE_KEY = 'syllabus-templates';
?>
<link rel="stylesheet" href="<?= $ASSET_BASE ?>/assets/css/TemplateBuilder-Scaffold.css">

<script>
  // Make BASE_PATH available to TemplateBuilder-Scaffold.js
  window.BASE_PATH = "<?= $esc($base) ?>";
</script>

<script defer src="<?= $ASSET_BASE ?>/assets/js/TemplateBuilder-Scaffold.js"></script>

<div ><!-- CONTAINER OPEN -->

  <!-- PAGE HEADER -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-0">Syllabus Templates</h2>
      <div class="text-muted small">
        <?= $esc(
          $mode === 'system-folders' ? 'Select a college folder to view templates.'
          : ($mode === 'college' ? 'General + Program templates for this college.'
          : 'College general + my program templates.')
        ) ?>
      </div>
    </div>

    <div class="d-flex gap-2">
      <?php if (!empty($showBackToFolders)): ?>
        <a class="btn btn-outline-secondary" href="<?= $esc($base.'/dashboard?page='.$PAGE_KEY) ?>">
          <i class="bi bi-arrow-left"></i> All Colleges
        </a>
      <?php endif; ?>
      <?php if (!empty($canCreateGlobal) || !empty($canCreateCollege) || !empty($canCreateProgram)): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tbCreateModal">
          <i class="bi bi-file-earmark-plus"></i> New Template
        </button>
      <?php endif; ?>
    </div>
  </div>
  <!-- /PAGE HEADER -->

  <?php
    $partialsDir = __DIR__ . '/partials';

    if ($mode === 'system-folders') {
      // Simple list of folders (anchors) – no side pane here
      include $partialsDir . '/FoldersList.php';

    } else {
      // FLEX LAYOUT: MAIN + ASIDE (must be siblings inside ONE wrapper)
      ?>
      <div class="tb-flex-wrap d-block d-md-flex gap-3 align-items-start"><!-- FLEX WRAP OPEN -->

        <main class="tb-flex-main flex-grow-1"><!-- MAIN OPEN -->
          <?php
            if ($mode === 'college') {
              include $partialsDir . '/CollegeSection.php';
            } else {
              // program mode → wrap into a single college section with one program section
              $programs = [
                ['program' => ($program ?? []), 'templates' => ($program_templates ?? [])]
              ];
              include $partialsDir . '/CollegeSection.php';
            }
          ?>
        </main><!-- MAIN CLOSE -->

        <aside class="tb-flex-aside flex-shrink-0" id="tb-info-pane"><!-- ASIDE OPEN -->
          <div class="card">
            <div class="card-header"><strong>Details</strong></div>
            <div class="card-body">
              <div class="text-muted" id="tb-info-empty">Select a template to see details.</div>
              <dl class="row mt-3 mb-0 d-none" id="tb-info">
                <dt class="col-4">Title</dt>
                <dd class="col-8" id="tb-i-title"></dd>
                <dt class="col-4">Owner</dt>
                <dd class="col-8" id="tb-i-owner"></dd>
                <dt class="col-4">Updated</dt>
                <dd class="col-8" id="tb-i-updated"></dd>
                <dt class="col-4">Actions</dt>
                <dd class="col-8">
                  <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-primary" id="tb-open">Open</button>
                    <button class="btn btn-sm btn-outline-secondary" id="tb-duplicate">Duplicate</button>
                    <button class="btn btn-sm btn-warning"
                            id="tb-edit"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#tbEditModal"
                            style="display:none">
                      Edit
                    </button>
                  </div>

                  <script>
                    // Expose per-scope edit permissions for client logic
                    window.TB_PERMS = {
                      canEditSystem:  <?= !empty($canEditSystem)  ? 'true' : 'false' ?>,
                      canEditCollege: <?= !empty($canEditCollege) ? 'true' : 'false' ?>,
                      canEditProgram: <?= !empty($canEditProgram) ? 'true' : 'false' ?>,
                    };
                  </script>
                  <?php if (empty($canEditSystem) && empty($canEditCollege) && empty($canEditProgram)): ?>
                  <script>
                    // Remove the edit button entirely if user has no edit perms
                    (function(){ const b = document.getElementById('tb-edit'); if (b) b.remove(); })();
                  </script>
                  <?php endif; ?>
                </dd>
              </dl>
            </div>
          </div>
        </aside><!-- /ASIDE CLOSE -->

      </div><!-- FLEX WRAP CLOSE -->
      <?php
    }

    // CREATE MODAL (optional)
    if (!empty($canCreateGlobal) || !empty($canCreateCollege) || !empty($canCreateProgram)) {
    $globalAllowed   = !empty($canCreateGlobal);
    $collegeAllowed  = !empty($canCreateCollege);
    $programAllowed  = !empty($canCreateProgram);
    $courseAllowed   = !empty($canCreateCourse); // may be undefined; defaults false
    $colList         = $allColleges       ?? [];
    $progList        = $programsOfCollege ?? [];
    $defaultCollegeId= $defaultCollegeId  ?? null;

    include $partialsDir . '/CreateModal.php';

    if (function_exists('renderCreateModal')) {
      renderCreateModal(
        $ASSET_BASE,
        $globalAllowed,
        $collegeAllowed,
        $programAllowed,
        $courseAllowed,      // NEW
        $colList,
        $progList,
        $defaultCollegeId,   // NEW
        $esc
      );
    }
  }

    // EDIT MODAL (always render so the button works on any mode)
    $colList  = $allColleges       ?? [];
    $progList = $programsOfCollege ?? [];
    include $partialsDir . '/EditModal.php';
    if (function_exists('renderEditModal')) {
      renderEditModal(
        $ASSET_BASE,
        $colList,
        $progList,
        $esc
      );
    }
  ?>

</div><!-- /CONTAINER CLOSE -->
