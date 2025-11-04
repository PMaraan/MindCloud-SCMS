<?php
/**
 * /app/Modules/Syllabi/Views/partials/ProgramSections.php
 * Accordion of per-program syllabus grids for a college.
 *
 * Expects:
 *   - $programSections (array) // each item: ['program' => [...], 'syllabi' => [...]]
 *   - $esc (callable)
 */
if (empty($programSections)) {
  echo '<div class="text-muted">No program syllabi.</div>';
  return;
}
?>
<div class="accordion" id="sy-program-accordion">
  <?php foreach ($programSections as $idx => $sec):
    $p        = $sec['program'] ?? [];
    $syllabi  = $sec['syllabi'] ?? [];
    $pid      = (int)($p['program_id'] ?? 0);
    $label    = trim((string)($p['program_name'] ?? 'Program'));
    $itemId   = 'sy-prog-' . ($pid ?: $idx);
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
        <?php
          // Use the Syllabi grid; it expects $rows (or $rows_local)
          $rows_local = $syllabi;
          include __DIR__ . '/Grid.php';
        ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
