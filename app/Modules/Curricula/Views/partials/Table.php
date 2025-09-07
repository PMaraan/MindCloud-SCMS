<?php // expects: $rows (array), $canEdit (bool), $canDelete (bool) ?>
<div class="table-responsive">
  <table class="table table-bordered table-striped table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th style="width:90px;">ID</th>
        <th style="width:180px;">Curriculum Code</th>
        <th>Title</th>
        <th style="width:160px;">Effective Start</th>
        <th style="width:160px;">Effective End</th>
        <th style="width:200px;" class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!empty($rows)): ?>
      <?php foreach ($rows as $r): ?>
        <tr
          data-curriculum-id="<?= (int)($r['curriculum_id'] ?? 0) ?>"
          data-curriculum-code="<?= htmlspecialchars((string)($r['curriculum_code'] ?? ''), ENT_QUOTES) ?>"
          data-title="<?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES) ?>"
          data-start="<?= htmlspecialchars((string)($r['effective_start'] ?? ''), ENT_QUOTES) ?>"
          data-end="<?= htmlspecialchars((string)($r['effective_end'] ?? ''), ENT_QUOTES) ?>"
        >
          <td><?= (int)($r['curriculum_id'] ?? 0) ?></td>
          <td><?= htmlspecialchars((string)($r['curriculum_code'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['effective_start'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)($r['effective_end'] ?? ''), ENT_QUOTES) ?></td>
          <td class="text-end">
            <div class="btn-group">
              <?php if (!empty($canEdit)): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="modal" data-bs-target="#EditModal">Edit</button>
              <?php endif; ?>
              <?php if (!empty($canDelete)): ?>
                <button type="button" class="btn btn-sm btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#DeleteModal">Delete</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
