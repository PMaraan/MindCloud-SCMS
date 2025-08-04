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
            <?php 
                // get programs
                $query = $db->getAllProgramsInfo();

                if ($query && $query['success']) {
                    $programs = $query['db'];

                    // Check permissions once
                    $canEditPrograms = $db->checkPermission('ProgramModification');
                    $canDeletePrograms = $db->checkPermission('ProgramDeletion');
                    $canEdit = $canEditPrograms['success'] && $canEditPrograms['hasPermission'];
                    $canDelete = $canDeletePrograms['success'] && $canDeletePrograms['hasPermission'];

                    if (!empty($programs)) {
                        foreach ($programs as $program): echo "<!--show programs table-->"; 
                        $program_id = htmlspecialchars($program['program_id']);
                        $program_name = htmlspecialchars($program['program_name']);
                        $college_id = htmlspecialchars($program['college_id']);
                        $college_short_name = htmlspecialchars($program['college_short_name']);
                        $chair_id = htmlspecialchars($program['chair_id']);
                        $chair_name = htmlspecialchars($program['chair_name']);
            ?>

                <tr>
                    <!-- output each field as a table cell -->
                    <td><?= $program_id ?></td>                
                    <td><?= $program_name ?></td>
                    <td><?= $college_short_name ?></td>
                    <td><?= $chair_id ?></td>
                    <td><?= $chair_name ?></td>
                    <td>
                        <?php if ($canEdit): ?>
                        <!-- save data in the button to fetch in the edit modal -->
                            <button
                                class="btn btn-sm btn-outline-primary edit-college-btn"
                                data-program-id="<?= $program_id ?>"                    
                                data-program-name="<?= $program_name ?>"
                                data-college-id="<?= $college_id ?>" 
                                data-college-short-name="<?= $college_short_name ?>" 
                                data-chair-id="<?= $chair_id ?>"
                                data-chair-name="<?= $chair_name ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#editProgramModal"
                                data-action="edit" 
                            ><!-- action is required to be handled by js -->
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        <?php endif; ?>

                        <?php if ($canDelete): ?>
                            <!-- save data in the button for autofill in the delete modal -->
                            <button                    
                                class="btn btn-sm btn-outline-danger delete-college-btn"
                                data-program-id="<?= $program_id ?>"  
                                data-program-name="<?= $program_name ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteProgramModal"
                                data-action="delete" 
                            ><!-- action is required to be handled by js -->
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        <?php endif; ?>
                    </td>
                    
                </tr>
            <?php
                        endforeach;
                    }else {
                        // No records to show
                        echo '<tr><td colspan="8" class="text-center text-muted">No records to show</td></tr>';
                    }
                } else {
                    // Query failes
                    $error = $query['error'] ?? 'Unknown error';
                    echo "<script>alert('Error: " . addslashes($error) . "');</script>";    
            }
            ?>
        
        </tbody>
    </table>