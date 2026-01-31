    <?php
        //root/app/views/Colleges_includes/CollegesTable.php
    ?>

    <!-- Colleges Table -->
    <table class="table account-table table-bordered table-hover">
        <thead>
        <tr>
            <th>College ID</th>            
            <th>College Short Name</th>
            <th>College Name</th>
            <th>Dean ID</th>
            <th>Dean</th>
            <th></th> <!-- Action column -->
        </tr>
        </thead>
        <tbody id="table-body">
            <?php 
                // Get colleges
                $query = $db->getAllCollegesInfo();

                if($query && $query['success']) {
                    $colleges = $query['db'];

                    // Check permissions once
                    $canEditColleges = $db->checkPermission('CollegeModification');
                    $canDeleteColleges = $db->checkPermission('CollegeDeletion');
                    $canEdit = $canEditColleges['success'] && $canEditColleges['hasPermission'];
                    $canDelete = $canDeleteColleges['success'] && $canDeleteColleges['hasPermission'];

                    if (!empty($colleges)) {
                        foreach ($colleges as $college):
                        $college_id = htmlspecialchars($college['college_id']);
                        $college_short_name = htmlspecialchars($college['short_name']);
                        $college_name = htmlspecialchars($college['college_name']);
                        $dean_id = htmlspecialchars($college['dean_id']);
                        $dean_name = htmlspecialchars($college['dean_name']);
            ?>

            <tr>
                <!-- output each field as a table cell -->
                <td><?= $college_id ?></td>                
                <td><?= $college_short_name ?></td>
                <td><?= $college_name ?></td>
                <td><?= $dean_id ?></td>
                <td><?= $dean_name ?></td>
                <td>
                    <?php if ($canEdit): ?>
                        <!-- save data in the button to fetch in the edit modal -->
                        <button                    
                            class="btn btn-sm btn-outline-primary edit-college-btn"
                            data-college-id="<?= $college_id ?>"                    
                            data-college-short-name="<?= $college_short_name ?>"
                            data-college-name="<?= $college_name ?>"
                            data-dean-id="<?= $dean_id ?? ''?>"
                            data-dean-name="<?= $dean_name ?? ''?>"
                            data-bs-toggle="modal"
                            data-bs-target="#editCollegeModal"
                            data-action="edit" 
                        ><!-- action is required to be handled by js -->
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    <?php endif; ?>

                    <?php if ($canDelete): ?>
                        <!-- save data in the button for autofill in the delete modal -->
                        <button                    
                            class="btn btn-sm btn-outline-danger delete-college-btn"
                            data-college-id="<?= $college_id ?>"  
                            data-college-name="<?= $college_name ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteCollegeModal"
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
                    // Query failed
                    $error = $query['error'] ?? 'Unknown error';
                    echo "<script>alert('Error: " . addslashes($error) . "');</script>";
                }
            ?>
        
        </tbody>
    </table>