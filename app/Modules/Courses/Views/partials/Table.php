<?php
// app/Modules/Courses/Views/partials/Table.php
/**
 * Courses Module – Table Partial
 *
 * Expected variables (from CoursesController::index()):
 * @var array<int, array{
 *     course_id:int|string,
 *     course_code:string|null,
 *     course_name:string|null,
 *     college_id:int|string|null,
 *     college_short:string|null,
 *     curricula:string|null,       // comma-separated curriculum codes for display
 *     curricula_ids:string|null    // comma-separated curriculum ids for JS preselect
 * }> $rows
 * @var bool $canEdit
 * @var bool $canDelete
 */
?>
<?php $esc = $esc ?? static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); ?>
<div class="table-responsive shadow-sm border rounded">
  <table class="table table-bordered table-striped table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th style="width:120px;">Code</th>
        <th>Name</th>
        <th style="width:220px;">Curricula</th>
        <th style="width:160px;">College</th>
        <th style="width:200px;">Assigned Professors</th>
        <?php if (!empty($canEdit) || !empty($canDelete)): ?>
          <th style="width:180px;" class="text-end">Actions</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($rows)): ?>
        <?php foreach ($rows as $r): ?>
          <?php
            $courseId       = $r['course_id']      ?? '';
            $courseCode     = $r['course_code']    ?? '';
            $courseName     = $r['course_name']    ?? '';
            $collegeId      = $r['college_id']     ?? '';
            $collegeShort   = $r['department_short']  ?? '—';
            $curriculaLabel = ($r['curricula']     ?? '') !== '' ? $r['curricula'] : '—';
            $curriculaIds   = $r['curricula_ids']  ?? ''; // e.g., "3,5,9"
            $professorsList   = $r['assigned_professors'] ?? [];
            $profNames        = array_column($professorsList, 'name');
            $profDisplay      = !empty($profNames) ? implode('<br>', array_map($esc, $profNames)) : '<span class="text-muted">—</span>';
            $profJson         = $esc(json_encode($professorsList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
          ?>
          <tr
            data-course-id="<?= (int)$r['course_id'] ?>"
            data-course-code="<?= $esc($r['course_code']) ?>"
            data-course-name="<?= $esc($r['course_name']) ?>"
            data-college-id="<?= $r['college_id'] !== null ? (int)$r['college_id'] : '' ?>"
            data-curricula-ids="<?= $esc($r['curricula_ids'] ?? '') ?>"
            data-professors="<?= $profJson ?>"
          >
            <td><?= $esc($r['course_code']) ?></td>
            <td><?= $esc($r['course_name']) ?></td>
            <td><?= $esc($curriculaLabel) ?></td>
            <td><?= $esc($r['department_short'] ?? '—') ?></td>
            <td><?= $profDisplay ?></td>

            <?php if (!empty($canEdit) || !empty($canDelete)): ?>
              <td class="text-end">
                <?php if (!empty($canEdit)): ?>
                  <button
                    class="btn btn-sm btn-primary <?= !empty($canDelete) ? 'me-2' : '' ?>"
                    data-bs-toggle="modal"
                    data-bs-target="#EditModal"
                  >
                    <i class="bi bi-pencil"></i> Edit
                  </button>
                <?php endif; ?>
                <?php if (!empty($canDelete)): ?>
                  <button
                    class="btn btn-sm btn-outline-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#DeleteModal"
                  >
                    <i class="bi bi-trash"></i> Delete
                  </button>
                <?php endif; ?>
                <?php if (empty($canEdit) && empty($canDelete)): ?>
                  <span class="text-muted">No action</span>
                <?php endif; ?>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="<?= (!empty($canEdit) || !empty($canDelete)) ? 5 : 4 ?>" class="text-center">No records found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
