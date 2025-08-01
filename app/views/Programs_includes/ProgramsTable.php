    <?php
        //root/app/views/Programs_includes/ProgramsTable.php
    ?>

    <!-- Programs Table -->
    <table class="table account-table table-bordered table-hover">
        <thead>
        <tr>
            <th>Program ID</th>            
            <th>Program Name</th>
            <th>College</th>
            <th>Chair ID</th>
            <th>Chair</th>
            <th></th>
        </tr>
        </thead>
        <tbody id="table-body">
            <!-- show the rows from the table declared in Programs.php -->
            <?php foreach ($programs as $program): echo "<!--show programs table-->"; 
            //echo print_r($programs)?>
            <tr>
                <!-- output each field as a table cell -->
                <td><?= htmlspecialchars($program['program_id']) ?></td>                
                <td><?= htmlspecialchars($program['program_name']) ?></td>
                <td><?= htmlspecialchars($program['college_short_name']) ?></td>
                <td><?= htmlspecialchars($program['chair_id']) ?></td>
                <td><?= htmlspecialchars($program['chair_name']) ?></td>
                <td>
                    <!-- save data in the button to fetch in the edit modal -->
                    <button
                    class="btn btn-sm btn-outline-primary edit-college-btn"
                    data-program-id="<?= htmlspecialchars($program['program_id']) ?>"                    
                    data-program-name="<?= htmlspecialchars($program['program_name']) ?>"
                    data-college-id="<?= htmlspecialchars($program['college_id']) ?>" 
                    data-college-short-name="<?= htmlspecialchars($program['college_short_name']) ?>" 
                    data-chair-id="<?= htmlspecialchars($program['chair_id']) ?>"
                    data-chair-name="<?= htmlspecialchars($program['chair_name']) // edit the db query to include the chair name?>"
                    data-bs-toggle="modal"
                    data-bs-target="#editProgramModal"
                    data-action="edit" 
                    ><!-- action is required to be handled by js -->
                    <i class="bi bi-pencil"></i> Edit
                    </button>
                </td>
                
            </tr>
            <?php endforeach; ?>
        
        </tbody>
    </table>