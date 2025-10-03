(() => {
  'use strict';

  const val = (row, name) => row?.getAttribute(`data-${name}`) || '';

  // Helper to show/hide + enable/disable a group tied to a checkbox
  const bindToggleGroup = (checkbox, group, selectToToggle) => {
    if (!checkbox || !group) return;
    const apply = () => {
      const on = !!checkbox.checked;
      group.hidden = !on;
      checkbox.setAttribute('aria-expanded', on ? 'true' : 'false');
      if (selectToToggle) {
        selectToToggle.disabled = !on;
        if (!on) {
          // Optional: clear value when turning off so it won't submit
          selectToToggle.value = '';
        }
      }
    };
    checkbox.addEventListener('change', apply);
    apply(); // initial state
  };

  document.addEventListener('DOMContentLoaded', () => {
    // ----- EDIT MODAL -----
    const editModal = document.getElementById('editDepartmentsModal');
    if (editModal && !editModal.dataset.bound) {
      editModal.dataset.bound = '1';

      // Hook up the toggle (edit)
      const editIsCollege = editModal.querySelector('#edit-is-college');
      const editDeanGroup = editModal.querySelector('#edit-dean-group');
      const editDeanSelect = editModal.querySelector('select[name="dean_id_no"]');
      bindToggleGroup(editIsCollege, editDeanGroup, editDeanSelect);

      editModal.addEventListener('show.bs.modal', (evt) => {
        const btn = evt.relatedTarget; if (!btn) return;
        const row = btn.closest('tr'); if (!row) return;

        const id              = val(row, 'id');
        const shortName       = val(row, 'short_name');
        const departmentName  = val(row, 'department_name');
        const deanIdNo        = val(row, 'dean_id_no');
        const isCollege       = (val(row, 'is_college') || '0') === '1';

        editModal.querySelector('[name="id"]').value = id;
        editModal.querySelector('[name="short_name"]').value = shortName;
        editModal.querySelector('[name="department_name"]').value = departmentName;

        // Set checkbox then apply visibility
        if (editIsCollege) {
          editIsCollege.checked = isCollege;
          // Re-apply visibility/disabled state
          const event = new Event('change');
          editIsCollege.dispatchEvent(event);
        }

        // Set dean select if group is on
        if (editDeanSelect) {
          if (isCollege) {
            editDeanSelect.value = deanIdNo || '';
            if (deanIdNo && ![...editDeanSelect.options].some(o => o.value === deanIdNo)) {
              const opt = document.createElement('option');
              opt.value = deanIdNo;
              opt.textContent = deanIdNo + ' — (not in list)';
              editDeanSelect.appendChild(opt);
              editDeanSelect.value = deanIdNo;
            }
          } else {
            editDeanSelect.value = '';
          }
        }
      });
    }

    // ----- CREATE MODAL -----
    const createModal = document.getElementById('createDepartmentsModal');
    if (createModal && !createModal.dataset.bound) {
      createModal.dataset.bound = '1';

      const createIsCollege = createModal.querySelector('#create-is-college');
      const createDeanGroup = createModal.querySelector('#create-dean-group');
      const createDeanSelect = createModal.querySelector('select[name="dean_id_no"]');
      bindToggleGroup(createIsCollege, createDeanGroup, createDeanSelect);

      // Optional: reset form each time it's opened
      createModal.addEventListener('show.bs.modal', () => {
        // Default: not a college → hide dean field
        if (createIsCollege) {
          createIsCollege.checked = false;
          const event = new Event('change');
          createIsCollege.dispatchEvent(event);
        }
        // Clear fields (optional)
        const f = createModal.querySelector('form');
        if (f) {
          f.querySelector('[name="short_name"]')?.setAttribute('value', '');
          f.querySelector('[name="department_name"]')?.setAttribute('value', '');
          if (createDeanSelect) createDeanSelect.value = '';
        }
      });
    }

    // ----- DELETE MODAL (unchanged) -----
    const delModal = document.getElementById('deleteDepartmentsModal');
    if (delModal && !delModal.dataset.bound) {
      delModal.dataset.bound = '1';

      delModal.addEventListener('show.bs.modal', (evt) => {
        const btn = evt.relatedTarget; if (!btn) return;
        const row = btn.closest('tr'); if (!row) return;

        const id             = val(row, 'id');
        const shortName      = val(row, 'short_name');
        const departmentName = val(row, 'department_name');

        delModal.querySelector('input[name="id"]')?.setAttribute('value', id);
        const sid = delModal.querySelector('.js-del-id');    if (sid) sid.textContent = id;
        const ss  = delModal.querySelector('.js-del-short'); if (ss)  ss.textContent  = shortName;
        const sn  = delModal.querySelector('.js-del-name');  if (sn)  sn.textContent  = departmentName;
      });
    }
  });
})();
