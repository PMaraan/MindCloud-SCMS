<?php
// /app/Modules/TemplateBuilder/Views/partials/CollegeSection.php
// expects: $college (arr), $general (arr), $programs (arr), $esc (callable)
$cLabel = trim(($college['short_name'] ?? '') . ' — ' . ($college['college_name'] ?? ''));
if ($cLabel === '—') $cLabel = 'College';
?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="bi bi-folder2-open text-warning"></i> <strong><?= $esc($cLabel) ?></strong></div>
  </div>
  <div class="card-body">
    <div class="accordion mb-3" id="tb-college-accordion">
      <!-- General -->
      <div class="accordion-item mb-2">
        <h2 class="accordion-header" id="tb-general-h">
          <button class="accordion-button" type="button" data-bs-toggle="collapse"
                  data-bs-target="#tb-general-c" aria-expanded="true" aria-controls="tb-general-c">
            General Templates
          </button>
        </h2>
        <div id="tb-general-c" class="accordion-collapse collapse show" aria-labelledby="tb-general-h">
          <div class="accordion-body">
            <?php $templates_local = $general ?? []; include __DIR__ . '/Grid.php'; ?>
          </div>
        </div>
      </div>

      <!-- Program sections -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="tb-programs-h">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                  data-bs-target="#tb-programs-c" aria-expanded="false" aria-controls="tb-programs-c">
            Program Templates
          </button>
        </h2>
        <div id="tb-programs-c" class="accordion-collapse collapse" aria-labelledby="tb-programs-h">
          <div class="accordion-body">
            <?php $programSections = $programs ?? []; include __DIR__ . '/ProgramSections.php'; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
