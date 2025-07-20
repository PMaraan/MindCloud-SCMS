    <?php
        //root/app/views/Roles_includes/RolesTable.php
    ?>

    <!-- Roles Table -->
    <table class="table account-table table-bordered table-hover">
        <thead>
        <tr>
            <th>Role ID</th>            
            <th>Role Name</th>
            <th>Level</th>
            <th></th>
        </tr>
        </thead>
        <tbody id="table-body">
            
            <?php foreach ($roles as $role): echo "<!--show roles table-->"; ?>
            <tr>
                <td><?= htmlspecialchars($role['role_id']) ?></td>                
                <td><?= htmlspecialchars($role['name']) ?></td>
                <td><?= htmlspecialchars($role['level']) ?></td>
                <td>
                    <button 
                    class="btn btn-sm btn-outline-primary edit-role-btn"
                    data-role-id="<?= htmlspecialchars($role['role_id']) ?>"                    
                    data-role-name="<?= htmlspecialchars($role['name']) ?>"
                    data-role-level="<?= htmlspecialchars($role['level']) ?>"
                    data-bs-toggle="modal"
                    data-bs-target="#editRoleModal"
                    data-action="edit" 
                    ><!-- action is required to be handled by js -->
                    <i class="bi bi-pencil"></i> Edit
                    </button>
                </td>
                
            </tr>
            <?php endforeach; ?>
        
        </tbody>
    </table>