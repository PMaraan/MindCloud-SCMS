<?php
/**
 * /app/Modules/SyllabusTemplates/Views/partials/EditModal.php
 * Edit modal for syllabus template DB fields (not the JSON content).
 * Autofilled from currently selected tile’s data-* attributes.
 *
 * Posts to ?action=edit (handled by SyllabusTemplatesController@edit).
 */
if (!function_exists('renderEditModal')) {
  function renderEditModal(
    string $ASSET_BASE,
    array $colleges,
    array $programsOfCollege,
    ?int $defaultCollegeId,
    bool $lockCollege,
    bool $allowGlobalScope,
    callable $esc
  ): void {
    $base    = defined('BASE_PATH') ? BASE_PATH : '';
    $pageKey = $GLOBALS['PAGE_KEY'] ?? 'syllabus-templates';
    ?>
<div class="modal fade" id="tbEditModal" tabindex="-1" aria-hidden="true" aria-labelledby="tbEditLabel" data-no-reset>
  <div class="modal-dialog">
    <form method="post"
          action="<?= $esc($base) ?>/dashboard?page=<?= $esc($pageKey) ?>&action=edit"
          class="modal-content"
          data-default-college="<?= (int)($defaultCollegeId ?? 0) ?>"
          data-lock-college="<?= $lockCollege ? '1' : '0' ?>">
      <div class="modal-header">
        <h5 class="modal-title" id="tbEditLabel">Edit Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $esc($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="template_id" id="tb-e-id">
        <!-- Title -->
        <div class="mb-3">
          <label class="form-label">Title </label>
          <input type="text" name="title" id="tb-e-title" class="form-control" maxlength="255" required>
        </div>
        <!-- Scope -->
        <div class="mb-3">
          <label class="form-label d-block">Scope <span class="text-danger">*</span></label>
          <div class="d-flex gap-3 flex-wrap">
          <?php if ($allowGlobalScope): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-e-scope-global" value="global">
              <label class="form-check-label" for="tb-e-scope-global">Global</label>
            </div>
          <?php endif; ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-e-scope-college" value="college">
              <label class="form-check-label" for="tb-e-scope-college">College</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-e-scope-program" value="program">
              <label class="form-check-label" for="tb-e-scope-program">Program</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-e-scope-course" value="course">
              <label class="form-check-label" for="tb-e-scope-course">Course</label>
            </div>
          </div>
        </div>
        <!-- College -->
        <div class="mb-3" id="tb-e-college-wrap">
          <label class="form-label" for="tb-e-college">College</label>
          <select name="college_id" id="tb-e-college" class="form-select" aria-label="College" title="College">
            <option value="">— Select college —</option>
            <?php foreach ($colleges as $c): ?>
            <option value="<?= (int)($c['college_id'] ?? 0) ?>">
              <?= $esc(($c['short_name'] ?? '').' — '.($c['college_name'] ?? '')) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Program -->
        <div class="mb-3" id="tb-e-program-wrap">
          <label class="form-label" for="tb-e-program">Program</label>
          <select name="program_id" id="tb-e-program" class="form-select" aria-label="Program" title="Program">
            <option value="">— Select program —</option>
            <?php foreach ($programsOfCollege as $p): ?>
            <option value="<?= (int)($p['program_id'] ?? 0) ?>">
              <?= $esc($p['program_name'] ?? '') ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Course -->
        <div class="mb-3 d-none" id="tb-e-course-wrap">
          <label class="form-label" for="tb-e-course">Course</label>
          <select name="course_id" id="tb-e-course" class="form-select" aria-label="Course" title="Course">
            <option value="">— Select course —</option>
          </select>
        </div>

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


<?php
  }
}
?>
