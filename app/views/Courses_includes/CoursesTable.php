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
            <th>Program</th>
            <th></th>
        </tr>
        </thead>
        <tbody id="table-body">
            <!-- show the rows from the table declared in Colleges.php -->
            <?php foreach ($courses as $course): echo htmlspecialchars("<!--show courses table-->"); ?>
            <tr>
                <!-- output each field as a table cell -->
                <td><?= htmlspecialchars($course['course_id']) ?></td>                
                <td><?= htmlspecialchars($course['course_code']) ?></td>
                <td><?= htmlspecialchars($course['course_name']) ?></td>
                <td><?= htmlspecialchars($course['offering_program']) ?></td>
                <td>
                    <!-- save data in the button to fetch in the edit modal -->
                    <button                    
                    class="btn btn-sm btn-outline-primary edit-college-btn"
                    data-course-id="<?= htmlspecialchars($course['course_id']) ?>"                    
                    data-course-code="<?= htmlspecialchars($course['course_code']) ?>"
                    data-course-name="<?= htmlspecialchars($course['course_name']) ?>"
                    data-course-program="<?= htmlspecialchars($course['offering_program']) ?>"
                    data-bs-toggle="modal"
                    data-bs-target="#editCourseModal"
                    data-action="edit" 
                    ><!-- action is required to be handled by js -->
                    <i class="bi bi-pencil"></i> Edit
                    </button>
                </td>
                
            </tr>
            <?php endforeach; ?>
        
        </tbody>
    </table>