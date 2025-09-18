<?php 
// /app/Modules/Curricula/Views/partials/Table.php
// expects: $rows (array), $canEdit (bool), $canDelete (bool)
/**
 * Curricula table view partial.
 *
 * Expects:
 * @var array $rows      List of curricula rows
 * @var bool  $canEdit   Whether current user can edit
 * @var bool  $canDelete Whether current user can delete
 */
?>
<div class="table-responsive shadow-sm border rounded">
  <table class="table table-bordered table-striped table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th style="width:90px;">ID</th>
        <th style="width:180px;">Curriculum Code</th>
        <th>Title</th>
        <th style="width:160px;">Effective Start</th>
        <th style="width:160px;">Effective End</th>
        <th style="width:180px;" class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!empty($rows)): ?>
      <?php foreach ($rows as $r): ?>
        <tr
          data-curriculum-id="<?= (int)($r['curriculum_id'] ?? 0) ?>"
          data-curriculum-code="<?= htmlspecialchars((string)($r['curriculum_code'] ?? ''), ENT_QUOTES) ?>"
          data-title="<?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES) ?>"
          data-start="<?= htmlspecialchars((string)($r['effective_start'] ?? ''), ENT_QUOTES) ?>"
          data-end="<?= htmlspecialchars((string)($r['effective_end'] ?? ''), ENT_QUOTES) ?>"
        >
          <td><?= (int)($r['curriculum_id'] ?? 0) ?></td>
          <td><?= htmlspecialchars((string)($r['curriculum_code'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['effective_start'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['effective_end'] ?? ''), ENT_QUOTES) ?></td>
          <td class="text-end">
            <?php if (!empty($canEdit)): ?>
              <button
                type="button"
                class="btn btn-sm btn-primary <?= !empty($canDelete) ? 'me-2' : '' ?>"
                data-bs-toggle="modal"
                data-bs-target="#EditModal"
              >
                <i class="bi bi-pencil"></i> Edit
              </button>
            <?php endif; ?>

            <?php if (!empty($canDelete)): ?>
              <button
                type="button"
                class="btn btn-sm btn-danger"
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
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
