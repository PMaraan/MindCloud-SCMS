<?php
/**
 * Duplicate modal – create a new Template (or Syllabus later) from an existing template.
 * Frontend-only for now. Posts to ?action=duplicate in the future; we prevent default via JS for now.
 *
 * Scopes shown match Create modal permissions:
 *   - allowSystem, allowCollege, allowProgram booleans decide which radios appear.
 */
if (!function_exists('renderDuplicateModal')) {
  function renderDuplicateModal(
    string $ASSET_BASE,
    bool $allowSystem,
    bool $allowCollege,
    bool $allowProgram,
    array $colleges,
    array $programsOfCollege,
    callable $esc
  ): void {
    $pageKey = $GLOBALS['PAGE_KEY'] ?? 'syllabus-templates';
    $base = (defined('BASE_PATH') ? BASE_PATH : '');
    ?>
<?php
  // Use $GLOBALS so we don’t need to change the function args
  $__u = $GLOBALS['user'] ?? [];
  $__role = strtolower((string)($__u['role_name'] ?? ''));
  $__defaultScope = (in_array($__role, ['dean','chair'], true) ? 'college' : '');
  $__defaultCollegeId = (int)($__u['college_id'] ?? 0);
?>
<div class="modal fade"
    id="tbDuplicateModal"
    tabindex="-1"
    aria-hidden="true"
    aria-labelledby="tbDupLabel"
    data-no-reset
    data-default-scope="<?= htmlspecialchars($__defaultScope, ENT_QUOTES) ?>"
    data-default-college="<?= (int)$__defaultCollegeId ?>">
  <div class="modal-dialog">
    <form id="tb-dup-form"
            method="post"
            action="<?= $esc(defined('BASE_PATH') ? BASE_PATH : '') ?>/dashboard?page=<?= $esc($GLOBALS['PAGE_KEY'] ?? 'syllabus-templates') ?>&action=duplicate"
            class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tbDupLabel">Duplicate Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $esc($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="source_template_id" id="tb-d-src-id" value="">

        <!-- What to create -->
        <div class="mb-3">
          <label class="form-label d-block">Create as</label>
          <div class="d-flex gap-3 flex-wrap">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="make_type" id="tb-d-make-template" value="template" checked>
              <label class="form-check-label" for="tb-d-make-template">New Template from this template</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="make_type" id="tb-d-make-syllabus" value="syllabus" disabled>
              <label class="form-check-label" for="tb-d-make-syllabus">New Syllabus from this template</label>
            </div>
          </div>
          <div class="form-text">Syllabus creation will be wired soon.</div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="tb-d-title">Title <span class="text-danger">*</span></label>
          <input type="text" name="title" id="tb-d-title" class="form-control" required maxlength="255" aria-label="Title" title="Title">
        </div>

        <!-- Scope radios -->
        <div class="mb-3">
          <label class="form-label d-block">Scope <span class="text-danger">*</span></label>
          <?php if ($allowSystem): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-d-scope-system" value="system">
              <label class="form-check-label" for="tb-d-scope-system">System / Global</label>
            </div>
          <?php endif; ?>
          <?php if ($allowCollege): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-d-scope-college" value="college">
              <label class="form-check-label" for="tb-d-scope-college">College</label>
            </div>
          <?php endif; ?>
          <?php if ($allowProgram): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-d-scope-program" value="program">
              <label class="form-check-label" for="tb-d-scope-program">Program</label>
            </div>
          <?php endif; ?>
          <?php if ($allowProgram): /* course scope only for program-capable roles */ ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-d-scope-course" value="course">
              <label class="form-check-label" for="tb-d-scope-course">Course</label>
            </div>
          <?php endif; ?>
        </div>

        <!-- College/Program/Course cascades -->
        <div class="mb-3 d-none" id="tb-d-college-wrap">
          <label class="form-label" for="tb-d-college">College <span class="text-danger">*</span></label>
          <select name="college_id" id="tb-d-college" class="form-select" aria-label="College" title="College">
            <option value="">— Select college —</option>
            <?php foreach ($colleges as $c): ?>
              <option value="<?= (int)($c['college_id'] ?? 0) ?>">
                <?= $esc(($c['short_name'] ?? '').' — '.($c['college_name'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3 d-none" id="tb-d-program-wrap">
          <label class="form-label" for="tb-d-program">Program <span class="text-danger">*</span></label>
          <select name="program_id" id="tb-d-program" class="form-select" aria-label="Program" title="Program">
            <option value="">— Select program —</option>
            <?php foreach ($programsOfCollege as $p): ?>
              <option value="<?= (int)($p['program_id'] ?? 0) ?>">
                <?= $esc(($p['program_name'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3 d-none" id="tb-d-course-wrap">
          <label class="form-label" for="tb-d-course">Course <span class="text-danger">*</span></label>
          <select name="course_id" id="tb-d-course" class="form-select" aria-label="Course" title="Course">
            <option value="">— Select course —</option>
          </select>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Duplicate</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // Toggle visibility based on scope (same rules as Create/Edit)
  const rSys = document.getElementById('tb-d-scope-system');
  const rCol = document.getElementById('tb-d-scope-college');
  const rPrg = document.getElementById('tb-d-scope-program');
  const rCrs = document.getElementById('tb-d-scope-course');

  const wrapCol = document.getElementById('tb-d-college-wrap');
  const wrapPrg = document.getElementById('tb-d-program-wrap');
  const wrapCrs = document.getElementById('tb-d-course-wrap');

  function updateVis() {
    const vSys = rSys && rSys.checked;
    const vCol = rCol && rCol.checked;
    const vPrg = rPrg && rPrg.checked;
    const vCrs = rCrs && rCrs.checked;

    if (wrapCol) wrapCol.classList.toggle('d-none', !(vCol || vPrg || vCrs));
    if (wrapPrg) wrapPrg.classList.toggle('d-none', !(vPrg || vCrs));
    if (wrapCrs) wrapCrs.classList.toggle('d-none', !vCrs);

    // Required flags
    const selCol = document.getElementById('tb-d-college');
    const selPrg = document.getElementById('tb-d-program');
    const selCrs = document.getElementById('tb-d-course');

    if (selCol) selCol.required = (vCol || vPrg || vCrs);
    if (selPrg) selPrg.required = (vPrg || vCrs);
    if (selCrs) selCrs.required = vCrs;
  }

  [rSys, rCol, rPrg, rCrs].forEach(el => el && el.addEventListener('change', updateVis));
  updateVis();
});
</script>
<?php
  }
}
