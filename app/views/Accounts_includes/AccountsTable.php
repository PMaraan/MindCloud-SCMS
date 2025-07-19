    <?php
        //root/app/views/Accounts_includes/AccountsTable.php
    ?>

    <!-- Accounts Table -->
    <table class="table account-table table-bordered table-hover">
        <thead>
        <tr>
            <th>ID Number</th>            
            <th>First Name</th>
            <th>M.I.</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>College</th>
            <th>Roles</th>
            <th></th>
        </tr>
        </thead>
        <tbody id="table-body">
            
            <?php foreach ($users as $user): echo "<!--show users table-->"; ?>
            <tr>
                <td><?= htmlspecialchars($user['id_no']) ?></td>                
                <td><?= htmlspecialchars($user['fname']) ?></td>
                <td><?= htmlspecialchars($user['mname']) ?></td>
                <td><?= htmlspecialchars($user['lname']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['college_short_name']) ?></td>
                <td>
                    <?php foreach (explode(',', $user['roles']) as $role): ?>
                        <span class="role-badge"><?= htmlspecialchars(trim($role)) ?></span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <button 
                    class="btn btn-sm btn-outline-primary edit-btn"
                    data-id_no="<?= htmlspecialchars($user['id_no']) ?>"                    
                    data-fname="<?= htmlspecialchars($user['fname']) ?>"
                    data-mname="<?= htmlspecialchars($user['mname']) ?>"
                    data-lname="<?= htmlspecialchars($user['lname']) ?>"
                    data-email="<?= htmlspecialchars($user['email']) ?>"
                    data-role="<?= htmlspecialchars($user['roles']) ?>"
                    data-bs-toggle="modal"
                    data-bs-target="#editUserModal"
                    >
                    <i class="bi bi-pencil"></i> Edit
                    </button>
                </td>
                
            </tr>
            <?php endforeach; ?>
        
        </tbody>
    </table>