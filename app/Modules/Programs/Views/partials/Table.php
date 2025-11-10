<?php
// app/Modules/Programs/Views/partials/Table.php
/**
 * Programs listing table.
 *
 * Expects:
 * @var array $rows       Each row: program_id, program_name, department_id, college_label, chair_id, chair_full_name
 * @var bool  $canEdit
 * @var bool  $canDelete
 */
?>
<div class="table-responsive shadow-sm border rounded">
  <table class="table table-bordered table-striped table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Program</th>
        <th style="width:200px;">College</th>
        <th style="width:260px;">Chair</th>
        <th style="width:180px;" class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!empty($rows)): ?>
      <?php foreach ($rows as $r): ?>
        <tr data-program-id="<?= (int)$r['program_id'] ?>"
            data-program-name="<?= htmlspecialchars((string)$r['program_name'], ENT_QUOTES) ?>"
            data-college-id="<?= (int)$r['department_id'] ?>"
            data-chair-id="<?= htmlspecialchars((string)($r['chair_id'] ?? ''), ENT_QUOTES) ?>"
            data-chair-name="<?= htmlspecialchars((string)($r['chair_full_name'] ?? ''), ENT_QUOTES) ?>">
          <td><?= htmlspecialchars((string)$r['program_name'], ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['college_label'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['chair_full_name'] ?? '-unassigned-'), ENT_QUOTES) ?></td>
          <td class="text-end">
            <?php if (!empty($canEdit)): ?>
              <button class="btn btn-sm btn-primary <?= !empty($canDelete) ? 'me-2' : '' ?>"
                      data-bs-toggle="modal"
                      data-bs-target="#editProgramModal">
                <i class="bi bi-pencil"></i> Edit
              </button>
            <?php endif; ?>
            <?php if (!empty($canDelete)): ?>
              <button class="btn btn-sm btn-danger"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteProgramModal">
                <i class="bi bi-trash"></i> Delete
              </button>
            <?php endif; ?>
            <?php if (empty($canEdit) && empty($canDelete)): ?>
              <span class="text-muted">No action</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="4" class="text-center text-muted py-4">No results.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
