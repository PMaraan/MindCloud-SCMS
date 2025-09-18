<?php 
// app/Modules/Colleges/Views/partials/Table.php
// Expects the following variables from CollegesController.php
/** @var array $rows */
/** @var bool  $canEdit */
/** @var bool  $canDelete */
?>
<div class="table-responsive shadow-sm border rounded">
  <table class="table table-bordered table-striped table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th style="width:90px;">ID</th>
        <th style="width:160px;">Short Name</th>
        <th>College Name</th>
        <th style="width:160px;">Dean ID No</th>
        <th style="width:220px;">Dean Full Name</th>
        <th style="width:180px;" class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!empty($rows)): ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)($r['college_id'] ?? 0) ?></td>
          <td><?= htmlspecialchars((string)($r['short_name'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['college_name'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)(($r['dean_id_no']      ?? '') ?: '-unassigned-'), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)(($r['dean_full_name']  ?? '') ?: '-unassigned-'), ENT_QUOTES) ?></td>
          <td class="text-end">
            <?php if ($canEdit): ?>
              <button class="btn btn-sm btn-primary <?= $canDelete ? 'me-2' : '' ?>"
                      data-bs-toggle="modal"
                      data-bs-target="#editCollegesModal"
                      data-id="<?= (int)$r['college_id'] ?>"
                      data-short_name="<?= htmlspecialchars((string)$r['short_name'], ENT_QUOTES) ?>"
                      data-college_name="<?= htmlspecialchars((string)$r['college_name'], ENT_QUOTES) ?>"
                      data-dean_id_no="<?= htmlspecialchars((string)($r['dean_id_no'] ?? ''), ENT_QUOTES) ?>">
                <i class="bi bi-pencil"></i> Edit
              </button>
            <?php endif; ?>
            <?php if ($canDelete): ?>
              <button class="btn btn-sm btn-danger"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteCollegesModal"
                      data-id="<?= (int)$r['college_id'] ?>"
                      data-short_name="<?= htmlspecialchars((string)$r['short_name'], ENT_QUOTES) ?>"
                      data-college_name="<?= htmlspecialchars((string)$r['college_name'], ENT_QUOTES) ?>">
                <i class="bi bi-trash"></i> Delete
              </button>
            <?php endif; ?>
            <?php if (!$canEdit && !$canDelete): ?>
              <span class="text-muted">No action</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="6" class="text-center">No records found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
