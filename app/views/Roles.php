<div class="row">
    <?php
        require_once __DIR__ . "/../models/PostgresDatabase.php"; //load database
        $pdo = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
    ?>
    <div class="container">
        <h1>Roles</h1><br>
    </div>

    <div class="container">
        <h2>Create Roles</h2><br>
    </div>

    <div class="container">
        <h2>View Roles</h2><br>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Role ID</th>
                    <th>Role Name</th>
                    <th>Level</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $rolesTable = $pdo->getAllRoles();
                    foreach ($rolesTable as $role){
                ?>
                <tr>
                    <td><?= htmlspecialchars($role['role_id']) ?></td>
                    <td><?= htmlspecialchars($role['name']) ?></td>
                    <td><?= htmlspecialchars($role['level']) ?></td>
                </tr>
                <?php
                    }
                ?>
                

            </tbody>
        </table>
        

    </div>
    <div class="container">
        <h2>Edit Roles</h2><br>
    </div>
    <div class="container">
        <h2>Delete Roles</h2><br>
    </div>
    <div class="container">
        <h2>Allocate Roles</h2><br>
    </div>
    
</div>