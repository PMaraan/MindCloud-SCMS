<?php
/* app/Modules/Courses/Views/partials/Table.php */
if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:120px;">Code</th>
        <th>Name</th>
        <th style="width:200px;">Curricula</th>
        <th style="width:140px;">College</th>
        <?php if (!empty($canEdit) || !empty($canDelete)): ?>
          <th style="width:120px;">Actions</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($rows)): foreach ($rows as $r): ?>
        <?php
          $courseId      = $r['course_id']      ?? '';
          $courseCode    = $r['course_code']    ?? '';
          $courseName    = $r['course_name']    ?? '';
          $collegeId     = $r['college_id']     ?? '';
          $college       = $r['college_short']  ?? '—';
          $curriculaLabel = ($r['curricula'] ?? '') !== '' ? $r['curricula'] : '—';
          // Comma-separated list like "3,5,9" provided by the model
          $curriculaIds  = $r['curricula_ids']  ?? '';   // may be '' if none
        ?>
        <tr
          data-course-id="<?= e($courseId) ?>"
          data-course-code="<?= e($courseCode) ?>"
          data-course-name="<?= e($courseName) ?>"
          data-college-id="<?= e($collegeId) ?>"
          data-curricula-ids="<?= e($curriculaIds) ?>"
        >
          <td><?= e($courseCode) ?></td>
          <td><?= e($courseName) ?></td>
          <td><?= e($curriculaLabel) ?></td>
          <td><?= e($college) ?></td>
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
