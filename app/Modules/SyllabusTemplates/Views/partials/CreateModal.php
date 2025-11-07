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
    array $colleges,
    array $programsOfCollege,
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
              <input class="form-check-input" type="radio" name="scope" id="tb-scope-system" value="system" checked>
              <label class="form-check-label" for="tb-scope-system">System / Global</label>
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
              <input class="form-check-input" type="radio" name="scope" id="tb-scope-program" value="program" <?= (!$allowSystem && !$allowCollege) ? 'checked' : '' ?>>
              <label class="form-check-label" for="tb-scope-program">Program</label>
            </div>
          <?php endif; ?>
          <?php /* NEW: Course scope */ ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-scope-course" value="course" <?= (!$allowSystem && !$allowCollege && !$allowProgram) ? 'checked' : '' ?>>
              <label class="form-check-label" for="tb-scope-course">Course</label>
            </div>
        </div>

        <div class="mb-3 d-none" id="tb-college-wrap">
          <label class="form-label">College <span class="text-danger">*</span></label>
          <select name="college_id" id="tb-college" class="form-select">
            <option value="">— Select college —</option>
            <?php foreach ($colleges as $c): ?>
              <option value="<?= (int)($c['college_id'] ?? 0) ?>">
                <?= $esc(($c['short_name'] ?? '').' — '.($c['college_name'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3 d-none" id="tb-program-wrap">
          <label class="form-label">Program <span class="text-danger">*</span></label>
          <select name="program_id" id="tb-program" class="form-select">
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

  function show(el, on){ if (el) el.classList.toggle('d-none', !on); }
  function req(el, on){ if (!el) return; if (on) el.setAttribute('required','required'); else el.removeAttribute('required'); }

  function scope(){
    if (scopeCrs && scopeCrs.checked) return 'course';
    if (scopePrg && scopePrg.checked) return 'program';
    if (scopeCol && scopeCol.checked) return 'college';
    return 'system';
  }

  function updateVisibility(){
    const s = scope();
    // reset
    show(wrapCol,false); show(wrapPrg,false); show(wrapCrs,false);
    req(selCol,false); req(selPrg,false); req(selCrs,false);

    if (s === 'system') return;
    if (s === 'college'){ show(wrapCol,true); req(selCol,true); return; }
    if (s === 'program'){ show(wrapCol,true); show(wrapPrg,true); req(selCol,true); req(selPrg,true); return; }
    if (s === 'course'){ show(wrapCol,true); show(wrapPrg,true); show(wrapCrs,true); req(selCol,true); req(selPrg,true); req(selCrs,true); return; }
  }

  [scopeSys,scopeCol,scopePrg,scopeCrs].forEach(el => el && el.addEventListener('change', updateVisibility));

  function getBase(){
    if (typeof window.BASE_PATH === 'string') return window.BASE_PATH;
    const p = window.location.pathname; const cut = p.indexOf('/dashboard'); return cut>-1 ? p.slice(0,cut) : '';
  }
  async function safeGetJSON(url){
    const r = await fetch(url, { headers: { 'Accept':'application/json' }, credentials:'same-origin', cache:'no-store' });
    if (!r.ok) throw new Error('HTTP '+r.status);
    const ct = (r.headers.get('content-type')||'').toLowerCase();
    if (!ct.includes('application/json')){ await r.text(); throw new Error('Non-JSON'); }
    return r.json();
  }
  function fillSelect(sel, items, placeholder){
    if (!sel) return;
    sel.innerHTML = '';
    const o0 = document.createElement('option');
    o0.value = ''; o0.textContent = placeholder || '— Select —';
    sel.appendChild(o0);
    (items||[]).forEach(it => {
      const o = document.createElement('option');
      o.value = String(it.id ?? it.program_id ?? it.course_id ?? '');
      o.textContent = String(it.label ?? it.program_name ?? it.course_name ?? '');
      sel.appendChild(o);
    });
  }

  // College -> Programs
  if (selCol && selPrg){
    selCol.addEventListener('change', async function(){
      const cid = Number(this.value);
      fillSelect(selPrg, [], '— Select program —');
      fillSelect(selCrs, [], '— Select course —');
      if (!Number.isFinite(cid) || cid<=0) return;
      try{
        const url = `${getBase()}/api/syllabus-templates/programs?department_id=${encodeURIComponent(cid)}`;
        const data = await safeGetJSON(url);
        const items = Array.isArray(data?.programs) ? data.programs : data; // accepts either shape
        fillSelect(selPrg, items, '— Select program —');
      }catch{}
    });
  }

  // Program -> Courses
  if (selPrg && selCrs){
    selPrg.addEventListener('change', async function(){
      const pid = Number(this.value);
      fillSelect(selCrs, [], '— Select course —');
      if (!Number.isFinite(pid) || pid<=0) return;
      try{
        const url = `${getBase()}/api/syllabus-templates/courses?program_id=${encodeURIComponent(pid)}`;
        const data = await safeGetJSON(url);
        const items = Array.isArray(data?.courses) ? data.courses : data; // accepts either shape
        fillSelect(selCrs, items, '— Select course —');
      }catch{}
    });
  }

  // Initial state
  updateVisibility();
});
</script>
<?php
  }
}
