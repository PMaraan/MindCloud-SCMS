<?php
/**
 * /app/Modules/Syllabi/Views/partials/FoldersList.php
 * Renders clickable college “folders” for Syllabi.
 *
 * Expects:
 *   - $colleges (array)   // [{ college_id, short_name, college_name }, ...]
 *   - $esc (callable)
 *
 * Notes:
 *   - Mirrors Syllabus Templates’ FoldersList but targets ?page=syllabi.
 */
$base    = defined('BASE_PATH') ? BASE_PATH : '';
$pageKey = isset($PAGE_KEY) ? $PAGE_KEY : 'syllabi';
?>
<div class="card">
  <div class="card-body p-0">
    <?php if (empty($colleges)): ?>
      <div class="text-muted text-center py-4">No colleges.</div>
    <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($colleges as $c):
          $href  = $base . '/dashboard?page=' . $pageKey . '&college=' . (int)($c['college_id'] ?? 0);
          $short = $esc($c['short_name'] ?? '');
          $name  = $esc($c['college_name'] ?? '');
        ?>
          <a href="<?= $href ?>" class="list-group-item list-group-item-action d-flex align-items-center"
             aria-label="Open college: <?= $short ?> — <?= $name ?>">
            <div class="me-3 text-warning"><i class="bi bi-folder-fill fs-3" aria-hidden="true"></i></div>
            <div class="flex-grow-1">
              <div class="fw-semibold"><?= $short ?></div>
              <div class="text-muted small"><?= $name ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
