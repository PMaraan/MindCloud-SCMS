<?php 
// app/Modules/Colleges/Views/partials/Table.php
// Expects the following variables from CollegesController.php
/** @var array $rows */
/** @var bool  $canEdit */
/** @var bool  $canDelete */
?>
<div class="table-responsive">
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
              <button class="btn btn-sm btn-outline-primary"
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

<script>
  /*
document.addEventListener('DOMContentLoaded', () => {
  // ----- EDIT MODAL -----
  const editModal = document.getElementById('editCollegesModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', (evt) => {
      const btn = evt.relatedTarget;
      if (!btn) return;

      // Read from data-* on the button that triggered the modal
      const id          = btn.getAttribute('data-id') || '';
      const shortName   = btn.getAttribute('data-short_name') || '';
      const collegeName = btn.getAttribute('data-college_name') || '';
      const deanIdNo    = btn.getAttribute('data-dean_id_no') || '';

      // Fill hidden/visible inputs in the modal
      const idInput          = editModal.querySelector('[name="id"]');
      const shortNameInput   = editModal.querySelector('[name="short_name"]');
      const collegeNameInput = editModal.querySelector('[name="college_name"]');
      const deanSelect       = editModal.querySelector('[name="dean_id_no"]');

      if (idInput)          idInput.value = id;
      if (shortNameInput)   shortNameInput.value = shortName;
      if (collegeNameInput) collegeNameInput.value = collegeName;

      if (deanSelect) {
        // Try to select the existing dean if present in options; blank clears
        deanSelect.value = deanIdNo || '';
        // If current dean isn't in the dropdown (edge case), add it so it's visible
        if (deanIdNo && ![...deanSelect.options].some(o => o.value === deanIdNo)) {
          const opt = document.createElement('option');
          opt.value = deanIdNo;
          opt.textContent = deanIdNo + ' — (not in list)';
          deanSelect.appendChild(opt);
          deanSelect.value = deanIdNo;
        }
      }
    });
  }

  // ----- DELETE MODAL -----
  const delModal = document.getElementById('deleteCollegesModal');
  if (delModal) {
    delModal.addEventListener('show.bs.modal', (evt) => {
      const btn = evt.relatedTarget;
      if (!btn) return;

      const id          = btn.getAttribute('data-id') || '';
      const shortName   = btn.getAttribute('data-short_name') || '';
      const collegeName = btn.getAttribute('data-college_name') || '';

      // Hidden input (for submission)
      const idInput = delModal.querySelector('input[name="id"]');
      if (idInput) idInput.value = id;

      // Visible fields in the confirmation UI
      const spanId    = delModal.querySelector('.js-del-id');
      const spanShort = delModal.querySelector('.js-del-short');
      const spanName  = delModal.querySelector('.js-del-name');

      if (spanId)    spanId.textContent = id;
      if (spanShort) spanShort.textContent = shortName;
      if (spanName)  spanName.textContent = collegeName;
    });
  }
});
*/
</script>
