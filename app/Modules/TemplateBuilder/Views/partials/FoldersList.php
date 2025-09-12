<?php
// expects: $colleges (array), $esc (callable)
$base = defined('BASE_PATH') ? BASE_PATH : '';
?>
<div class="card">
  <div class="card-body p-0">
    <?php if (empty($colleges)): ?>
      <div class="text-muted text-center py-4">No colleges.</div>
    <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($colleges as $c):
          $href  = $base . '/dashboard?page=templatebuilder&college=' . (int)($c['college_id'] ?? 0);
          $short = $esc($c['short_name'] ?? '');
          $name  = $esc($c['college_name'] ?? '');
        ?>
          <a href="<?= $href ?>" class="list-group-item list-group-item-action d-flex align-items-center">
            <div class="me-3 text-warning"><i class="bi bi-folder-fill fs-3"></i></div>
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
