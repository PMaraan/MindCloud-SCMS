<?php
// /app/Modules/TemplateBuilder/Views/partials/Grid.php
// expects: $templates (array), $esc (callable)
$templates = $templates_local ?? $templates ?? [];
if (empty($templates)) {
  echo '<div class="text-muted">No templates.';
  return;
}
?>
<div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-3">
  <?php foreach ($templates as $t): 
    $tid   = (int)($t['template_id'] ?? 0);
    $title = $esc($t['title'] ?? 'Untitled');
    $upd   = $esc($t['updated_at'] ?? '');
    $scope = $esc($t['scope'] ?? '');
  ?>
    <div class="col">
      <div class="tb-tile card h-100" tabindex="0"
           data-template-id="<?= $tid ?>"
           data-title="<?= $title ?>"
           data-owner="<?= $scope ?>"
           data-updated="<?= $upd ?>">
        <div class="card-body d-flex flex-column align-items-center text-center">
          <?php
            $iconClass = 'bi-file-earmark-text';
            $sc = strtolower((string)($t['scope'] ?? ''));
            if ($sc === 'college')  $iconClass = 'bi-building';
            if ($sc === 'program')  $iconClass = 'bi-mortarboard';
            if ($sc === 'system')   $iconClass = 'bi-globe2';
          ?>
          <div class="tb-icon tb-icon-xl mb-2"><i class="bi <?= $iconClass ?>"></i></div>
          <div class="tb-name fw-semibold" title="<?= $title ?>"><?= $title ?></div>
          <div class="tb-meta text-muted small">
            <span class="me-2"><?= $scope ?></span>
            <span><?= $upd ?></span>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
