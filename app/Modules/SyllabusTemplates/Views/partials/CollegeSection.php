<?php
/**
 * /app/Modules/SyllabusTemplates/Views/partials/CollegeSection.php
 * Accordion showing General templates + Program template sections for one college.
 */
// expects: $college (arr), $general (arr), $programs (arr), $esc (callable)
$cLabel = trim(($college['short_name'] ?? '') . ' — ' . ($college['college_name'] ?? ''));
if ($cLabel === '—') $cLabel = 'College';
?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div><i class="bi bi-folder2-open text-warning"></i> <strong><?= $esc($cLabel) ?></strong></div>
  </div>
  <div class="card-body">
    <?php
      // Split $general into global and college lists (accept both 'global' and legacy 'system')
      $globalTemplates  = [];
      $collegeTemplates = [];

      if (!empty($general) && is_array($general)) {
          foreach ($general as $t) {
              $scope = strtolower((string)($t['scope'] ?? ''));

              // Accept both 'global' (new name) and 'system' (legacy)
              if ($scope === 'global' || $scope === 'system') {
                  $globalTemplates[] = $t;
              } elseif ($scope === 'college') {
                  $collegeTemplates[] = $t;
              } else {
                  // keep unknowns under collegeTemplates as a safe default
                  $collegeTemplates[] = $t;
              }
          }
      }

      // Helper to render tiles (keeps markup consistent with existing tiles)
      $renderTile = function(array $t, callable $esc) {
          $tid  = (int)($t['template_id'] ?? $t['id'] ?? 0);
          $title = $esc((string)($t['title'] ?? $t['template_title'] ?? 'Untitled'));
          $scope = $esc((string)($t['scope'] ?? 'system'));
          $owner = $esc((string)($t['owner'] ?? $scope));
          $updated = $esc((string)($t['updated_at'] ?? $t['updated'] ?? ''));
          $ownerDeptId = (int)($t['owner_department_id'] ?? $t['department_id'] ?? $t['college_id'] ?? 0);
          $programId   = (int)($t['program_id'] ?? 0);
          $ownerProgId = (int)($t['owner_program_id'] ?? 0);
          $courseId    = (int)($t['course_id'] ?? 0);
          $version     = $esc((string)($t['version'] ?? ''));
          $status      = $esc((string)($t['status'] ?? 'draft'));

          // Optional human-readable names (used by JS Details pane).
          // Prefer "short_name — full name" when short_name exists.
          $collegeShort = trim((string)($t['college_short_name'] ?? ''));
          $collegeFull  = trim((string)($t['college_name'] ?? $t['department_name'] ?? ''));
          if ($collegeShort !== '') {
              $collegeName = $esc($collegeShort . ' — ' . $collegeFull);
          } else {
              $collegeName = $esc($collegeFull);
          }
          $programName = $esc((string)($t['program_name'] ?? ''));
          $courseName  = $esc((string)($t['course_name'] ?? ''));

          // icon class by scope
          $icon = 'bi bi-file-earmark-text';
          if ($scope === 'global' || $scope === 'system') $icon = 'bi bi-globe2';
          if ($scope === 'college') $icon = 'bi bi-building';
          if ($scope === 'program') $icon = 'bi bi-mortarboard';
          if ($scope === 'course')  $icon = 'bi bi-file-earmark-text';

          return <<<HTML
          <div class="col">
            <div class="tb-tile card h-100" tabindex="0" role="button"
                aria-label="Open template: {$title}"
                data-template-id="{$tid}"
                data-title="{$title}"
                data-owner="{$owner}"
                data-updated="{$updated}"
                data-scope="{$scope}"
                data-owner-department-id="{$ownerDeptId}"
                data-program-id="{$programId}"
                data-owner-program-id="{$ownerProgId}"
                data-course-id="{$courseId}"
                data-college-name="{$collegeName}"
                data-program-name="{$programName}"
                data-course-name="{$courseName}"
                data-version="{$version}"
                data-status="{$status}">
              <div class="card-body d-flex flex-column align-items-center text-center">
                <div class="tb-icon tb-icon-xl mb-2"><i class="{$icon}"></i></div>
                <div class="tb-name fw-semibold" title="{$title}">{$title}</div>
                <div class="tb-meta text-muted small">
                  <span class="me-2">{$owner}</span>
                  <span>{$updated}</span>
                </div>
              </div>
            </div>
          </div>
          HTML;
      };
      ?>

      <div class="accordion mb-3" id="tb-college-accordion">

        <!-- Global Templates -->
        <div class="accordion-item mb-2">
          <h2 class="accordion-header" id="tb-global-h">
            <button class="accordion-button <?= empty($globalTemplates) ? 'collapsed' : '' ?>" type="button"
                    data-bs-toggle="collapse" data-bs-target="#tb-global-c"
                    aria-expanded="<?= empty($globalTemplates) ? 'false' : 'true' ?>"
                    aria-controls="tb-global-c">
              Global Templates
            </button>
          </h2>

          <div id="tb-global-c" class="accordion-collapse collapse <?= empty($globalTemplates) ? '' : 'show' ?>" aria-labelledby="tb-global-h">
            <div class="accordion-body">
              <?php if (empty($globalTemplates)): ?>
                <div class="text-muted">No global templates.</div>
              <?php else: ?>
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-3">
                  <?php foreach ($globalTemplates as $t): echo $renderTile($t, $esc); endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- College Templates (accordion header text = college name) -->
        <div class="accordion-item mb-2">
          <h2 class="accordion-header" id="tb-college-general-h">
            <button class="accordion-button <?= empty($collegeTemplates) ? 'collapsed' : '' ?>" type="button"
                    data-bs-toggle="collapse" data-bs-target="#tb-college-general-c"
                    aria-expanded="<?= empty($collegeTemplates) ? 'false' : 'true' ?>"
                    aria-controls="tb-college-general-c">
              <?= $esc(($college['college_name'] ?? $college['department_name'] ?? 'College')) ?> General Templates
            </button>
          </h2>

          <div id="tb-college-general-c" class="accordion-collapse collapse <?= empty($collegeTemplates) ? '' : 'show' ?>" aria-labelledby="tb-college-general-h">
            <div class="accordion-body">
              <?php if (empty($collegeTemplates)): ?>
                <div class="text-muted">No college general templates.</div>
              <?php else: ?>
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-3">
                  <?php foreach ($collegeTemplates as $t): echo $renderTile($t, $esc); endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div>

    <?php
      // Render Programs accordion: expects $programs to be an array like:
      // [ ['program' => ['program_id'=>..., 'program_name'=>...], 'templates' => [ ...templates... ]], ... ]
      // If your controller uses a different var name, rename $programs accordingly.
      $programsList = $programs ?? []; // safe fallback
    ?>

    <?php if (!empty($programsList) && is_array($programsList)): ?>
      <div class="accordion mb-3" id="tb-programs-outer-accordion">
        <div class="accordion-item">
          <h2 class="accordion-header" id="tb-programs-h">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#tb-programs-c"
                    aria-expanded="false" aria-controls="tb-programs-c">
              Program Templates
            </button>
          </h2>

          <div id="tb-programs-c" class="accordion-collapse collapse" aria-labelledby="tb-programs-h">
            <div class="accordion-body">
              <div class="accordion" id="tb-program-accordion">
                <?php foreach ($programsList as $progBlock):
                  $prog = $progBlock['program'] ?? [];
                  $progTemplates = $progBlock['templates'] ?? [];
                  $progId = (int)($prog['program_id'] ?? $prog['id'] ?? 0);
                  $progName = $esc((string)($prog['program_name'] ?? $prog['name'] ?? 'Program'));
                  $panelId = 'tb-prog-' . $progId;
                  $collapseId = 'tb-prog-' . $progId . '-c';
                ?>
                  <div class="accordion-item mb-2">
                    <h2 class="accordion-header" id="<?= $esc($panelId . '-h') ?>">
                      <button class="accordion-button collapsed" type="button"
                              data-bs-toggle="collapse"
                              data-bs-target="#<?= $esc($collapseId) ?>"
                              aria-expanded="false"
                              aria-controls="<?= $esc($collapseId) ?>">
                        <?= $progName ?>
                      </button>
                    </h2>

                    <div id="<?= $esc($collapseId) ?>" class="accordion-collapse collapse" aria-labelledby="<?= $esc($panelId . '-h') ?>">
                      <div class="accordion-body">
                        <?php if (empty($progTemplates)): ?>
                          <div class="text-muted">No templates.</div>
                        <?php else: ?>
                          <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-3">
                            <?php foreach ($progTemplates as $t): echo $renderTile($t, $esc); endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>
