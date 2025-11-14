<?php
/**
 * /app/Modules/SyllabusTemplates/Views/partials/CreateModal.php
 * “New Template” modal (scope: system/college/program).
 */
// /app/Modules/SyllabusTemplates/Views/partials/CreateModal.php
if (!function_exists('renderCreateModal')) {
  function renderCreateModal(
    string $assetBase,
    bool $allowGlobal,
    bool $allowCollege,
    bool $allowProgram,
    bool $allowCourse,
    array $colleges,
    array $programs,
    ?int $defaultCollegeId,
    bool $lockCollege,
    callable $esc
  ): void {
    $pageKey = $GLOBALS['PAGE_KEY'] ?? 'syllabus-templates';
    $base    = defined('BASE_PATH') ? BASE_PATH : '';
?>
<div class="modal fade" id="tbCreateModal" tabindex="-1" aria-hidden="true" aria-labelledby="tbCreateLabel">
  <div class="modal-dialog">
    <form method="post"
          action="<?= $esc($base) ?>/dashboard?page=<?= $esc($pageKey) ?>&action=create"
          class="modal-content"
          data-default-college="<?= (int)($defaultCollegeId ?? 0) ?>"
          data-lock-college="<?= $lockCollege ? '1' : '0' ?>">
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

          <?php if ($allowGlobal): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-scope-global" value="global" checked>
              <label class="form-check-label" for="tb-scope-global">Global</label>
            </div>
          <?php endif; ?>

          <?php if ($allowCollege): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-scope-college" value="college" <?= $allowGlobal ? '' : 'checked' ?>>
              <label class="form-check-label" for="tb-scope-college">College</label>
            </div>
          <?php endif; ?>

          <?php if ($allowProgram): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="scope" id="tb-scope-program" value="program" <?= (!$allowGlobal && !$allowCollege) ? 'checked' : '' ?>>
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
          <select name="college_id"
                  id="tb-college"
                  class="form-select"
                  data-default="<?= (int)($defaultCollegeId ?? 0) ?>"
                  data-lock="<?= $lockCollege ? '1' : '0' ?>">
            <option value="">— Select college —</option>
            <?php foreach ($colleges as $college): ?>
              <?php $cid = (int)($college['college_id'] ?? 0); ?>
              <option value="<?= $cid ?>" <?= $defaultCollegeId === $cid ? 'selected' : '' ?>>
                <?= $esc(($college['short_name'] ?? '').' — '.($college['college_name'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3 d-none" id="tb-program-wrap">
          <label class="form-label" for="tb-program">Program <span class="text-danger">*</span></label>
          <select name="program_id" id="tb-program" class="form-select">
            <option value="">— Select program —</option>
            <?php foreach ($programs as $program): ?>
              <option value="<?= (int)($program['program_id'] ?? 0) ?>">
                <?= $esc($program['program_name'] ?? '') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Programs list auto-filters based on the selected college.</div>
        </div>

        <div class="mb-3 d-none" id="tb-course-wrap">
          <label class="form-label" for="tb-course">Course <span class="text-danger">*</span></label>
          <select name="course_id" id="tb-course" class="form-select">
            <option value="">— Select course —</option>
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
<?php
  }
}
?>
