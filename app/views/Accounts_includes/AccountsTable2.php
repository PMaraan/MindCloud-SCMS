    <?php
        //root/app/views/Accounts_includes/AccountsTable2.php
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
            <th>Role</th>
            <th></th>
        </tr>
        </thead>
        <tbody id="table-body">
            
            <?php 
                // get users from db
                $query = $db->getAllUsersAccountInfo();
                if ($query && $query['success']) {
                    $users = $query['db'];
                    //echo print_r($users);
                } else {
                    $error = $query['error'] ?? 'Unknown error';
                    echo "<script>alert('Error: " . addslashes($error) . "');</script>";    
                }

                // check if user has permission
                $canEditAccount = $db->checkPermission('AccountModification');
                $canEdit = $canEditAccount['success'] && $canEditAccount['hasPermission'];
                foreach ($users as $user): echo "<!--show users table--> "; 
            ?>
            <tr>
                <td><?= htmlspecialchars($user['id_no']) ?></td>
                <td><?= htmlspecialchars($user['fname']) ?></td>
                <td><?= htmlspecialchars($user['mname']) ?></td>
                <td><?= htmlspecialchars($user['lname']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['college_short_name']) ?></td>
                <td><?= htmlspecialchars($user['role_name']) ?></td>
                <td>
                    <?php if ($canEdit): ?>
                        <button 
                        class="btn btn-sm btn-outline-primary edit-btn2"
                        data-id-no="<?= htmlspecialchars($user['id_no']) ?>"                    
                        data-fname="<?= htmlspecialchars($user['fname']) ?>"
                        data-mname="<?= htmlspecialchars($user['mname']) ?>"
                        data-lname="<?= htmlspecialchars($user['lname']) ?>"
                        data-email="<?= htmlspecialchars($user['email']) ?>"
                        data-college-id="<?= htmlspecialchars($user['college_id']) ?>"
                        data-college-name="<?= htmlspecialchars($user['college_short_name']) ?>"
                        data-role-id="<?= htmlspecialchars($user['role_id']) ?>"
                        data-role-name="<?= htmlspecialchars($user['role_name']) ?>"
                        <?php
                        /*
                        data-program-id="<?= htmlspecialchars($user['program_id']) ?>"
                        data-program-name="<?= htmlspecialchars($user['program_name']) ?>"               
                        */
                        ?>
                        data-bs-toggle="modal"
                        data-bs-target="#editUserModal2"
                        data-action="edit" 
                        ><!-- action is required to be handled by js -->
                        <i class="bi bi-pencil"></i> Edit
                        </button>
                    <?php endif; ?>
                    
                    <?php if (!$canEdit): ?>
                        <span class="text-muted">No Actions</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        
        </tbody>
    </table>