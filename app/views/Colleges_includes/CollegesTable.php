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
            <!-- show the rows from the table declared in Colleges.php -->
            <?php foreach ($colleges as $college): echo "<!--show colleges table-->"; ?>
            <tr>
                <!-- output each field as a table cell -->
                <td><?= htmlspecialchars($college['college_id']) ?></td>                
                <td><?= htmlspecialchars($college['short_name']) ?></td>
                <td><?= htmlspecialchars($college['college_name']) ?></td>
                <td><?= htmlspecialchars($college['dean_name']) ?></td>
                <td>
                    <!-- save data in the button to fetch in the edit modal -->
                    <button                    
                    class="btn btn-sm btn-outline-primary edit-college-btn"
                    data-college-id="<?= htmlspecialchars($college['college_id']) ?>"                    
                    data-college-short-name="<?= htmlspecialchars($college['short_name']) ?>"
                    data-college-name="<?= htmlspecialchars($college['college_name']) ?>"
                    data-dean="<?= htmlspecialchars($college['dean']) ?>"
                    data-dean-name="<?= htmlspecialchars($college['dean_name']) ?>"
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