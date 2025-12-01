<?php
// /app/Modules/Accounts/Views/partials/AccountsTable.php
// Expects: $users, $canEdit, $canDelete
/** @var array $users */
/** @var bool  $canEdit */
/** @var bool  $canDelete */
?>
<div class="table-responsive shadow-sm border rounded">
  <table class="table table-bordered table-striped table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>ID No</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>College/Department</th>
        <th>Status</th>
        <th style="width:180px;" class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!empty($users)): ?>
      <?php foreach ($users as $row): ?>
        <tr
          data-id-no="<?= htmlspecialchars((string)($row['id_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
          data-fname="<?= htmlspecialchars((string)($row['fname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
          data-mname="<?= htmlspecialchars((string)($row['mname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
          data-lname="<?= htmlspecialchars((string)($row['lname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
          data-email="<?= htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
          data-role-id="<?= htmlspecialchars((string)($row['role_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
          data-department-id="<?= htmlspecialchars((string)($row['department_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
        >
          <td><?= htmlspecialchars((string)$row['id_no']) ?></td>
          <td>
            <?= htmlspecialchars((string)$row['lname']) ?>,
            <?= htmlspecialchars((string)$row['fname']) ?>
            <?= htmlspecialchars((string)($row['mname'] ? $row['mname'][0] . '.' : '')) ?>
          </td>
          <td><?= htmlspecialchars((string)$row['email']) ?></td>
          <td><?= htmlspecialchars((string)($row['role_name'])) ?></td>
          <td><?= htmlspecialchars((string)($row['department_short_name'] ?: '-unassigned-')) ?></td>
          <td><?= htmlspecialchars((string)($row['status'] ?? '')) ?></td>
          <td class="text-end">
            <?php if ($canEdit): ?>
              <button class="btn btn-sm btn-primary <?= $canDelete ? 'me-2' : '' ?>"
                      data-bs-toggle="modal"
                      data-bs-target="#editUserModal">
                <i class="bi bi-pencil"></i> Edit
              </button>
            <?php endif; ?>

            <?php if ($canDelete): ?>
              <button class="btn btn-sm btn-danger"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteUserModal">
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
      <tr><td colspan="8" class="text-center">No users found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
