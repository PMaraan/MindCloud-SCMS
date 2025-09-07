<?php /* EditModal */ ?>
<div class="modal fade" id="EditModal" tabindex="-1" aria-hidden="true" aria-labelledby="EditCourseLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= BASE_PATH ?>/dashboard?page=courses&action=edit" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="EditCourseLabel">Edit Course</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" id="edit-id" name="id">

        <div class="mb-3">
          <label class="form-label">Course Code <span class="text-danger">*</span></label>
          <input type="text" id="edit-course_code" name="course_code" class="form-control" maxlength="50" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Course Name <span class="text-danger">*</span></label>
          <input type="text" id="edit-course_name" name="course_name" class="form-control" maxlength="50" required>
        </div>

        <div class="mb-0">
          <label class="form-label">Linked Curricula</label>
          <select id="edit-curriculum_ids" name="curriculum_ids[]" class="form-select" multiple size="6">
            <?php if (!empty($curricula)): ?>
              <?php foreach ($curricula as $c): ?>
                <option value="<?= (int)$c['curriculum_id'] ?>">
                  <?= htmlspecialchars((string)$c['curriculum_code'], ENT_QUOTES, 'UTF-8') ?>
                  —
                  <?= htmlspecialchars((string)$c['curriculum_title'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="" disabled>(No curricula found)</option>
            <?php endif; ?>
          </select>
          <div class="form-text">Hold Ctrl/Cmd (or Shift) to select multiple.</div>
        </div>

        <div class="mb-0 mt-3">
          <label class="form-label">College (optional)</label>
          <select id="edit-college_id" name="college_id" class="form-select">
            <option value="">— None —</option>
            <?php if (!empty($colleges)): ?>
              <?php foreach ($colleges as $c): ?>
                <option value="<?= (int)$c['college_id'] ?>">
                  <?= htmlspecialchars((string)$c['short_name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>
