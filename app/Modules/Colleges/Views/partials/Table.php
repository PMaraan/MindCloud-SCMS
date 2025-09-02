<?php /* app/Modules/College/Views/partials/Table.php */ ?>
<?php // expects: $rows (array), $canEdit (bool), $canDelete (bool) ?>
<div class="table-responsive">
  <table class="table table-bordered table-striped table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:90px;">ID</th>
        <th style="width:160px;">Short Name</th>
        <th>College Name</th>
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
          <td class="text-end">
            <?php if ($canEdit): ?>
              <button class="btn btn-sm btn-outline-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#editCollegesModal"
                      data-id="<?= (int)$r['college_id'] ?>"
                      data-short_name="<?= htmlspecialchars((string)$r['short_name'], ENT_QUOTES) ?>"
                      data-college_name="<?= htmlspecialchars((string)$r['college_name'], ENT_QUOTES) ?>">
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
      <tr><td colspan="4" class="text-center">No records found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const editModal = document.getElementById('editModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', evt => {
      const btn = evt.relatedTarget;
      if (!btn) return;
      editModal.querySelector('[name="id"]').value            = btn.getAttribute('data-id') || '';
      editModal.querySelector('[name="short_name"]').value    = btn.getAttribute('data-short_name') || '';
      editModal.querySelector('[name="college_name"]').value  = btn.getAttribute('data-college_name') || '';
    });
  }

  const delModal = document.getElementById('deleteModal');
  if (delModal) {
    delModal.addEventListener('show.bs.modal', evt => {
      const btn = evt.relatedTarget;
      if (!btn) return;

      const id          = btn.getAttribute('data-id') || '';
      const shortName   = btn.getAttribute('data-short_name') || '';
      const collegeName = btn.getAttribute('data-college_name') || '';

      // hidden input for submission
      const inputId = delModal.querySelector('input[name="id"]');
      if (inputId) inputId.value = id;

      // display fields
      const spanId = delModal.querySelector('.js-del-id');
      if (spanId) spanId.textContent = id;

      const spanShort = delModal.querySelector('.js-del-short');
      if (spanShort) spanShort.textContent = shortName;

      const spanName = delModal.querySelector('.js-del-name');
      if (spanName) spanName.textContent = collegeName;
    });
  }
});
</script>
