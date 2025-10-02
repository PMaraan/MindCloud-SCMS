// /public/assets/js/accounts.js
document.addEventListener('DOMContentLoaded', function () {
  // ---- Edit modal autofill ----
  const editModal = document.getElementById('editUserModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget;
      if (!btn) return;
      const row = btn.closest('tr');
      if (!row) return;

      const idNo      = row.dataset.idNo || '';
      const fname     = row.dataset.fname || '';
      const mname     = row.dataset.mname || '';
      const lname     = row.dataset.lname || '';
      const email     = row.dataset.email || '';
      const roleId    = row.dataset.roleId || '';
      const departmentId = row.dataset.departmentId || '';

      const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };

      setVal('edit-id-no', idNo);
      setVal('edit-fname', fname);
      setVal('edit-mname', mname);
      setVal('edit-lname', lname);
      setVal('edit-email', email);

      const roleSel = document.getElementById('edit-role');
      const collegeSel = document.getElementById('edit-college');
      if (roleSel) roleSel.value = roleId;
      if (collegeSel) collegeSel.value = departmentId;
    });
  }

  // ---- Delete modal autofill (show name + ID No.) ----
  const deleteModal = document.getElementById('deleteUserModal');
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget;
      if (!btn) return;
      const row = btn.closest('tr');
      if (!row) return;

      const idNo   = row.dataset.idNo || '';
      // Compose full name with optional middle name
      const fullName = [
      row.dataset.fname,
      row.dataset.mname,
      row.dataset.lname,
      ]
      .filter(Boolean)       // removes null, undefined, empty string
      .join(' ')             // join with spaces
      .trim() || 'this account';

      const idField     = document.getElementById('delete-id-no');
      const nameField   = document.getElementById('delete-username');
      const idNoDisplay = document.getElementById('delete-idno-display');

      if (idField)     idField.value = idNo;
      if (nameField)   nameField.textContent = fullName;
      if (idNoDisplay) idNoDisplay.textContent = idNo;
    });
  }
});
