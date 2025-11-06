(function () {
  function selectTile(card) {
    document.querySelectorAll('.tb-tile.tb-card--active')
      .forEach(el => el.classList.remove('tb-card--active'));
    card.classList.add('tb-card--active');

    const info  = document.getElementById('tb-info');
    const empty = document.getElementById('tb-info-empty');
    if (info && empty) {
      document.getElementById('tb-i-title').textContent   = card.dataset.title   || '';
      document.getElementById('tb-i-owner').textContent   = card.dataset.owner   || '';
      document.getElementById('tb-i-updated').textContent = card.dataset.updated || '';
      empty.classList.add('d-none');
      info.classList.remove('d-none');
    }

    window.__tb_selectedId = card.dataset.templateId || null;
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Folder rows are anchors now — no JS needed.

    // Template tile selection
    document.body.addEventListener('click', function (ev) {
      const card = ev.target.closest('.tb-tile');
      if (card) selectTile(card);
    });

    // Arrow key navigation across tiles
    document.addEventListener('keydown', function (ev) {
      if (!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(ev.key)) return;

      const tiles = Array.from(document.querySelectorAll('.tb-tile'));
      if (!tiles.length) return;

      let idx = tiles.findIndex(t => t.classList.contains('tb-card--active'));
      if (idx < 0) idx = 0;

      if (ev.key === 'ArrowRight' || ev.key === 'ArrowDown') {
        idx = Math.min(idx + 1, tiles.length - 1);
      }
      if (ev.key === 'ArrowLeft' || ev.key === 'ArrowUp') {
        idx = Math.max(idx - 1, 0);
      }

      tiles[idx].focus();
      tiles[idx].click();
    });

    const btnOpen = document.getElementById('tb-open');
    const btnDup  = document.getElementById('tb-duplicate');

    // --- EDIT MODAL (autofill from selected tile) ---
    const btnEdit = document.getElementById('tb-edit');
    const editModalEl = document.getElementById('tbEditModal');

    function __tb_getActiveTile() {
      // First try the currently active card
      let tile = document.querySelector('.tb-tile.tb-card--active');
      if (tile) return tile;

      // Fallback to last selected id
      const id = window.__tb_selectedId;
      if (id) tile = document.querySelector(`.tb-tile[data-template-id="${CSS.escape(String(id))}"]`);
      return tile || null;
    }

    function __tb_fillEditModalFrom(tile) {
      if (!tile) return;

      const g = (k, d='') => (tile.dataset[k] ?? d);

      // Map dataset -> form fields
      const idEl      = document.getElementById('tb-e-id');
      const titleEl   = document.getElementById('tb-e-title');
      const vEl       = document.getElementById('tb-e-version');
      const stEl      = document.getElementById('tb-e-status');
      const deptSel   = document.getElementById('tb-e-college'); // department/college select
      const progSel   = document.getElementById('tb-e-program');   // program select
      const courseEl  = document.getElementById('tb-e-course-id');

      if (idEl)      idEl.value    = g('templateId', '');
      if (titleEl)   titleEl.value = g('title', '');

      // scope radios
      const scope = (g('scope','system') || 'system').toLowerCase();
      const scopeMap = {
        system:  'tb-e-scope-system',
        college: 'tb-e-scope-college',
        program: 'tb-e-scope-program'
      };
      const scopeId = scopeMap[scope] || 'tb-e-scope-system';
      const r = document.getElementById(scopeId);
      if (r) r.checked = true;

      // selects and numeric fields
      const deptId    = g('ownerDepartmentId','');
      const programId = g('programId','');
      const courseId  = g('courseId','');

      if (deptSel)    deptSel.value   = String(deptId || '');
      if (progSel)    progSel.value   = String(programId || '');
      if (courseEl)   courseEl.value  = courseId || '';
      if (vEl)        vEl.value       = g('version','') || '';
      if (stEl)       stEl.value      = g('status','draft') || 'draft';

      // refresh scope-dependent visibility
      const evt = new Event('change');
      ['tb-e-scope-system','tb-e-scope-college','tb-e-scope-program']
        .map(id => document.getElementById(id))
        .filter(Boolean)
        .forEach(el => el.dispatchEvent(evt));
    }

    // When the modal is about to show, pull data from the currently selected tile
    if (editModalEl) {
      editModalEl.addEventListener('show.bs.modal', () => {
        const tile = __tb_getActiveTile();
        __tb_fillEditModalFrom(tile);
      });
    }

    // ---- Auto-filter (College -> Programs, Program -> Courses) ----
    async function fetchJSON(url) {
      const r = await fetch(url, { credentials: 'same-origin' });
      if (!r.ok) throw new Error('Network error');
      return r.json();
    }
    function getBase() {
      if (typeof window.BASE_PATH !== 'undefined' && window.BASE_PATH) return window.BASE_PATH;
      const path = window.location.pathname;
      const cut = path.indexOf('/dashboard');
      return cut > -1 ? path.slice(0, cut) : '';
    }
    function fillSelect(sel, items, placeholder) {
      if (!sel) return;
      sel.innerHTML = '';
      const opt0 = document.createElement('option');
      opt0.value = '';
      opt0.textContent = placeholder || '— Select —';
      sel.appendChild(opt0);
      (items || []).forEach(it => {
        const o = document.createElement('option');
        o.value = String(it.id);
        o.textContent = it.label || '';
        sel.appendChild(o);
      });
    }

    async function loadProgramsForDepartment(departmentId, preselectId) {
      const sel = document.getElementById('tb-e-program');
      if (!departmentId) {
        fillSelect(sel, [], '— Select program —');
        return;
      }
      const url = `${getBase()}/dashboard?page=syllabus-templates&action=apiPrograms&department_id=${encodeURIComponent(departmentId)}`;
      const data = await fetchJSON(url);
      fillSelect(sel, data, '— Select program —');
      if (preselectId) sel.value = String(preselectId);
      sel.dispatchEvent(new Event('change')); // trigger course loading
    }

    async function loadCoursesForProgram(programId, preselectId) {
      const sel = document.getElementById('tb-e-course');
      if (!sel) return;
      if (!programId) {
        fillSelect(sel, [], '— Select course —');
        return;
      }
      const url = `${getBase()}/dashboard?page=syllabus-templates&action=apiCourses&program_id=${encodeURIComponent(programId)}`;
      const data = await fetchJSON(url);
      fillSelect(sel, data, '— Select course —');
      if (preselectId) sel.value = String(preselectId);
    }

    // Wire change events inside the Edit modal
    if (editModalEl) {
      editModalEl.addEventListener('shown.bs.modal', async () => {
        const deptSel = document.getElementById('tb-e-college');
        const progSel = document.getElementById('tb-e-program');

        // Preselect current values from the tile (if any)
        const tile = __tb_getActiveTile();
        const currentDeptId   = tile?.dataset.ownerDepartmentId || '';
        const currentProgramId= tile?.dataset.programId || '';
        const currentCourseId = tile?.dataset.courseId || '';

        // If the modal open came with dept/program, fetch chained lists and preselect:
        if (currentDeptId) {
          await loadProgramsForDepartment(currentDeptId, currentProgramId || null);
          if (currentProgramId) {
            await loadCoursesForProgram(currentProgramId, currentCourseId || null);
          } else {
            fillSelect(document.getElementById('tb-e-course'), [], '— Select course —');
          }
        } else {
          // blank state
          fillSelect(progSel, [], '— Select program —');
          fillSelect(document.getElementById('tb-e-course'), [], '— Select course —');
        }

        // Live change handlers
        if (deptSel) {
          deptSel.onchange = async (e) => {
            const depId = e.target.value;
            await loadProgramsForDepartment(depId, null);
          };
        }
        if (progSel) {
          progSel.onchange = async (e) => {
            const pid = e.target.value;
            await loadCoursesForProgram(pid, null);
          };
        }
      });
    }

    // Optional: If user clicks Edit with nothing selected, we can nudge them.
    // (Modal still opens because Bootstrap handles it via data attributes.)
    if (btnEdit) {
      btnEdit.addEventListener('click', () => {
        if (!__tb_getActiveTile()) {
          // Keep it silent for now, or uncomment:
          // alert('Select a template first.');
        }
      });
    }
    // --- OPEN IN EDITOR (new tab) ---
    if (btnOpen) {
      btnOpen.addEventListener('click', () => {
        const tile = document.querySelector('.tb-tile.tb-card--active');
        const id = tile?.getAttribute('data-template-id') || window.__tb_selectedId;

        if (!id) {
          alert('Select a template first.');
          return;
        }

        // Derive a reliable base path:
        // 1) Prefer server-injected BASE_PATH
        // 2) Fallback: strip anything after "/dashboard" from current path
        let base = (typeof window.BASE_PATH !== 'undefined' && window.BASE_PATH) ? window.BASE_PATH : '';
        if (!base) {
          const path = window.location.pathname;
          const cut = path.indexOf('/dashboard');
          base = cut > -1 ? path.slice(0, cut) : '';
        }

        // Build URL. Keep it action-less so AppShell’s allowed-action guard won’t block it.
        // The RT Editor can read ?templateId=... on index().
        const url = `${base}/dashboard?page=rteditor&templateId=${encodeURIComponent(id)}`;

        window.open(url, '_blank', 'noopener');
      });
    }

    // Double-click a tile to open
    document.body.addEventListener('dblclick', function (ev) {
      const tile = ev.target.closest('.tb-tile');
      if (!tile) return;
      const id = tile.getAttribute('data-template-id');
      if (!id) return;

      const base = (typeof BASE_PATH !== 'undefined' ? BASE_PATH : '') || '';
      const url  = `${base}/dashboard?page=rteditor&action=openTemplate&id=${encodeURIComponent(id)}`;
      window.open(url, '_blank', 'noopener');
    });

    // Press Enter on a focused tile to open
    document.addEventListener('keydown', function (ev) {
      if (ev.key !== 'Enter') return;
      const focused = document.activeElement?.closest?.('.tb-tile');
      if (!focused) return;

      const id = focused.getAttribute('data-template-id');
      if (!id) return;
      const base = (typeof BASE_PATH !== 'undefined' ? BASE_PATH : '') || '';
      const url  = `${base}/dashboard?page=rteditor&action=openTemplate&id=${encodeURIComponent(id)}`;
      window.open(url, '_blank', 'noopener');
    });

    // --- DUPLICATE TEMPLATE (alert only) ---
    if (btnDup) btnDup.addEventListener('click', function () {
      if (!window.__tb_selectedId) return;
      alert('Duplicate template ID: ' + window.__tb_selectedId);
    });
  });

  console.log('[TB] module JS loaded');
})();
