<?php
// /app/Modules/SyllabusTemplates/Views/index.php
/** vars:
 * $mode: 'global-folders' | 'college' | 'program'
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
          $mode === 'global-folders' ? 'Select a college folder to view templates.'
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

  <!-- CSRF token for JS actions (duplicate, etc.) -->
  <span id="tb-csrf" data-token="<?= $esc($_SESSION['csrf_token'] ?? '') ?>" hidden></span>

  <?php
    $partialsDir = __DIR__ . '/partials';

    if ($mode === 'global-folders') {
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

                <dt class="col-4">Scope</dt>
                <dd class="col-8" id="tb-i-scope"></dd>

                <!-- College / Program / Course — hidden unless tile provides values -->
                <dt class="col-4 d-none" id="tb-i-college-dt">College</dt>
                <dd class="col-8 d-none" id="tb-i-college"></dd>

                <dt class="col-4 d-none" id="tb-i-program-dt">Program</dt>
                <dd class="col-8 d-none" id="tb-i-program"></dd>

                <dt class="col-4 d-none" id="tb-i-course-dt">Course</dt>
                <dd class="col-8 d-none" id="tb-i-course"></dd>

                <dt class="col-4">Status</dt>
                <dd class="col-8" id="tb-i-status"></dd>

                <dt class="col-4">Updated</dt>
                <dd class="col-8" id="tb-i-updated"></dd>

                <dt class="col-4">Actions</dt>
                <dd class="col-8">
                  <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-primary" id="tb-open">Open</button>

                    <!-- Use Template (formerly Duplicate) — same behavior, kept as outline secondary -->
                    <button class="btn btn-sm btn-outline-secondary"
                            id="tb-duplicate"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#tbDuplicateModal">
                      Use Template
                    </button>

                    <!-- Edit: should be filled like duplicate but filled (secondary filled) -->
                    <button class="btn btn-sm btn-secondary"
                            id="tb-edit"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#tbEditModal"
                            style="display:none">
                      Edit
                    </button>

                    <!-- Archive / Unarchive: same color as previous edit (yellowish) -->
                    <button class="btn btn-sm btn-warning" 
                            id="tb-archive" 
                            type="button"
                            style="display:none">
                      Archive
                    </button>

                    <!-- Delete: only shown when status is archived. Hidden by default. -->
                    <button class="btn btn-sm btn-danger d-none" 
                            id="tb-delete" 
                            type="button"
                            data-bs-toggle="modal" 
                            data-bs-target="#tbDeleteModal">
                      Delete
                    </button>
                  </div>

                  <script>
                    // Expose per-scope edit permissions for client logic
                    // prefer canEditGlobal (AAO) name; kept for clarity in JS
                    window.TB_PERMS = {
                      canEditGlobal:  <?= !empty($canEditGlobal)  ? 'true' : 'false' ?>,
                      canEditCollege: <?= !empty($canEditCollege) ? 'true' : 'false' ?>,
                      canEditProgram: <?= !empty($canEditProgram) ? 'true' : 'false' ?>,
                    };
                  </script>
                  <?php if (empty($canEditGlobal) && empty($canEditCollege) && empty($canEditProgram)): ?>
                  <script>
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
    include $partialsDir . '/DeleteModal.php';
    if (function_exists('renderEditModal')) {
      renderEditModal(
        $ASSET_BASE,
        $colList,
        $progList,
        $esc
      );
    }

    // DUPLICATE MODAL (same scope rules as Create)
    $globalAllowed  = !empty($canCreateGlobal);
    $collegeAllowed = !empty($canCreateCollege);
    $programAllowed = !empty($canCreateProgram);
    $colList        = $allColleges       ?? [];
    $progList       = $programsOfCollege ?? [];

    // include the function definition
    include $partialsDir . '/DuplicateModal.php';

    // Role-aware defaults for Duplicate modal (computed from *current* $user)
    $__role           = strtolower((string)($user['role_name'] ?? ''));
    $__defaultCollege = (int)($user['college_id'] ?? 0);
    $__defaultScope   = in_array($__role, ['dean','chair'], true) ? 'college' : '';
    $__lockCollege    = (in_array($__role, ['dean','chair'], true) && $__defaultCollege > 0);
?>
    <script>
      window.__TB_DUP_DEFAULTS = {
        scope: '<?= $esc($__defaultScope ?? '') ?>',
        college: '<?= (int)($__defaultCollege ?? 0) ?>',
        lock: '<?= !empty($__lockCollege) ? '1' : '0' ?>'
      };
      console.log('[TB probe] server defaults:', window.__TB_DUP_DEFAULTS);
    </script>
<?php
    // actually render the modal markup
    if (function_exists('renderDuplicateModal')) {
      renderDuplicateModal(
        $ASSET_BASE,
        $globalAllowed,
        $collegeAllowed,
        $programAllowed,
        $allColleges,
        $programsOfCollege,
        $esc,
        $__defaultScope,       // NEW
        $__defaultCollege,     // NEW
        $__lockCollege         // NEW
      );
    }
  ?>

</div><!-- /CONTAINER CLOSE -->
