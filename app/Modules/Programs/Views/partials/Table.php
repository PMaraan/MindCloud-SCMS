<?php
// app/Modules/Programs/Views/partials/Table.php
/**
 * Programs listing table.
 *
 * Expects:
 * @var array $rows       Each row: program_id, program_name, college_id, college_label
 * @var bool  $canEdit
 * @var bool  $canDelete
 */
?>
<div class="table-responsive shadow-sm border rounded">
  <table class="table table-bordered table-striped table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Program</th>
        <th style="width:240px;">College</th>
        <th style="width:180px;" class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!empty($rows)): ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars((string)$r['program_name'], ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['college_label'] ?? ''), ENT_QUOTES) ?></td>
          <td class="text-end">
            <?php if (!empty($canEdit)): ?>
              <button class="btn btn-sm btn-primary <?= !empty($canDelete) ? 'me-2' : '' ?>"
                      data-bs-toggle="modal"
                      data-bs-target="#editProgramModal"
                      data-program-id="<?= (int)$r['program_id'] ?>"
                      data-program-name="<?= htmlspecialchars((string)$r['program_name'], ENT_QUOTES) ?>"
                      data-college-id="<?= (int)$r['college_id'] ?>">
                <i class="bi bi-pencil"></i> Edit
              </button>
            <?php endif; ?>
            <?php if (!empty($canDelete)): ?>
              <button class="btn btn-sm btn-danger"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteProgramModal"
                      data-program-id="<?= (int)$r['program_id'] ?>"
                      data-program-name="<?= htmlspecialchars((string)$r['program_name'], ENT_QUOTES) ?>">
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
      <tr><td colspan="3" class="text-center text-muted py-4">No results.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
