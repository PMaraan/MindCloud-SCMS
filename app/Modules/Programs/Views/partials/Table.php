<?php /** @var array $rows */ /** @var bool $canEdit */ /** @var bool $canDelete */ ?>
<div class="table-responsive shadow-sm border rounded">
  <table class="table table-bordered table-striped table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>Program</th>
        <th style="width: 240px;">College</th>
        <th style="width: 160px;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="3" class="text-center text-muted py-4">No results.</td></tr>
    <?php else: foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['program_name']) ?></td>
        <td><?= htmlspecialchars($r['college_label'] ?? '') ?></td>
        <td>
          <div class="btn-group">
            <?php if (!empty($canEdit)): ?>
              <button class="btn btn-sm btn-outline-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#editProgramModal"
                      data-program-id="<?= (int)$r['program_id'] ?>"
                      data-program-name="<?= htmlspecialchars($r['program_name'], ENT_QUOTES) ?>"
                      data-college-id="<?= (int)$r['college_id'] ?>">
                <i class="bi bi-pencil"></i>Edit
              </button>
            <?php endif; ?>
            <?php if (!empty($canDelete)): ?>
              <button class="btn btn-sm btn-danger"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteProgramModal"
                      data-program-id="<?= (int)$r['program_id'] ?>"
                      data-program-name="<?= htmlspecialchars($r['program_name'], ENT_QUOTES) ?>">
                <i class="bi bi-trash"></i>Delete
              </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
