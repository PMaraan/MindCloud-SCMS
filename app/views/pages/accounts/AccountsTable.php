<?php
// root/app/views/pages/accounts/AccountsTable.php
?>
<div class="table-responsive">
  <table class="table table-striped table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>ID No</th>
        <th>First Name</th>
        <th>Middle Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>College</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($users)): ?>
        <?php foreach ($users as $user): ?>
          <tr 
            data-id-no="<?= htmlspecialchars($user['id_no']) ?>"
            data-fname="<?= htmlspecialchars($user['fname']) ?>"
            data-mname="<?= htmlspecialchars($user['mname']) ?>"
            data-lname="<?= htmlspecialchars($user['lname']) ?>"
            data-email="<?= htmlspecialchars($user['email']) ?>"
          >
            <td><?= htmlspecialchars($user['id_no']) ?></td>
            <td><?= htmlspecialchars($user['fname']) ?></td>
            <td><?= htmlspecialchars($user['mname']) ?></td>
            <td><?= htmlspecialchars($user['lname']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['role_name']) ?></td>
            <td><?= htmlspecialchars($user['college_short_name'] ?? '-') ?></td>
            <td>
              <?php if ($this->userHasPermission('edit_accounts') || $this->userHasPermission('delete_accounts')): ?>
                <?php if ($this->userHasPermission('edit_accounts')): ?>
                  <button class="btn btn-sm btn-warning" 
                          data-bs-toggle="modal" 
                          data-bs-target="#editUserModal">
                    Edit
                  </button>
                <?php endif; ?>

                <?php if ($this->userHasPermission('delete_accounts')): ?>
                  <button class="btn btn-sm btn-danger" 
                          data-bs-toggle="modal" 
                          data-bs-target="#deleteUserModal">
                    Delete
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
