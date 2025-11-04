<?php
/**
 * /app/Modules/Syllabi/Views/partials/CreateModal.php
 * “New Syllabus” modal (college → program → course selection).
 *
 * Expects from the parent view (abstract ok for now):
 *   - $ASSET_BASE (string)
 *   - $colleges (array)            // [{ college_id, short_name, college_name }, ...]
 *   - $programsOfCollege (array)   // [{ program_id, program_name, college_id }, ...] (optionally prefiltered)
 *   - $coursesOfProgram (array)    // [{ course_id, course_code, course_name, program_id? }, ...] (optionally prefiltered)
 *   - $esc (callable)
 *
 * Posts to: /dashboard?page=syllabi&action=create
 * Fields posted:
 *   - csrf_token
 *   - college_id (required)
 *   - program_id (required)
 *   - course_id (required)
 *   - version   (optional, varchar(10) fits your table)
 */
if (!function_exists('renderSyllabiCreateModal')) {
  function renderSyllabiCreateModal(
    string $ASSET_BASE,
    array $colleges,
    array $programsOfCollege,
    array $coursesOfProgram,
    callable $esc
  ): void {
    $base    = (defined('BASE_PATH') ? BASE_PATH : '');
    $pageKey = $GLOBALS['PAGE_KEY'] ?? 'syllabi';
    ?>
<div class="modal fade" id="syCreateModal" tabindex="-1" aria-hidden="true" aria-labelledby="syCreateLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= $esc($base) ?>/dashboard?page=<?= $esc($pageKey) ?>&action=create" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="syCreateLabel">New Syllabus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $esc($_SESSION['csrf_token'] ?? '') ?>"/>

        <div class="mb-3">
          <label class="form-label">College <span class="text-danger">*</span></label>
          <select name="college_id" id="sy-college" class="form-select" required>
            <option value="">— Select college —</option>
            <?php foreach ($colleges as $c): ?>
              <option value="<?= (int)($c['college_id'] ?? 0) ?>">
                <?= $esc(($c['short_name'] ?? '').' — '.($c['college_name'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Program <span class="text-danger">*</span></label>
          <select name="program_id" id="sy-program" class="form-select" required>
            <option value="">— Select program —</option>
            <?php foreach ($programsOfCollege as $p): ?>
              <option value="<?= (int)($p['program_id'] ?? 0) ?>"
                      data-college-id="<?= (int)($p['college_id'] ?? 0) ?>">
                <?= $esc($p['program_name'] ?? '') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Program options can auto-filter after you choose a college.</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Course <span class="text-danger">*</span></label>
          <select name="course_id" id="sy-course" class="form-select" required>
            <option value="">— Select course —</option>
            <?php foreach ($coursesOfProgram as $crs): ?>
              <option value="<?= (int)($crs['course_id'] ?? 0) ?>"
                      data-program-id="<?= (int)($crs['program_id'] ?? 0) ?>">
                <?= $esc(($crs['course_code'] ?? '').' — '.($crs['course_name'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Course options can auto-filter after you choose a program.</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Version (optional)</label>
          <input type="text" name="version" class="form-control" maxlength="10" placeholder="e.g., v1.0">
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
  const selCollege = document.getElementById('sy-college');
  const selProgram = document.getElementById('sy-program');
  const selCourse  = document.getElementById('sy-course');

  // Simple client-side filtering (works with pre-rendered option datasets)
  function filterProgramsByCollege(collegeId) {
    Array.from(selProgram.options).forEach(opt => {
      if (!opt.value) return; // keep placeholder
      const cid = opt.getAttribute('data-college-id') || '';
      opt.hidden = !!(collegeId && cid && cid !== String(collegeId));
      if (opt.hidden && opt.selected) opt.selected = false;
    });
  }

  function filterCoursesByProgram(programId) {
    Array.from(selCourse.options).forEach(opt => {
      if (!opt.value) return; // keep placeholder
      const pid = opt.getAttribute('data-program-id') || '';
      opt.hidden = !!(programId && pid && pid !== String(programId));
      if (opt.hidden && opt.selected) opt.selected = false;
    });
  }

  if (selCollege) {
    selCollege.addEventListener('change', function(){
      const cid = this.value;
      filterProgramsByCollege(cid);
      // Reset courses when college changes (since program will change)
      filterCoursesByProgram('');
    });
  }
  if (selProgram) {
    selProgram.addEventListener('change', function(){
      const pid = this.value;
      filterCoursesByProgram(pid);
    });
  }
});
</script>
<?php
  }
}
