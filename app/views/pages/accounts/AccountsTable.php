<?php
// root/app/views/pages/accounts/AccountsTable.php
// Uses $users, $canEdit, $canDelete
?>
<div class="table-responsive">
  <table class="table table-bordered table-striped table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>ID No</th>
        <th>First Name</th>
        <th>Middle Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>College</th>
        <th style="width: 140px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($users)): ?>
        <?php foreach ($users as $user): ?>
          <tr
            data-id-no="<?= htmlspecialchars((string)$user['id_no'], ENT_QUOTES, 'UTF-8') ?>"
            data-fname="<?= htmlspecialchars((string)$user['fname'], ENT_QUOTES, 'UTF-8') ?>"
            data-mname="<?= htmlspecialchars((string)($user['mname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            data-lname="<?= htmlspecialchars((string)$user['lname'], ENT_QUOTES, 'UTF-8') ?>"
            data-email="<?= htmlspecialchars((string)$user['email'], ENT_QUOTES, 'UTF-8') ?>"
          >
            <td><?= htmlspecialchars((string)$user['id_no']) ?></td>
            <td><?= htmlspecialchars((string)$user['fname']) ?></td>
            <td><?= htmlspecialchars((string)($user['mname'] ?: '-unassigned-')) ?></td>
            <td><?= htmlspecialchars((string)$user['lname']) ?></td>
            <td><?= htmlspecialchars((string)$user['email']) ?></td>
            <td><?= htmlspecialchars((string)($user['role_name'])) ?></td>
            <td><?= htmlspecialchars((string)($user['college_short_name'] ?: '-unassigned-')) ?></td>
            <td>
              <?php if ($canEdit || $canDelete): ?>
                <?php if ($canEdit): ?>
                  <button class="btn btn-sm btn-outline-primary"
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
              <?php else: ?>
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
