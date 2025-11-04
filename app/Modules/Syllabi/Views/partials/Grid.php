<?php
/**
 * /app/Modules/Syllabi/Views/partials/Grid.php
 * Renders a responsive grid of syllabus tiles.
 *
 * Expects: $rows (array) or $syllabi or $rows_local, and $esc (callable).
 * Row shape (from SyllabiModel::listSyllabi SELECT):
 *   - syllabus_id, course_id, course_code, course_name,
 *     program_id, program_name, version, status, updated_at, filename
 */
$rows = $rows_local ?? $rows ?? $syllabi ?? [];
if (empty($rows)) {
  echo '<div class="text-muted">No syllabi.</div>';
  return;
}

$base = defined('BASE_PATH') ? BASE_PATH : '';
?>
<div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-3">
  <?php foreach ($rows as $r):
    $sid   = (int)($r['syllabus_id'] ?? 0);
    $cc    = trim((string)($r['course_code'] ?? ''));
    $cn    = trim((string)($r['course_name'] ?? ''));
    $pn    = trim((string)($r['program_name'] ?? ''));
    $ver   = trim((string)($r['version'] ?? ''));
    $stat  = trim((string)($r['status'] ?? ''));
    $upd   = $esc($r['updated_at'] ?? '');
    $title = $cc !== '' ? ($cc . ' — ' . ($cn !== '' ? $cn : 'Course')) : ($cn !== '' ? $cn : 'Untitled Syllabus');
    if ($ver !== '') $title .= ' ('.$ver.')';
    $href  = $base . '/dashboard?page=rteditor&syllabusId=' . $sid;
  ?>
    <div class="col">
      <a href="<?= $href ?>" class="text-decoration-none">
        <div class="sy-tile card h-100" tabindex="0" role="button"
             aria-label="Open syllabus: <?= $esc($title) ?>"
             data-syllabus-id="<?= $sid ?>"
             data-title="<?= $esc($title) ?>"
             data-program="<?= $esc($pn) ?>"
             data-updated="<?= $upd ?>"
             data-status="<?= $esc($stat) ?>">
          <div class="card-body d-flex flex-column align-items-center text-center">
            <?php
              // simple icon logic: draft vs others
              $iconClass = ($stat && strtolower($stat) !== 'draft') ? 'bi-journal-check' : 'bi-journal-text';
            ?>
            <div class="sy-icon sy-icon-xl mb-2"><i class="bi <?= $iconClass ?>"></i></div>
            <div class="sy-name fw-semibold" title="<?= $esc($title) ?>"><?= $esc($title) ?></div>
            <div class="sy-meta text-muted small">
              <?php if ($pn !== ''): ?><span class="me-2"><?= $esc($pn) ?></span><?php endif; ?>
              <?php if ($stat !== ''): ?><span class="me-2"><?= $esc($stat) ?></span><?php endif; ?>
              <span><?= $upd ?></span>
            </div>
          </div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>
