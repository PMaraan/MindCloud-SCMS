<?php
// /app/Modules/Syllabi/Views/partials/ProgramSections.php
/**
 * Program accordion panels inside the college accordion.
 * - Expects $programSections entries with keys: key, label, program?, syllabi[]
 */
if (empty($programSections)) {
  echo '<div class="text-muted">No program syllabi.</div>';
  return;
}
?>
<div class="accordion" id="sy-program-accordion">
  <?php foreach ($programSections as $idx => $entry):
    $panelId = $entry['key'] ?? ('program-' . $idx);
    $label   = $entry['label'] ?? 'Program';
    $program = $entry['program'] ?? [];
    $syllabi = $entry['syllabi'] ?? [];
  ?>
  <div class="accordion-item mb-2">
    <h2 class="accordion-header" id="<?= $esc($panelId) ?>-h">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
              data-bs-target="#<?= $esc($panelId) ?>-c" aria-expanded="false" aria-controls="<?= $esc($panelId) ?>-c">
        <?= $esc($label) ?>
      </button>
    </h2>
    <div id="<?= $esc($panelId) ?>-c" class="accordion-collapse collapse" aria-labelledby="<?= $esc($panelId) ?>-h">
      <div class="accordion-body">
        <?php
          // Use the Syllabi grid to render tiles; it expects $rows_local
          $rows_local = $syllabi;
          include __DIR__ . '/Grid.php';
        ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
