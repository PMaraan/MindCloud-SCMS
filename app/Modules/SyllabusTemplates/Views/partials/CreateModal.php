<?php
/**
 * /app/Modules/SyllabusTemplates/Views/partials/CreateModal.php
 * “New Template” modal (scope: system/college/program).
 */
// /app/Modules/SyllabusTemplates/Views/partials/CreateModal.php
if (!function_exists('renderCreateModal')) {
  function renderCreateModal(
    string $ASSET_BASE,
    bool $allowSystem,
    bool $allowCollege,
    bool $allowProgram,
    bool $allowCourse,              // NEW
    array $colleges,
    array $programsOfCollege,
    ?int $defaultCollegeId,         // NEW
    callable $esc
  ): void {
    // Page key comes from parent view; fall back to default if not set
    $pageKey = $GLOBALS['PAGE_KEY'] ?? 'syllabus-templates';
    $base = (defined('BASE_PATH') ? BASE_PATH : '');
    ?>
<div class="modal fade" id="tbCreateModal" tabindex="-1" aria-hidden="true" aria-labelledby="tbCreateLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= $esc($base) ?>/dashboard?page=<?= $esc($pageKey) ?>&action=create" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tbCreateLabel">New Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $esc($_SESSION['csrf_token'] ?? '') ?>"/>

        <div class="mb-3">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" required maxlength="255">
        </div>

        <div class="mb-3">
          <label class="form-label d-block">Scope <span class="text-danger">*</span></label>
          <?php if ($allowSystem): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-scope-global" value="global" checked>
              <label class="form-check-label" for="tb-scope-global">Global</label>
            </div>
          <?php endif; ?>

          <?php if ($allowCollege): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-scope-college" value="college" <?= $allowSystem ? '' : 'checked' ?>>
              <label class="form-check-label" for="tb-scope-college">College</label>
            </div>
          <?php endif; ?>

          <?php if ($allowProgram): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-scope-program" value="program"
                    <?= (!$allowSystem && !$allowCollege) ? 'checked' : '' ?>>
              <label class="form-check-label" for="tb-scope-program">Program</label>
            </div>
          <?php endif; ?>

          <?php if ($allowCourse): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-scope-course" value="course">
              <label class="form-check-label" for="tb-scope-course">Course</label>
            </div>
          <?php endif; ?>
        </div>

        <div class="mb-3 d-none" id="tb-college-wrap">
          <label class="form-label" for="tb-college">College <span class="text-danger">*</span></label>
          <select name="college_id" id="tb-college" class="form-select" aria-label="College" title="College">
            <option value="">— Select college —</option>
            <?php foreach ($colleges as $c): 
                  $cid = (int)($c['college_id'] ?? 0);
                  $sel = ($defaultCollegeId && $cid === (int)$defaultCollegeId) ? 'selected' : '';
            ?>
              <option value="<?= $cid ?>" <?= $sel ?>>
                <?= $esc(($c['short_name'] ?? '').' — '.($c['college_name'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3 d-none" id="tb-program-wrap">
          <label class="form-label" for="tb-program">Program</label>Program <span class="text-danger">*</span></label>
          <select name="program_id" id="tb-program" class="form-select" aria-label="Program" title="Program">
            <option value="">— Select program —</option>
            <?php foreach ($programsOfCollege as $p): ?>
              <option value="<?= (int)($p['program_id'] ?? 0) ?>">
                <?= $esc(($p['program_name'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Programs list auto-filters based on the selected college when available.</div>
        </div>

        <div class="mb-3 d-none" id="tb-course-wrap">
          <label class="form-label" for="tb-course">Course <span class="text-danger">*</span></label>
          <select name="course_id" id="tb-course" class="form-select" aria-label="Course" title="Course">
            <option value="">— Select course —</option>
            <!-- Will be filled based on selected Program -->
          </select>
        </div>

        <div class="mb-3 d-none" id="tb-course-wrap">
          <label class="form-label">Course <span class="text-danger">*</span></label>
          <select name="course_id" id="tb-course" class="form-select">
            <option value="">— Select course —</option>
            <!-- options loaded dynamically based on Program -->
          </select>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const scopeSys = document.getElementById('tb-scope-system');
  const scopeCol = document.getElementById('tb-scope-college');
  const scopePrg = document.getElementById('tb-scope-program');
  const scopeCrs = document.getElementById('tb-scope-course');

  const wrapCol  = document.getElementById('tb-college-wrap');
  const wrapPrg  = document.getElementById('tb-program-wrap');
  const wrapCrs  = document.getElementById('tb-course-wrap');

  const selCol   = document.getElementById('tb-college');
  const selPrg   = document.getElementById('tb-program');
  const selCrs   = document.getElementById('tb-course');

  function updateVisibility() {
    const vSys = scopeSys && scopeSys.checked;
    const vCol = scopeCol && scopeCol.checked;
    const vPrg = scopePrg && scopePrg.checked;
    const vCrs = scopeCrs && scopeCrs.checked;

    if (wrapCol) wrapCol.classList.toggle('d-none', !(vCol || vPrg || vCrs));
    if (wrapPrg) wrapPrg.classList.toggle('d-none', !(vPrg || vCrs));
    if (wrapCrs) wrapCrs.classList.toggle('d-none', !vCrs);

    // required flags by scope
    if (selCol) selCol.toggleAttribute('required', (vCol || vPrg || vCrs));
    if (selPrg) selPrg.toggleAttribute('required', (vPrg || vCrs));
    if (selCrs) selCrs.toggleAttribute('required', vCrs);
  }

  // College → Program dynamic load (existing)
  if (selCol && selPrg) {
    selCol.addEventListener('change', async function(){
      const cid = this.value;
      // reset program
      selPrg.innerHTML = '<option value="">— Select program —</option>';
      // reset course
      if (selCrs) selCrs.innerHTML = '<option value="">— Select course —</option>';

      if (!cid) return;
      try {
        const base = (typeof window.BASE_PATH === 'string') ? window.BASE_PATH : '';
        const url  = `${base}/api/syllabus-templates/programs?department_id=${encodeURIComponent(cid)}`;
        const res  = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if (!res.ok) return;
        const data = await res.json();
        if (Array.isArray(data?.programs)) {
          for (const p of data.programs) {
            const opt = document.createElement('option');
            opt.value = p.id ?? '';
            opt.textContent = p.label ?? '';
            selPrg.appendChild(opt);
          }
        }
      } catch {}
    });
  }

  // Program → Course dynamic load (new)
  if (selPrg && selCrs) {
    selPrg.addEventListener('change', async function(){
      const pid = this.value;
      selCrs.innerHTML = '<option value="">— Select course —</option>';
      if (!pid) return;
      try {
        const base = (typeof window.BASE_PATH === 'string') ? window.BASE_PATH : '';
        const url  = `${base}/api/syllabus-templates/courses?program_id=${encodeURIComponent(pid)}`;
        const res  = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if (!res.ok) return;
        const data = await res.json();
        if (Array.isArray(data?.courses)) {
          for (const c of data.courses) {
            const opt = document.createElement('option');
            opt.value = c.id ?? '';
            opt.textContent = c.label ?? '';
            selCrs.appendChild(opt);
          }
        }
      } catch {}
    });
  }

  // If default college is present, preselect it and trigger program load (for deans)
  <?php if (!empty($defaultCollegeId)): ?>
    if (selCol) {
      selCol.value = "<?= (int)$defaultCollegeId ?>";
      selCol.dispatchEvent(new Event('change'));
    }
    // default scope for deans is College; check it if system is not allowed
    <?php if (!$allowSystem && $allowCollege): ?>
      const r = document.getElementById('tb-scope-college');
      if (r) { r.checked = true; }
    <?php endif; ?>
  <?php endif; ?>

  [scopeSys, scopeCol, scopePrg, scopeCrs].forEach(el => el && el.addEventListener('change', updateVisibility));
  updateVisibility();
});
</script>
<?php
  }
}
