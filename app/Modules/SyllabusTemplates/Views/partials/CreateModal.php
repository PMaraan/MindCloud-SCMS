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
  const wrapCol  = document.getElementById('tb-college-wrap');
  const wrapPrg  = document.getElementById('tb-program-wrap');
  const selCol   = document.getElementById('tb-college');
  const selPrg   = document.getElementById('tb-program');

  function updateVisibility() {
    const vSys = scopeSys && scopeSys.checked;
    const vCol = scopeCol && scopeCol.checked;
    const vPrg = scopePrg && scopePrg.checked;

    if (wrapCol) wrapCol.classList.toggle('d-none', !(vCol || vPrg));
    if (wrapPrg) wrapPrg.classList.toggle('d-none', !vPrg);
  }

  // College → Program dynamic load
  if (selCol && selPrg) {
    selCol.addEventListener('change', async function(){
      const cid = this.value;
      // reset program select
      selPrg.innerHTML = '<option value="">— Select program —</option>';

      if (!cid) return;

      try {
        const base = (typeof window.BASE_PATH === 'string') ? window.BASE_PATH : '';
        const url  = `${base}/dashboard?page=<?= $esc($pageKey) ?>&action=programs&department_id=${encodeURIComponent(cid)}`;
        const res  = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        if (Array.isArray(data)) {
          for (const p of data) {
            const opt = document.createElement('option');
            opt.value = p.program_id ?? '';
            opt.textContent = p.program_name ?? '';
            selPrg.appendChild(opt);
          }
        }
      } catch (e) {
        // swallow; keep UX simple
      }
    });
  }

  [scopeSys, scopeCol, scopePrg].forEach(el => el && el.addEventListener('change', updateVisibility));
  updateVisibility();
});
</script>
<?php
  }
}
