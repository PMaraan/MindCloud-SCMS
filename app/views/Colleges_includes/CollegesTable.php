    <?php
        //root/app/views/Colleges_includes/CollegesTable.php
    ?>

    <!-- Roles Table -->
    <table class="table account-table table-bordered table-hover">
        <thead>
        <tr>
            <th>College ID</th>            
            <th>College Short Name</th>
            <th>College Name</th>
            <th>Dean</th>
            <th></th>
        </tr>
        </thead>
        <tbody id="table-body">
            
            <?php foreach ($colleges as $college): echo "<!--show roles table-->"; ?>
            <tr>
                <td><?= htmlspecialchars($college['college_id']) ?></td>                
                <td><?= htmlspecialchars($college['short_name']) ?></td>
                <td><?= htmlspecialchars($college['college_name']) ?></td>
                <td><?= htmlspecialchars($college['dean_name']) ?></td>
                <td>
                    <button 
                    class="btn btn-sm btn-outline-primary edit-role-btn"
                    data-college-id="<?= htmlspecialchars($college['college_id']) ?>"                    
                    data-college-short-name="<?= htmlspecialchars($college['short_name']) ?>"
                    data-college-name="<?= htmlspecialchars($college['college_name']) ?>"
                    data-dean="<?= htmlspecialchars($college['dean']) ?>"
                    data-bs-toggle="modal"
                    data-bs-target="#editCollegeModal"
                    data-action="edit" 
                    ><!-- action is required to be handled by js -->
                    <i class="bi bi-pencil"></i> Edit
                    </button>
                </td>
                
            </tr>
            <?php endforeach; ?>
        
        </tbody>
    </table>