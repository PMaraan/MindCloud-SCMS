<?php
// /app/Modules/TemplateBuilder/Views/index.php
// Expects: $mode ('multi-college' or 'college-drive'),
//          $colleges (array), $templates (array), $currentCollege (nullable array)

if (!defined('BASE_PATH')) {
    // When opened directly (outside Dashboard flow), compute $ASSET_BASE
    $reqUri = $_SERVER['REQUEST_URI'] ?? '';
    $projectBase = '';
    if ($reqUri !== '') {
        $parts = explode('/app/', $reqUri, 2);
        $projectBase = rtrim($parts[0] ?? '', '/');
    }
    $ASSET_BASE = ($projectBase !== '' ? $projectBase : '') . '/public';
} else {
    $ASSET_BASE = BASE_PATH;
}

// Safety helpers
$esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?= $ASSET_BASE ?>/assets/css/TemplateBuilder-Scaffold.css">
<script defer src="<?= $ASSET_BASE ?>/assets/js/TemplateBuilder-Scaffold.js"></script>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-0">Template Builder</h2>
      <div class="text-muted small">
        <?= $esc($mode === 'multi-college' ? 'Select a college to view its syllabus templates.' : 'Browse syllabus templates for the selected college.') ?>
      </div>
    </div>
    <div>
      <?php if ($mode === 'college-drive' && !empty($currentCollege)): ?>
        <a href="<?= $esc(BASE_PATH) ?>/dashboard?page=templatebuilder" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left-short"></i> All Colleges
        </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($mode === 'multi-college'): ?>
    <!-- ===================== FOLDER LIST VIEW (non-college-bound roles) ===================== -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:64px;">&nbsp;</th>
                <th style="width:180px;">Short Name</th>
                <th>College</th>
                <th style="width:160px;" class="text-end">Open</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($colleges)): ?>
                <?php foreach ($colleges as $c): ?>
                  <tr>
                    <td class="text-center">
                      <i class="bi bi-folder-fill fs-3 text-warning"></i>
                    </td>
                    <td class="fw-medium"><?= $esc($c['short_name'] ?? '') ?></td>
                    <td><?= $esc($c['college_name'] ?? '') ?></td>
                    <td class="text-end">
                      <a class="btn btn-primary btn-sm"
                         href="<?= $esc(BASE_PATH) ?>/dashboard?page=templatebuilder&college=<?= (int)($c['college_id'] ?? 0) ?>">
                        Open
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">No colleges available.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- ===================== DRIVE-LIKE VIEW (college-bound roles) ===================== -->
    <div class="row g-3">
      <div class="col-12 col-lg-8 col-xl-9">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-folder2-open text-warning"></i>
              <strong>
                <?= $esc($currentCollege['short_name'] ?? 'Selected College') ?>
              </strong>
              <span class="text-muted">— <?= $esc($currentCollege['college_name'] ?? '') ?></span>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-outline-secondary btn-sm" id="tb-refresh"><i class="bi bi-arrow-repeat"></i></button>
              <button class="btn btn-primary btn-sm" id="tb-new-template"><i class="bi bi-file-earmark-plus"></i> New Template</button>
            </div>
          </div>
          <div class="card-body">
            <?php if (empty($templates)): ?>
              <div class="text-center text-muted py-5">No templates yet.</div>
            <?php else: ?>
              <!-- Grid like Google Drive -->
              <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3" id="tb-grid">
                <?php foreach ($templates as $t): ?>
                  <div class="col">
                    <div class="tb-card card h-100" tabindex="0"
                         data-template-id="<?= (int)$t['template_id'] ?>"
                         data-title="<?= $esc($t['title']) ?>"
                         data-course-code="<?= $esc($t['course_code']) ?>"
                         data-updated="<?= $esc($t['updated_at']) ?>"
                         data-owner="<?= $esc($t['owner']) ?>">
                      <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                          <i class="bi bi-file-earmark-text fs-1"></i>
                        </div>
                        <div class="fw-semibold mb-1"><?= $esc($t['title']) ?></div>
                        <div class="text-muted small">Code: <?= $esc($t['course_code']) ?></div>
                        <div class="text-muted small mt-auto">Updated: <?= $esc($t['updated_at']) ?></div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Right Pane -->
      <div class="col-12 col-lg-4 col-xl-3">
        <div class="card" id="tb-info-pane">
          <div class="card-header">
            <strong>Details</strong>
          </div>
          <div class="card-body">
            <div class="text-muted">Select a template to see details.</div>
            <dl class="row mt-3 mb-0 d-none" id="tb-info">
              <dt class="col-4">Title</dt>
              <dd class="col-8" id="tb-i-title"></dd>
              <dt class="col-4">Course</dt>
              <dd class="col-8" id="tb-i-course"></dd>
              <dt class="col-4">Owner</dt>
              <dd class="col-8" id="tb-i-owner"></dd>
              <dt class="col-4">Updated</dt>
              <dd class="col-8" id="tb-i-updated"></dd>
              <dt class="col-4">Actions</dt>
              <dd class="col-8">
                <div class="d-flex gap-2">
                  <button class="btn btn-sm btn-primary" id="tb-open">Open</button>
                  <button class="btn btn-sm btn-outline-secondary" id="tb-duplicate">Duplicate</button>
                </div>
              </dd>
            </dl>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
