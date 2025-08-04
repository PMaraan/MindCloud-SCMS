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
                <th></th> <!-- Action column -->
            </tr>
        </thead>
        <tbody id="table-body">
            <?php 
                // Get user records
                $query = $db->getAllUsersAccountInfo();

                if ($query && $query['success']) {
                    $users = $query['db'];

                    // Check permissions once
                    $canEditAccount = $db->checkPermission('AccountModification');
                    $canDeleteAccount = $db->checkPermission('AccountDeletion');
                    $canEdit = $canEditAccount['success'] && $canEditAccount['hasPermission'];
                    $canDelete = $canDeleteAccount['success'] && $canDeleteAccount['hasPermission'];

                    if (!empty($users)) {
                        foreach ($users as $user):
                            $id_no = htmlspecialchars($user['id_no']);
                            $fname = htmlspecialchars($user['fname']);
                            $mname = htmlspecialchars($user['mname']);
                            $lname = htmlspecialchars($user['lname']);
                            $email = htmlspecialchars($user['email']);
                            $college_id = htmlspecialchars($user['college_id']);
                            $college_short_name = htmlspecialchars($user['college_short_name']);
                            $role_id = htmlspecialchars($user['role_id']);
                            $role_name = htmlspecialchars($user['role_name']);
            ?>

            <tr>
                <td><?= $id_no ?></td>
                <td><?= $fname ?></td>
                <td><?= $mname ?></td>
                <td><?= $lname ?></td>
                <td><?= $email ?></td>
                <td><?= $college_short_name ?></td>
                <td><?= $role_name ?></td>
                <td>
                    <?php if ($canEdit): ?>
                        <button 
                            class="btn btn-sm btn-outline-primary edit-btn2"
                            data-id-no="<?= $id_no ?>"                    
                            data-fname="<?= $fname ?>"
                            data-mname="<?= $mname ?>"
                            data-lname="<?= $lname ?>"
                            data-email="<?= $email ?>"
                            data-college-id="<?= $college_id ?>"
                            data-college-name="<?= $college_short_name ?>"
                            data-role-id="<?= $role_id ?>"
                            data-role-name="<?= $role_name ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#editUserModal2"
                            data-action="edit"
                        >
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    <?php endif; ?>

                    <?php if ($canDelete): ?>
                        <button 
                            class="btn btn-sm btn-outline-danger delete-btn"
                            data-id-no="<?= $id_no ?>"
                            data-role-id="<? $role_id ?>"
                            data-full-name="<?= $fname . (isset($mname) ? ' ' . $mname : '') . ' ' . $lname ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteUserModal"
                            data-action="delete"
                        >
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    <?php endif; ?>

                    <?php if (!$canEdit && !$canDelete): ?>
                        <span class="text-muted">No Actions</span>
                    <?php endif; ?>
                </td>
            </tr>

            <?php 
                        endforeach;
                    } else {
                        // No records to show
                        echo '<tr><td colspan="8" class="text-center text-muted">No records to show</td></tr>';
                    }
                } else {
                    // Query failed
                    $error = $query['error'] ?? 'Unknown error';
                    echo "<script>alert('Error: " . addslashes($error) . "');</script>";    
                }
            ?>
        </tbody>
    </table>
