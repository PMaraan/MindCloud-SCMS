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
      const collegeId = row.dataset.collegeId || '';

      const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };

      setVal('edit-id-no', idNo);
      setVal('edit-fname', fname);
      setVal('edit-mname', mname);
      setVal('edit-lname', lname);
      setVal('edit-email', email);

      const roleSel = document.getElementById('edit-role');
      const collegeSel = document.getElementById('edit-college');
      if (roleSel) roleSel.value = roleId;
      if (collegeSel) collegeSel.value = collegeId;
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
      const parts = [];
      if (row.dataset.fname) parts.push(row.dataset.fname);
      if (row.dataset.mname) parts.push(row.dataset.mname);
      if (row.dataset.lname) parts.push(row.dataset.lname);
      const fullName = parts.join(' ').trim() || 'this account';

      const idField     = document.getElementById('delete-id-no');
      const nameField   = document.getElementById('delete-username');
      const idNoDisplay = document.getElementById('delete-idno-display');

      if (idField)     idField.value = idNo;
      if (nameField)   nameField.textContent = fullName;
      if (idNoDisplay) idNoDisplay.textContent = idNo;
    });
  }
});
