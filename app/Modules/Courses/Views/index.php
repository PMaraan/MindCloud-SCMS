<?php /* app/Modules/Courses/Views/index.php */ ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Courses</h2>

    <form class="d-flex" method="GET" action="<?= BASE_PATH ?>/dashboard">
      <input type="hidden" name="page" value="courses">
      <input class="form-control me-2" type="search" name="q" placeholder="Search..."
             value="<?= htmlspecialchars((string)($pager['query'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>

    <?php if (!empty($canCreate)): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#CreateModal">
        + Create
      </button>
    <?php endif; ?>
  </div>

  <?php if (!empty($_SESSION['flash'])): 
        $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= $f['type']==='danger'?'danger':htmlspecialchars($f['type']) ?> alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($f['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php require __DIR__ . '/partials/table.php'; ?>

  <?php
    // Pagination
    $pages = (int)($pager['pages'] ?? 1);
    $page  = (int)($pager['page'] ?? 1);
    $mk = function(int $n) use ($pager) {
      $url = ($pager['baseUrl'] ?? (BASE_PATH . '/dashboard?page=courses')) . '&pg=' . $n;
      $q = (string)($pager['query'] ?? '');
      if ($q !== '') $url .= '&q=' . urlencode($q);
      return $url;
    };
  ?>
  <nav aria-label="Courses pagination">
    <ul class="pagination">
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= $page <= 1 ? '#' : $mk($page - 1) ?>">Prev</a>
      </li>
      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= $mk($p) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= $page >= $pages ? '#' : $mk($page + 1) ?>">Next</a>
      </li>
    </ul>
  </nav>
</div>

<?php
  include __DIR__ . '/partials/CreateModal.php';
  include __DIR__ . '/partials/EditModal.php';
  include __DIR__ . '/partials/DeleteModal.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const editModal = document.getElementById('EditModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget; if (!btn) return;
      const row = btn.closest('tr'); if (!row) return;

      editModal.querySelector('#edit-id').value = row.dataset.courseId || '';
      editModal.querySelector('#edit-course_code').value = row.dataset.courseCode || '';
      editModal.querySelector('#edit-course_name').value = row.dataset.courseName || '';
      editModal.querySelector('#edit-curriculum_id').value = row.dataset.curriculumId || '';

      const sel = editModal.querySelector('#edit-college_id');
      if (sel) sel.value = row.dataset.collegeId || '';
    });
  }

  const delModal = document.getElementById('DeleteModal');
  if (delModal) {
    delModal.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget; if (!btn) return;
      const row = btn.closest('tr'); if (!row) return;

      delModal.querySelector('#delete-id').value = row.dataset.courseId || '';
      const label = delModal.querySelector('#delete-course-label');
      if (label) label.textContent = (row.dataset.courseCode || '') + ' — ' + (row.dataset.courseName || '');
    });
  }
});
</script>
