<?php
/**
 * /app/Modules/SyllabusTemplates/Views/partials/ProgramSections.php
 * Accordion of per-program template grids for a college.
 * Expects: $programSections (array), $esc (callable).
 */
if (empty($programSections)) {
  echo '<div class="text-muted">No program templates.</div>';
  return;
}
?>
<div class="accordion" id="tb-program-accordion">
  <?php foreach ($programSections as $idx => $sec):
    $p = $sec['program'] ?? [];
    $templates = $sec['templates'] ?? [];
    $pid = (int)($p['program_id'] ?? 0);
    $label = trim((string)($p['program_name'] ?? 'Program'));
    $itemId = 'tb-prog-' . ($pid ?: $idx);
  ?>
  <div class="accordion-item mb-2">
    <h2 class="accordion-header" id="<?= $itemId ?>-h">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
              data-bs-target="#<?= $itemId ?>-c" aria-expanded="false" aria-controls="<?= $itemId ?>-c">
        <?= $esc($label) ?>
      </button>
    </h2>
    <div id="<?= $itemId ?>-c" class="accordion-collapse collapse" aria-labelledby="<?= $itemId ?>-h">
      <div class="accordion-body">
        <?php $templates_local = $templates; include __DIR__ . '/Grid.php'; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
