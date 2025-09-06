<?php /* app/Modules/Courses/Views/partials/table.php */ ?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:120px;">Code</th>
        <th>Name</th>
        <th style="width:160px;">Curriculum</th>
        <th style="width:140px;">College</th>
        <?php if (!empty($canEdit) || !empty($canDelete)): ?>
          <th style="width:120px;">Actions</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($rows)): foreach ($rows as $r): ?>
        <tr
          data-course-id="<?= (int)$r['course_id'] ?>"
          data-course-code="<?= htmlspecialchars($r['course_code'], ENT_QUOTES, 'UTF-8') ?>"
          data-course-name="<?= htmlspecialchars($r['course_name'], ENT_QUOTES, 'UTF-8') ?>"
          data-college-id="<?= htmlspecialchars((string)$r['college_id'], ENT_QUOTES, 'UTF-8') ?>"
          data-curriculum-id="<?= (int)$r['curriculum_id'] ?>"
        >
          <td><?= htmlspecialchars($r['course_code']) ?></td>
          <td><?= htmlspecialchars($r['course_name']) ?></td>
          <td><?= htmlspecialchars($r['curriculum_code']) ?> — <?= htmlspecialchars($r['curriculum_title']) ?></td>
          <td><?= htmlspecialchars($r['college_short'] ?? '—') ?></td>
          <?php if (!empty($canEdit) || !empty($canDelete)): ?>
            <td>
              <div class="btn-group btn-group-sm">
                <?php if (!empty($canEdit)): ?>
                  <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#EditModal">Edit</button>
                <?php endif; ?>
                <?php if (!empty($canDelete)): ?>
                  <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#DeleteModal">Delete</button>
                <?php endif; ?>
              </div>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No results.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
