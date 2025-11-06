<?php
/**
 * /app/Modules/SyllabusTemplates/Views/partials/EditModal.php
 * Edit modal for syllabus template DB fields (not the JSON content).
 * Autofilled from currently selected tile’s data-* attributes.
 *
 * Posts to ?action=edit (handled by SyllabusTemplatesController@edit).
 */
if (!function_exists('renderEditModal')) {
  function renderEditModal(string $ASSET_BASE, array $colleges, array $programsOfCollege, callable $esc): void {
    $base = (defined('BASE_PATH') ? BASE_PATH : '');
    $pageKey = $GLOBALS['PAGE_KEY'] ?? 'syllabus-templates';
    ?>
<div class="modal fade" id="tbEditModal" tabindex="-1" aria-hidden="true" aria-labelledby="tbEditLabel" data-no-reset>
  <div class="modal-dialog">
    <form method="post" action="<?= $esc($base) ?>/dashboard?page=<?= $esc($pageKey) ?>&action=edit" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tbEditLabel">Edit Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $esc($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="template_id" id="tb-e-id">

        <div class="mb-3">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input type="text" name="title" id="tb-e-title" class="form-control" maxlength="255" required>
        </div>

        <div class="mb-3">
          <label class="form-label d-block">Scope <span class="text-danger">*</span></label>
          <div class="d-flex gap-3 flex-wrap">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-e-scope-system" value="system">
              <label class="form-check-label" for="tb-e-scope-system">System / Global</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-e-scope-college" value="college">
              <label class="form-check-label" for="tb-e-scope-college">College</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-e-scope-program" value="program">
              <label class="form-check-label" for="tb-e-scope-program">Program</label>
            </div>
          </div>
        </div>

        <div class="mb-3" id="tb-e-college-wrap">
            <label class="form-label">College</label>
            <select name="owner_department_id" id="tb-e-college" class="form-select">
                <option value="">— Select college —</option>
                <?php foreach ($colleges as $c): ?>
                <option value="<?= (int)($c['college_id'] ?? 0) ?>">
                    <?= $esc(($c['short_name'] ?? '').' — '.($c['college_name'] ?? '')) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3" id="tb-e-program-wrap">
            <label class="form-label">Program</label>
            <select name="program_id" id="tb-e-program" class="form-select">
                <option value="">— Select program —</option>
                <?php foreach ($programsOfCollege as $p): ?>
                <option value="<?= (int)($p['program_id'] ?? 0) ?>">
                    <?= $esc(($p['program_name'] ?? '')) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row g-3">
            <div class="col-sm-12">
                <label class="form-label">Course</label>
                <select name="course_id" id="tb-e-course" class="form-select">
                <option value="">— Select course —</option>
                <!-- Options are loaded dynamically based on selected Program -->
                </select>
            </div>
        </div>

        <!-- Keep server happy if it still reads owner_program_id; send blank -->
        <input type="hidden" name="owner_program_id" value="">

        <div class="row g-3 mt-1">
          <div class="col-sm-6">
            <label class="form-label">Version</label>
            <input type="text" name="version" id="tb-e-version" class="form-control" maxlength="10" placeholder="v1.0" readonly>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Status</label>
            <select name="status" id="tb-e-status" class="form-select">
              <option value="draft">draft</option>
              <option value="active">active</option>
              <option value="archived">archived</option>
            </select>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

<script>
// Scope-aware visibility
document.addEventListener('DOMContentLoaded', function(){
  const sys = document.getElementById('tb-e-scope-system');
  const col = document.getElementById('tb-e-scope-college');
  const prg = document.getElementById('tb-e-scope-program');
  const wrapCol = document.getElementById('tb-e-college-wrap');
  const wrapPrg = document.getElementById('tb-e-program-wrap');

  function updateVis(){
    const vSys = sys && sys.checked;
    const vCol = col && col.checked;
    const vPrg = prg && prg.checked;
    if (wrapCol) wrapCol.classList.toggle('d-none', !(vCol || vPrg));
    if (wrapPrg) wrapPrg.classList.toggle('d-none', !vPrg);
  }
  [sys,col,prg].forEach(el => el && el.addEventListener('change', updateVis));
  updateVis();
});
</script>
<?php
  }
}
