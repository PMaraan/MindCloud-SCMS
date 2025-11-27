<?php
/**
 * /app/Modules/Syllabi/Views/partials/Grid.php
 * Renders syllabus tiles with the same look/feel as SyllabusTemplates’ cards.
 *
 * Expected data keys: syllabus_id, title/course fields, status, updated_at, owner info, etc.
 */
$rows = $rows_local ?? [];
if (empty($rows)) {
  echo '<div class="text-muted">No syllabi.</div>';
  return;
}

$base = defined('BASE_PATH') ? BASE_PATH : '';
$esc  = $esc ?? static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-3">
  <?php foreach ($rows as $row):
    // Syllabus Id
    $sid    = (int)($row['syllabus_id'] ?? $row['id'] ?? 0);
    // Title
    $title  = (string)($row['title'] ?? '');
    if ($title === '') {
      $title = $course ? ($course . ($courseName ? ' — ' . $courseName : '')) : ($courseName ?: 'Untitled syllabus');
    }
    // College / Owner Department
    $collegeId = (int)($row['college_id'] ?? $row['owner_department_id'] ?? 0);
    $ownerCollege = (string)($row['college_name'] ?? $row['department_name'] ?? '');
    // Programs
    $programNames = $row['program_names'] ?? ($row['program_name'] ?? []);
    if (is_string($programNames)) {
      $trimmed = trim($programNames);
      if ($trimmed !== '' && $trimmed[0] === '{' && substr($trimmed, -1) === '}') {
        $csv = substr($trimmed, 1, -1);
        $programNames = array_filter(array_map('trim', str_getcsv($csv)));
      } elseif ($trimmed !== '') {
        $programNames = [$trimmed];
      } else {
        $programNames = [];
      }
    } elseif (!is_array($programNames)) {
      $programNames = [];
    }
    $programJson = $esc(json_encode(array_values($programNames), JSON_UNESCAPED_UNICODE));
    $programDisplay = $programNames ? implode(', ', $programNames) : '';
    $programIdsRaw = $row['program_ids'] ?? ($row['program_id'] ?? []);
    if (is_string($programIdsRaw)) {
      $trimmedIds = trim($programIdsRaw);
      if ($trimmedIds !== '' && $trimmedIds[0] === '{' && substr($trimmedIds, -1) === '}') {
        $csvIds = substr($trimmedIds, 1, -1);
        $programIds = array_filter(array_map('intval', str_getcsv($csvIds)));
      } elseif ($trimmedIds !== '') {
        $programIds = [(int)$trimmedIds];
      } else {
        $programIds = [];
      }
    } elseif (is_array($programIdsRaw)) {
      $programIds = array_values(array_filter(array_map('intval', $programIdsRaw)));
    } else {
      $programIds = [];
    }
    $primaryProgramId = (int)($row['rep_program_id'] ?? ($programIds[0] ?? 0));
    $programIdsJson = $esc(json_encode($programIds, JSON_UNESCAPED_UNICODE));
    // Course
    $course = trim((string)($row['course_code'] ?? ''));
    $courseName = trim((string)($row['course_name'] ?? ''));
    $courseId   = (int)($row['course_id'] ?? 0);
    $courseCode = (string)($row['course_code'] ?? '');
    $courseName = (string)($row['course_name'] ?? '');
    // Version
    $version = (string)($row['version'] ?? '');
    // Status
    $status = strtolower((string)($row['status'] ?? 'draft'));
    $icon = match ($status) {
      'published', 'active' => 'bi-journal-check',
      'archived'           => 'bi-archive',
      default              => 'bi-journal-text',
    };
    $badgeClass = match ($status) {
      'active', 'published' => 'bg-success',
      'archived'            => 'bg-dark',
      default               => 'bg-secondary',
    };
    // Updated At
    $updatedRaw = (string)($row['updated_at'] ?? $row['updated'] ?? '');
    $updated = $updatedRaw ? date('M j, Y', strtotime($updatedRaw)) : '';
    // Hydrate to RTEditor link
    $href = $base . '/dashboard?page=rteditor&syllabusId=' . $sid;
  ?>
    <div class="col">
      <div class="sy-tile card h-100" tabindex="0" role="button"
           aria-label="Open syllabus: <?= $esc($title) ?>"
           data-syllabus-id="<?= $esc($sid) ?>"
           data-title="<?= $esc($title) ?>"
           data-college-id="<?= $esc($collegeId) ?>"
           data-owner-department-id="<?= $esc($collegeId) ?>"
           data-college-name="<?= $esc($ownerCollege) ?>"
           data-programs="<?= $programJson ?>"
           data-program-ids="<?= $programIdsJson ?>"
           data-program-id="<?= $esc($primaryProgramId) ?>"
           data-course-id="<?= $esc($courseId) ?>"
           data-course-code="<?= $esc($courseCode) ?>"
           data-course-name="<?= $esc($courseName) ?>"
           data-version="<?= $esc($version) ?>"
           data-status="<?= $esc($status) ?>"
           data-updated="<?= $esc($updatedRaw) ?>">
        <div class="card-body d-flex flex-column align-items-center text-center">
          <div class="sy-icon sy-icon-xl mb-2"><i class="bi <?= $esc($icon) ?>"></i></div>
          <div class="sy-name fw-semibold" title="<?= $esc($title) ?>"><?= $esc($title) ?></div>
          <div class="sy-meta text-muted small">
            <?php if ($updated): ?><span class="me-2"><?= $esc($updated) ?></span><?php endif; ?>
            <!-- Temporarily hide status badge
            <span class="badge <?= $badgeClass ?> text-uppercase"><?= $esc($status ?: 'draft') ?></span>
            -->
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
