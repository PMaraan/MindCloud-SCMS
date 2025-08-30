<?php /* app/Modules/Colleges/Views/partials/Table.php */ ?>
<?php
// expects: $rows (array), $canEdit (bool), $canDelete (bool)
?>
<div class="table-responsive">
  <table class="table table-bordered table-striped table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Code</th>
        <th style="width:140px;" class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!empty($rows)): ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars((string)($r['id'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['code'] ?? ''), ENT_QUOTES) ?></td>
          <td class="text-end">
            <?php if ($canEdit): ?>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal">
                <i class="bi bi-pencil"></i> Edit
              </button>
            <?php endif; ?>
            <?php if ($canDelete): ?>
              <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="bi bi-trash"></i> Delete
              </button>
            <?php endif; ?>
            <?php if (!$canEdit && !$canDelete): ?>
              <span class="text-muted">No action</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="4" class="text-center">No records found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>