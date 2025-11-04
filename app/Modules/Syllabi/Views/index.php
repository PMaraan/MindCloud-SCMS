<?php
// /app/Modules/Syllabi/Views/index.php
/**
 * View: Syllabi Index (aligned to Syllabus Templates layout)
 *
 * Expected vars from controller:
 *   - $mode: 'system-folders' | 'college' | 'program'
 *   - $ASSET_BASE, $esc, $user, $role
 *   - When $mode==='system-folders': $colleges
 *   - When $mode==='college':
 *        $college, $general, $programs,
 *        optional: $showBackToFolders, $canCreateSyllabus, $allColleges, $programsOfCollege
 *   - When $mode==='program':
 *        $college, $general, $program, $program_syllabi, $canCreateSyllabus
 *
 * Notes:
 *   - Mirrors /SyllabusTemplates/Views/index.php structure for consistency.
 *   - Uses Syllabi partials:
 *        /partials/FoldersList.php
 *        /partials/CollegeSection.php  (internally includes ProgramSections + Grid)
 *   - Info pane on the right matches the “Details” card pattern.
 */
$base = defined('BASE_PATH') ? BASE_PATH : '';
$PAGE_KEY = 'syllabi';
?>
<div><!-- CONTAINER OPEN -->

  <!-- PAGE HEADER -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-0">Syllabi</h2>
      <div class="text-muted small">
        <?= $esc(
          $mode === 'system-folders' ? 'Select a college folder to view syllabi.'
          : ($mode === 'college' ? 'College-wide syllabi + Program syllabi for this college.'
          : 'College-wide syllabi + my program syllabi.')
        ) ?>
      </div>
    </div>

    <div class="d-flex gap-2">
      <?php if (!empty($showBackToFolders)): ?>
        <a class="btn btn-outline-secondary" href="<?= $esc($base.'/dashboard?page='.$PAGE_KEY) ?>">
          <i class="bi bi-arrow-left"></i> All Colleges
        </a>
      <?php endif; ?>
      <?php if (!empty($canCreateSyllabus)): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#syCreateModal">
          <i class="bi bi-file-earmark-plus"></i> New Syllabus
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
      // FLEX LAYOUT: MAIN + ASIDE (match Templates)
      ?>
      <div class="tb-flex-wrap d-block d-md-flex gap-3 align-items-start"><!-- FLEX WRAP OPEN -->

        <main class="tb-flex-main flex-grow-1"><!-- MAIN OPEN -->
          <?php
            if ($mode === 'college') {
              // expects: $college, $general, $programs
              include $partialsDir . '/CollegeSection.php';
            } else {
              // program mode → wrap into a single college section with one program section
              // Syllabi version uses ['program' => {...}, 'syllabi' => [...]]
              $programs = [
                ['program' => ($program ?? []), 'syllabi' => ($program_syllabi ?? [])]
              ];
              include $partialsDir . '/CollegeSection.php';
            }
          ?>
        </main><!-- MAIN CLOSE -->

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
                    <a class="btn btn-sm btn-primary" id="sy-open" href="#" role="button">Open</a>
                    <!-- Optional future action:
                    <button class="btn btn-sm btn-outline-secondary" id="sy-duplicate" type="button">Duplicate</button>
                    -->
                  </div>
                </dd>
              </dl>
            </div>
          </div>
        </aside><!-- /ASIDE CLOSE -->

      </div><!-- FLEX WRAP CLOSE -->
      <?php
    }

    // CREATE MODAL (optional; your real UI can replace this)
    if (!empty($canCreateSyllabus)) {
      // You can render a create modal here when ready, mirroring Templates’ CreateModal
      if (file_exists($partialsDir . '/CreateModal.php')) {
        include $partialsDir . '/CreateModal.php';
      }
    }
  ?>

</div><!-- /CONTAINER CLOSE -->

<script>
/**
 * Minimal pane wiring (non-invasive). Mirrors the Templates info pane behavior.
 * Reads data-* from tiles created in /partials/Grid.php (Syllabi version).
 */
(function(){
  const paneEmpty = document.getElementById('sy-info-empty');
  const pane      = document.getElementById('sy-info');
  if (!paneEmpty || !pane) return;

  function selectTile(card){
    const title   = card.getAttribute('data-title') || 'Untitled';
    const program = card.getAttribute('data-program') || '';
    const updated = card.getAttribute('data-updated') || '';
    const status  = card.getAttribute('data-status') || '';
    const id      = parseInt(card.getAttribute('data-syllabus-id') || '0', 10);

    document.getElementById('sy-i-title').textContent   = title;
    document.getElementById('sy-i-program').textContent = program;
    document.getElementById('sy-i-updated').textContent = updated;
    document.getElementById('sy-i-status').textContent  = status;

    const openBtn = document.getElementById('sy-open');
    if (openBtn && id > 0) {
      const base = "<?= $esc($base) ?>";
      openBtn.href = base + '/dashboard?page=rteditor&syllabusId=' + id;
    }

    paneEmpty.classList.add('d-none');
    pane.classList.remove('d-none');
  }

  // Delegate clicks from tiles
  document.addEventListener('click', (e) => {
    const card = e.target.closest?.('.sy-tile.card');
    if (!card) return;
    // Prevent the outer <a> from navigating if we’re only updating the pane
    const wrapperLink = e.target.closest('a');
    if (wrapperLink && wrapperLink.contains(card)) {
      e.preventDefault();
    }
    selectTile(card);
  });

  // Keyboard focus/enter to support accessibility
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    const card = document.activeElement?.classList?.contains('sy-tile') ? document.activeElement : null;
    if (!card) return;
    e.preventDefault();
    selectTile(card);
  });
})();
</script>
