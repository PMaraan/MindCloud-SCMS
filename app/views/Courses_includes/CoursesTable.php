    <?php
        //root/app/views/Courses_includes/CoursesTable.php
    ?>

    <!-- Courses Table -->
    <table class="table account-table table-bordered table-hover">
        <thead>
        <tr>
            <th>Course ID</th>            
            <th>Course Code</th>
            <th>Course Name</th>
            <th>Offering College</th>
            <th></th> <!-- Action column -->
        </tr>
        </thead>
        <tbody id="table-body">
            <?php 
                // Get colleges
                $query = $db->getAllCoursesInfo();

                if($query && $query['success']) {
                    $courses = $query['db'];

                    // Check permissions once
                    $canEditCourses = $db->checkPermission('CourseModification');
                    $canDeleteCourses = $db->checkPermission('CourseDeletion');
                    $canEdit = $canEditCourses['success'] && $canEditCourses['hasPermission'];
                    $canDelete = $canDeleteCourses['success'] && $canDeleteCourses['hasPermission'];
                
                    if (!empty($courses)) {
                        foreach ($courses as $course):
                        $course_id = htmlspecialchars($course['course_id']);
                        $course_code = htmlspecialchars($course['course_code']);
                        $course_name = htmlspecialchars($course['course_name']);
                        $offering_college_id = htmlspecialchars($course['college_id']);
                        $offering_college_short_name = htmlspecialchars($course['short_name']);
            ?>
            <tr>
                <!-- output each field as a table cell -->
                <td><?= $course_id ?></td>                
                <td><?= $course_code ?></td>
                <td><?= $course_name ?></td>
                <td><?= $offering_college_short_name ?></td>
                <td>
                    <?php if ($canEdit): ?>
                        <!-- save data in the button to fetch in the edit modal -->
                        <button                    
                            class="btn btn-sm btn-outline-primary edit-college-btn"
                            data-course-id="<?= $course_id ?>"                    
                            data-course-code="<?= $course_code ?>"
                            data-course-name="<?= $course_name ?>"
                            data-offering-college-id="<?= $offering_college_id ?>"
                            data-offering-college-name="<?= $offering_college_short_name ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#editCourseModal"
                            data-action="edit" 
                        ><!-- action is required to be handled by js -->
                        <i class="bi bi-pencil"></i> Edit
                        </button>
                    <?php endif; ?>

                    <?php if ($canDelete): ?>
                        <!-- save data in the button for autofill in the delete modal -->
                        <button                    
                            class="btn btn-sm btn-outline-danger delete-college-btn"
                            data-course-id="<?= $course_id ?>"
                            data-course-name="<?= $course_name ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteCourseModal"
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