<?php
/**
 * /app/Modules/Syllabi/Views/partials/Grid.php
 * Renders syllabus tiles with the same look/feel as SyllabusTemplates’ cards.
 *
 * Expected data keys: syllabus_id, title/course fields, status, updated_at, owner info, etc.
 */
$rows = $rows_local ?? $rows ?? $syllabi ?? [];
if (empty($rows)) {
  echo '<div class="text-muted">No syllabi.</div>';
  return;
}

$base = defined('BASE_PATH') ? BASE_PATH : '';
$esc  = $esc ?? static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-3">
  <?php foreach ($rows as $row):
    $sid    = (int)($row['syllabus_id'] ?? $row['id'] ?? 0);
    $title  = (string)($row['title'] ?? $row['course_title'] ?? '');
    $course = trim((string)($row['course_code'] ?? ''));
    $courseName = trim((string)($row['course_name'] ?? ''));
    if ($title === '') {
      $title = $course ? ($course . ($courseName ? ' — ' . $courseName : '')) : ($courseName ?: 'Untitled syllabus');
    }
    $status = strtolower((string)($row['status'] ?? 'draft'));
    $updatedRaw = (string)($row['updated_at'] ?? $row['updated'] ?? '');
    $updated = $updatedRaw ? date('M j, Y', strtotime($updatedRaw)) : '';
    $programNames = $row['program_names'] ?? ($row['program_name'] ?? []);
    if (is_string($programNames) && $programNames !== '') {
      $programNames = [$programNames];
    }
    if (!is_array($programNames)) {
      $programNames = [];
    }
    $programDisplay = $programNames ? implode(', ', array_filter(array_map('strval', $programNames))) : '';
    $dataPrograms = $esc(json_encode($programNames, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $ownerCollege = (string)($row['college_name'] ?? $row['department_name'] ?? '');
    $version = (string)($row['version'] ?? '');
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
    $href = $base . '/dashboard?page=rteditor&syllabusId=' . $sid;
  ?>
    <div class="col">
      <div class="sy-tile card h-100" tabindex="0" role="button"
           aria-label="Open syllabus: <?= $esc($title) ?>"
           data-syllabus-id="<?= $esc($sid) ?>"
           data-title="<?= $esc($title) ?>"
           data-status="<?= $esc($status) ?>"
           data-programs="<?= $dataPrograms ?>"
           data-college-name="<?= $esc($ownerCollege) ?>"
           data-updated="<?= $esc($updatedRaw) ?>"
           data-version="<?= $esc($version) ?>">
        <div class="card-body d-flex flex-column align-items-center text-center">
          <div class="sy-icon sy-icon-xl mb-2"><i class="bi <?= $esc($icon) ?>"></i></div>
          <div class="sy-name fw-semibold" title="<?= $esc($title) ?>"><?= $esc($title) ?></div>
          <div class="sy-meta text-muted small">
            <?php if ($updated): ?><span class="me-2"><?= $esc($updated) ?></span><?php endif; ?>
            <span class="badge <?= $badgeClass ?> text-uppercase"><?= $esc($status ?: 'draft') ?></span>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
