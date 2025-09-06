<?php /* CreateModal */ ?>
<div class="modal fade" id="CreateModal" tabindex="-1" aria-hidden="true" aria-labelledby="CreateCourseLabel">
  <div class="modal-dialog">
    <form method="post" action="<?= BASE_PATH ?>/dashboard?page=courses&action=create" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="CreateCourseLabel">Create Course</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

        <div class="mb-3">
          <label class="form-label">Course Code <span class="text-danger">*</span></label>
          <input type="text" name="course_code" class="form-control" maxlength="50" required>
          <div class="form-text">Unique per curriculum.</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Course Name <span class="text-danger">*</span></label>
          <input type="text" name="course_name" class="form-control" maxlength="50" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Curriculum <span class="text-danger">*</span></label>
          <select name="curriculum_id" class="form-select" required>
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
          <select name="college_id" class="form-select">
            <option value="">— None —</option>
            <?php foreach ($colleges as $c): ?>
              <option value="<?= (int)$c['college_id'] ?>"><?= htmlspecialchars($c['short_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Create</button>
      </div>
    </form>
  </div>
</div>
