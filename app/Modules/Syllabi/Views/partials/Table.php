<?php
// /app/Modules/Syllabi/Views/partials/Table.php
/**
 * Placeholder Table partial (to be replaced by your real grid/list).
 * Expects: $rows (array), $esc, $canEdit, $canDelete
 */
?>
<div class="card">
  <div class="card-body">
    <?php if (empty($rows)): ?>
      <div class="text-muted">No syllabi yet.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Title</th>
              <th>Course</th>
              <th>Section</th>
              <th>Updated</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r):
              $id    = (int)($r['syllabus_id'] ?? 0);
              $title = $esc($r['title'] ?? 'Untitled');
              $course= $esc($r['course'] ?? '');
              $sec   = $esc($r['section'] ?? '');
              $upd   = $esc($r['updated_at'] ?? '');
            ?>
            <tr data-syllabus-id="<?= $id ?>">
              <td><?= $title ?></td>
              <td><?= $course ?></td>
              <td><?= $sec ?></td>
              <td><span class="text-muted small"><?= $upd ?></span></td>
              <td class="text-end">
                <!-- Prepare an "Open" action; real wiring goes in RTEditor step -->
                <a class="btn btn-sm btn-primary"
                   href="#"
                   data-syllabus-id="<?= $id ?>"
                   title="Open in Editor"
                   onclick="/* TODO: route to RTEditor; e.g. location.href='<?= $esc($base ?? (defined('BASE_PATH')?BASE_PATH:'')) ?>/dashboard?page=rteditor&action=open&sid=<?= $id ?>' */ return false;">
                  <i class="bi bi-pencil-square"></i> Open
                </a>
                <?php if (!empty($canEdit)): ?>
                  <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#syEditModal"
                          data-syllabus-id="<?= $id ?>">
                    Edit
                  </button>
                <?php endif; ?>
                <?php if (!empty($canDelete)): ?>
                  <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#syDeleteModal"
                          data-syllabus-id="<?= $id ?>">
                    Delete
                  </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
