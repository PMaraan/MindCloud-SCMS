<?php
// /app/Modules/Syllabi/Views/partials/EditModal.php
/**
 * Placeholder Edit modal.
 */


if (!function_exists('renderSyllabiEditModal')) {
  function renderSyllabiEditModal(
    string $ASSET_BASE,
    array $colleges,
    array $programs,
    array $courses,
    callable $esc,
    bool $lockCollege = false,
    array $college = []
  ): void {
    $base    = (defined('BASE_PATH') ? BASE_PATH : '');
    $pageKey = $GLOBALS['PAGE_KEY'] ?? 'syllabi';
    $csrf = $esc($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<div class="modal fade" id="syEditModal" tabindex="-1" aria-hidden="true" aria-labelledby="syEditLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= $base ?>/dashboard?page=syllabi&action=edit" class="modal-content">
      <input type="hidden" name="syllabus_id" id="sy-e-id">
      <div class="modal-header">
        <h5 class="modal-title" id="syEditLabel">Edit Syllabus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <!-- Title -->
        <div class="mb-3">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="title" id="sy-e-title" maxlength="255" required>
        </div>
        <!-- College -->
        
        <div class="mb-3" id="sy-e-college-wrap">
          <label class="form-label" for="sy-e-college">College <span class="text-danger">*</span></label>
          <select
            name="college_id"
            id="sy-e-college"
            class="form-select"
            data-locked="<?= $lockCollege ? '1' : '0' ?>"
            data-locked-value="<?= $lockCollege ? (int)$college['college_id'] ?? '' : '' ?>"
          >
            <option value="">— Select college —</option>
            <?php foreach ($colleges as $c): ?>
              <option value="<?= (int)($c['college_id'] ?? 0) ?>"
                      <?= $lockCollege && (int)($c['college_id'] ?? 0) === (int)$college['college_id'] ? 'selected' : '' ?>>
                <?= $esc(($c['short_name'] ?? '').' — '.($c['college_name'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="college_id" value="<?= $lockCollege ? (int)$college['college_id'] ?? '' : '' ?>">
        </div>
        <!-- Program -->
        <div class="mb-3" id="sy-e-program-wrap">
          <label class="form-label" for="sy-e-program">Programs <span class="text-danger">*</span></label>
          <select name="program_ids[]" id="sy-e-program" class="form-select" aria-label="Programs" title="Programs" multiple size="6">
            <?php foreach ($programs as $p): ?>
              <option value="<?= (int)($p['program_id'] ?? 0) ?>"
                      data-college-id="<?= (int)($p['college_id'] ?? 0) ?>">
                <?= $esc($p['program_name'] ?? '') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Hold Ctrl (Windows) or Cmd (macOS) to select multiple programs.</div>
          <div id="sy-e-program-hidden" class="d-none"></div>
        </div>
        <!-- Course -->
        <div class="mb-3" id="sy-e-course-wrap">
          <label class="form-label" for="sy-e-course">Course <span class="text-danger">*</span></label>
          <select name="course_id" id="sy-e-course" class="form-select" aria-label="Course" title="Course">
            <option value="">— Select course —</option>
            <?php foreach ($courses as $crs): ?>
              <option value="<?= (int)($crs['course_id'] ?? 0) ?>"
                      data-program-id="<?= (int)($crs['program_id'] ?? 0) ?>">
                <?= $esc(($crs['course_code'] ?? '').' — '.($crs['course_name'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Version and Status -->
        <div class="row g-3 mt-1">
          <div class="col-sm-6">
            <label class="form-label">Version</label>
            <input type="text" name="version" id="sy-e-version" class="form-control" maxlength="10" placeholder="v1.0" readonly>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Status</label>
            <select name="status" id="sy-e-status" class="form-select">
              <option value="draft">draft</option>
              <option value="active">active</option>
              <option value="archived">archived</option>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>
<?php
  }
}