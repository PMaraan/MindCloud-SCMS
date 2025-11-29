<?php
// /app/Modules/Syllabi/Views/partials/CollegeSection.php
/**
 * College-level accordion wrapper.
 * - Expects $college      (array)
 * - Expects $accordions   (array) from buildAccordionData()
 * - Expects $esc          (callable)
 */
$cLabel = trim(($college['short_name'] ?? '') . ' — ' . ($college['college_name'] ?? ''));
if ($cLabel === '—') {
    $cLabel = 'College';
}

$generalEntry = null;
$programEntries = [];

foreach ($accordions ?? [] as $entry) {
    if (($entry['key'] ?? '') === 'general') {
        $generalEntry = $entry;
        continue;
    }
    $programEntries[] = $entry;
}

$generalSyllabi = $generalEntry['syllabi'] ?? [];
$generalLabel   = $generalEntry['label'] ?? 'Shared Syllabi';
?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="bi bi-folder2-open text-warning"></i> <strong><?= $esc($cLabel) ?></strong></div>
  </div>

  <div class="card-body">
    <div class="accordion mb-3" id="sy-college-accordion">
      <!-- College-wide syllabi -->
      <div class="accordion-item mb-2">
        <h2 class="accordion-header" id="sy-general-h">
          <button class="accordion-button" type="button" data-bs-toggle="collapse"
                  data-bs-target="#sy-general-c" aria-expanded="true" aria-controls="sy-general-c">
            <?= $esc($generalLabel) ?>
          </button>
        </h2>
        <div id="sy-general-c" class="accordion-collapse collapse show" aria-labelledby="sy-general-h">
          <div class="accordion-body">
            <?php
              $rows_local = $generalSyllabi;
              include __DIR__ . '/Grid.php';
            ?>
          </div>
        </div>
      </div>

      <!-- Program sections -->
       <?php if ($mode === 'global-folders' || $mode === 'college'): ?>
      <div class="accordion-item">
        <h2 class="accordion-header" id="sy-programs-h">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                  data-bs-target="#sy-programs-c" aria-expanded="false" aria-controls="sy-programs-c">
            Program Syllabi
          </button>
        </h2>
        <div id="sy-programs-c" class="accordion-collapse collapse" aria-labelledby="sy-programs-h">
          <div class="accordion-body">
            <?php
              $programSections = $programEntries;
              include __DIR__ . '/ProgramSections.php';
            ?>
          </div>
        </div>
      </div>
       <?php endif; ?>
    </div>
  </div>
</div>
