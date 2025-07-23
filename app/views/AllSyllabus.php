<?php
$colleges = [
  ['code' => 'CCS', 'name' => 'College of Computer Studies'],
  ['code' => 'CON', 'name' => 'College of Nursing'],
];
?>

<div class="container-fluid py-4 px-0">
  <h4 class="mb-3 ps-3">University</h4>

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
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 40px;"></th>
            <th>Code</th>
            <th>College</th>
          </tr>
        </thead>
        <tbody id="collegeList">
          <?php foreach ($colleges as $college): ?>
            <tr>
              <td><i class="bi bi-folder-fill folder-icon"></i></td>
              <td><?= $college['code'] ?></td>
              <td>
                <a
                  href="#"
                  data-page="Syllabus"
                  data-college="<?= $college['code'] ?>"
                  class="text-decoration-none text-dark college-link"
                >
                  <?= $college['name'] ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
