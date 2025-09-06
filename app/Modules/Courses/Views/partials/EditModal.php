<?php /* EditModal */ ?>
<div class="modal fade" id="EditModal" tabindex="-1" aria-hidden="true" aria-labelledby="EditCourseLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= BASE_PATH ?>/dashboard?page=courses&action=edit" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="EditCourseLabel">Edit Course</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" id="edit-id" name="id">

        <div class="mb-3">
          <label class="form-label">Course Code <span class="text-danger">*</span></label>
          <input type="text" id="edit-course_code" name="course_code" class="form-control" maxlength="50" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Course Name <span class="text-danger">*</span></label>
          <input type="text" id="edit-course_name" name="course_name" class="form-control" maxlength="50" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Curriculum <span class="text-danger">*</span></label>
          <select id="edit-curriculum_id" name="curriculum_id" class="form-select" required>
            <option value="">— Select Curriculum —</option>
            <?php foreach ($curricula as $c): ?>
                <option value="<?= (int)$c['curriculum_id'] ?>">
                    <?= htmlspecialchars($c['curriculum_code']) ?> — <?= htmlspecialchars($c['curriculum_title']) ?>
                </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-0">
          <label class="form-label">College (optional)</label>
          <select id="edit-college_id" name="college_id" class="form-select">
            <option value="">— None —</option>
            <?php foreach ($colleges as $c): ?>
              <option value="<?= (int)$c['college_id'] ?>"><?= htmlspecialchars($c['short_name']) ?></option>
            <?php endforeach; ?>
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
