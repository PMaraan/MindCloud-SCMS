// public/assets/js/programs.js
document.addEventListener('DOMContentLoaded', function () {
  // Helper: absolute base (set window.BASE_PATH in PHP if you host under a subfolder)
  const BASE = typeof window.BASE_PATH === 'string' ? window.BASE_PATH : '';

  // Util: fetch chairs for a department and populate a <select>
  async function loadChairsForDepartment(departmentId, selectEl, preselectIdNo) {
    if (!selectEl) return;
    selectEl.innerHTML = '<option value="">— None —</option>';

    const dep = parseInt(departmentId, 10);
    if (!(dep > 0)) return;

    try {
      const url = `${BASE}/api/programs/chairs?department_id=${encodeURIComponent(dep)}`;
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error('Network error');
      const data = await res.json();
      const chairs = Array.isArray(data.chairs) ? data.chairs : [];
      console.log('API chairs response for', dep, chairs);
      console.debug('[programs] chairs for dept', dep, chairs);

      for (const ch of chairs) {
        const opt = document.createElement('option');
        opt.value = ch.id_no || '';
        opt.textContent = `${ch.id_no || ''} — ${ch.full_name || ''}`.trim();
        selectEl.appendChild(opt);
      }

      if (preselectIdNo) selectEl.value = preselectIdNo;
    } catch (e) {
      // leave the "— None —" option
    }
  }

  // ===================== EDIT MODAL =====================
  const editModal = document.getElementById('editProgramModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget; if (!btn) return;
      const row = btn.closest('tr'); if (!row) return;

      const idInput     = editModal.querySelector('#progEditId');
      const nameInput   = editModal.querySelector('#progEditName');
      const collegeSel  = editModal.querySelector('#progEditCollege');
      const chairSel    = editModal.querySelector('#progEditChair');

      const progId   = row.dataset.programId || '';
      const progName = row.dataset.programName || '';
      const deptId   = row.dataset.collegeId || '';
      const chairId  = row.dataset.chairId || '';

      if (idInput)    idInput.value    = progId;
      if (nameInput)  nameInput.value  = progName;
      if (collegeSel) collegeSel.value = deptId;

      // Populate chair list for current department and preselect
      loadChairsForDepartment(deptId, chairSel, chairId);

      // When department changes, reload chair list (bind once per modal open)
      if (collegeSel) {
        // Remove any previous listener to avoid duplicates across multiple opens
        if (collegeSel._onDeptChange) {
          collegeSel.removeEventListener('change', collegeSel._onDeptChange);
        }
        collegeSel._onDeptChange = () => loadChairsForDepartment(collegeSel.value, chairSel, '');
        collegeSel.addEventListener('change', collegeSel._onDeptChange);
      }
    });
  }

  // ===================== CREATE MODAL =====================
  const createModal = document.getElementById('createProgramModal');
  if (createModal) {
    createModal.addEventListener('show.bs.modal', function () {
      const deptSel  = createModal.querySelector('select[name="department_id"]');
      const chairSel = createModal.querySelector('select[name="chair_id_no"]');
      if (chairSel) chairSel.innerHTML = '<option value="">— None —</option>';

      if (deptSel) {
        // If already chosen (back nav), load immediately
        if (deptSel.value) loadChairsForDepartment(deptSel.value, chairSel, '');
        // Then watch for changes (bind once per open)
        if (deptSel) {
          // If already chosen (back nav), load immediately
          if (deptSel.value) loadChairsForDepartment(deptSel.value, chairSel, '');
          // Bind persistent change listener (and de-dup across opens)
          if (deptSel._onDeptChange) {
            deptSel.removeEventListener('change', deptSel._onDeptChange);
          }
          deptSel._onDeptChange = () => loadChairsForDepartment(deptSel.value, chairSel, '');
          deptSel.addEventListener('change', deptSel._onDeptChange);
        }
      }
    });
  }

  // ===================== DELETE MODAL =====================
  const delModal = document.getElementById('deleteProgramModal');
  if (delModal) {
    delModal.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget; if (!btn) return;
      const row = btn.closest('tr'); if (!row) return;

      const idInput   = delModal.querySelector('#progDelId');
      const nameSpan  = delModal.querySelector('#progDelName');

      // New: chair line elements
      const chairLine = delModal.querySelector('#progDelChairLine');
      const chairSpan = delModal.querySelector('#progDelChair');

      const progId   = row.dataset.programId || '';
      const progName = row.dataset.programName || '';
      const chair    = row.dataset.chairName || ''; // <- from <tr data-chair-name="...">

      if (idInput)  idInput.value = progId;
      if (nameSpan) nameSpan.textContent = progName;

      // Show/hide the chair line based on availability
      if (chair && chairSpan && chairLine) {
        chairSpan.textContent = chair;
        chairLine.hidden = false;
      } else if (chairLine) {
        chairLine.hidden = true;
        if (chairSpan) chairSpan.textContent = '';
      }
    });
  }
});
