<?php
/**
 * /app/Modules/Syllabi/Views/partials/CollegeSection.php
 * Accordion showing college-wide syllabi + per-program syllabi for one college.
 *
 * Expects:
 *   - $college   array  (keys: short_name, college_name)
 *   - $general   array  (flat list of syllabi for the college)
 *   - $programs  array  (list of ['program' => {...}, 'syllabi' => [...]])
 *   - $esc       callable
 *
 * Notes:
 * - Mirrors Syllabus Templates UX, but labels are adapted to Syllabi.
 * - The included Grid.php here is the Syllabi grid partial.
 */
$cLabel = trim(($college['short_name'] ?? '') . ' — ' . ($college['college_name'] ?? ''));
if ($cLabel === '—') $cLabel = 'College';
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
            All College Syllabi
          </button>
        </h2>
        <div id="sy-general-c" class="accordion-collapse collapse show" aria-labelledby="sy-general-h">
          <div class="accordion-body">
            <?php $rows = $general ?? []; include __DIR__ . '/Grid.php'; ?>
          </div>
        </div>
      </div>

      <!-- Program sections -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="sy-programs-h">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                  data-bs-target="#sy-programs-c" aria-expanded="false" aria-controls="sy-programs-c">
            Program Syllabi
          </button>
        </h2>
        <div id="sy-programs-c" class="accordion-collapse collapse" aria-labelledby="sy-programs-h">
          <div class="accordion-body">
            <?php $programSections = $programs ?? []; include __DIR__ . '/ProgramSections.php'; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
