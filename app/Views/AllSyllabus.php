<?php
/*
$colleges = [
  ['short_name' => 'CCS', 'college_name' => 'College of Computer Studies'],
  ['short_name' => 'CON', 'college_name' => 'College of Nursing'],
];
*/
$db = new Datacontroller();
?>

<div class="container-fluid py-4 px-0">
  <h4 class="mb-3 ps-3">Syllabus</h4>

  <!-- 🔍 Search Bar -->
  <div class="mb-3 px-3">
    <label for="searchBar" class="w-100">
      <div class="input-group">
        <span class="input-group-text bg-white border-end-0">
          <i class="bi bi-search text-muted"></i>
        </span>
        <input
          type="text"
          class="form-control border-start-0"
          placeholder="Search"
          id="searchBar"
        />
      </div>
    </label>
  </div>

  <!-- 🗂️ College List -->
  <div class="card rounded-0 border-0 clickable-card">
    <div class="table-responsive">
      <table class="table mb-0 table-hover">
        <thead class="table-light">
          <tr>
            <th style="width: 40px;"></th>
            <th>Code</th>
            <th>College</th>
          </tr>
        </thead>
        <tbody id="collegeList">
          <?php 
            // Get colleges
            $query = $db->getAllColleges();

            if ($query && $query['success']) {
              $colleges = $query['db'];
              if (!empty($colleges)) {
                
                foreach ($colleges as $college): ?>
                <tr class="collegeEntry" data-college-id="<?= $college['college_id'] ?>">
                  <td><i class="bi bi-folder-fill folder-icon"></i></td>
                  <td><?= $college['short_name'] ?></td>
                  <td>
                    <a
                      href="#"
                      data-page="Syllabus"
                      data-college="<?= $college['short_name'] ?>"
                      class="text-decoration-none text-dark college-link"
                    >
                      <?= $college['college_name'] ?>
                    </a>
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
    </div>
  </div>
</div>
