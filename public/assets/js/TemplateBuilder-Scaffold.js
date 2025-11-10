(function () {
  // Fallback for CSS.escape on older browsers
  if (!window.CSS || typeof window.CSS.escape !== 'function') {
    (function() {
      const r = /[{}|\\^~\[\]`"<>#%]/g;
      window.CSS = window.CSS || {};
      window.CSS.escape = function(v) {
        return String(v).replace(r, '\\$&');
      };
    })();
  }
  
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
      // Toggle Edit button based on scope + user rights
      const btnEdit = document.getElementById('tb-edit');
      if (btnEdit) {
        const scope = (card.dataset.scope || 'system').toLowerCase();
        const P = (window.TB_PERMS || {});
        let can = false;
        if (scope === 'system')  can = !!P.canEditSystem;
        if (scope === 'college') can = !!P.canEditCollege;
        if (scope === 'program') can = !!P.canEditProgram;
        // course scope maps to program-level edit permission (same granularity)
        if (scope === 'course')  can = !!P.canEditProgram;

        btnEdit.style.display = can ? '' : 'none';
      }
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
      const courseEl  = document.getElementById('tb-e-course');

      if (idEl)      idEl.value    = g('templateId', '');
      if (titleEl)   titleEl.value = g('title', '');

      // scope radios
      const scope = (g('scope','system') || 'system').toLowerCase();
      const scopeMap = { 
        system:'tb-e-scope-system', 
        college:'tb-e-scope-college', 
        program:'tb-e-scope-program', 
        course:'tb-e-scope-course' 
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
      ['tb-e-scope-system','tb-e-scope-college','tb-e-scope-program','tb-e-scope-course']
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
      const r = await fetch(url, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!r.ok) throw new Error('HTTP ' + r.status);

      const ct = (r.headers.get('content-type') || '').toLowerCase();
      if (!ct.includes('application/json')) {
        // drain HTML to avoid JSON parser error, then throw
        await r.text();
        throw new Error('Non-JSON response');
      }
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
      // Normalize to a number and guard
      const depNum = Number(departmentId);
      if (!sel || !Number.isFinite(depNum) || depNum <= 0) {
        fillSelect(sel, [], '— Select program —');
        // Also clear courses when college/department is cleared
        const courseSel = document.getElementById('tb-e-course');
        fillSelect(courseSel, [], '— Select course —');
        return;
      }

      const url = `${getBase()}/dashboard?page=syllabus-templates&ajax=programs&department_id=${encodeURIComponent(depNum)}`;
      const data = await fetchJSON(url);
      // endpoint returns { programs: [...] }
      const items = Array.isArray(data?.programs) ? data.programs : [];
      fillSelect(sel, items, '— Select program —');

      if (preselectId) {
        sel.value = String(preselectId);
        // Only trigger course loading if we actually have a program preselected
        sel.dispatchEvent(new Event('change'));
      } else {
        // No program chosen yet → do NOT hit the courses API; just clear it
        const courseSel = document.getElementById('tb-e-course');
        fillSelect(courseSel, [], '— Select course —');
      }
    }

    async function loadCoursesForProgram(programId, preselectId) {
      const sel = document.getElementById('tb-e-course');
      if (!sel) return;

      // Nothing selected yet? Do NOT call the API.
      if (!programId || programId === '0') {
        fillSelect(sel, [], '— Select course —');
        return;
      }
      const url = `${getBase()}/api/syllabus-templates/courses?program_id=${encodeURIComponent(programId)}`;
      const data = await fetchJSON(url);
      fillSelect(sel, data, '— Select course —');
      if (preselectId) sel.value = String(preselectId);
    }

    // === Generic variants for the Duplicate modal (target different selects) ===
    async function loadProgramsForDepartmentTo(departmentId, preselectId, programSelectId, courseSelectId) {
      const progSel = document.getElementById(programSelectId);
      const crsSel  = document.getElementById(courseSelectId);
      const depNum = Number(departmentId);

      if (!progSel || !Number.isFinite(depNum) || depNum <= 0) {
        fillSelect(progSel, [], '— Select program —');
        if (crsSel) fillSelect(crsSel, [], '— Select course —');
        return;
      }

      const url  = `${getBase()}/dashboard?page=syllabus-templates&ajax=programs&department_id=${encodeURIComponent(depNum)}`;
      const data = await fetchJSON(url);
      const items = Array.isArray(data?.programs) ? data.programs : [];
      fillSelect(progSel, items, '— Select program —');

      const wantPid = preselectId ? String(preselectId) : '';
      if (wantPid) {
        // Strongly attempt to preselect the program the source tile had
        await robustSelect(progSel, wantPid);
        // If we *did* select a program now, leave course to caller
      } else {
        if (crsSel) fillSelect(crsSel, [], '— Select course —');
      }
    }

    async function loadCoursesForProgramTo(programId, preselectId, courseSelectId) {
      const crsSel = document.getElementById(courseSelectId);
      if (!crsSel) return;

      const pid = String(programId || '');
      if (!pid || pid === '0') {
        fillSelect(crsSel, [], '— Select course —');
        return;
      }
      const url  = `${getBase()}/api/syllabus-templates/courses?program_id=${encodeURIComponent(pid)}`;
      const data = await fetchJSON(url);
      fillSelect(crsSel, data, '— Select course —');

      const wantCid = preselectId ? String(preselectId) : '';
      if (wantCid) {
        await robustSelect(crsSel, wantCid);
      }
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
            const depId = String(e.target.value || '');
            if (!depId || depId === '0') {
              fillSelect(document.getElementById('tb-e-program'), [], '— Select program —');
              fillSelect(document.getElementById('tb-e-course'),  [], '— Select course —');
              return;
            }
            await loadProgramsForDepartment(depId, null);
          };
        }
        if (progSel) {
          progSel.onchange = async (e) => {
            const pid = String(e.target.value || '');
            if (!pid || pid === '0') {
              fillSelect(document.getElementById('tb-e-course'), [], '— Select course —');
              return;
            }
            await loadCoursesForProgram(pid, null);
          };
        }
      });
    }

    // --- visibility + required + always-show-placeholder when newly visible
    function dupUpdateVisRequired() {
      const sys = document.getElementById('tb-d-scope-system');
      const col = document.getElementById('tb-d-scope-college');
      const prg = document.getElementById('tb-d-scope-program');
      const crs = document.getElementById('tb-d-scope-course');

      const wCol = document.getElementById('tb-d-college-wrap');
      const wPrg = document.getElementById('tb-d-program-wrap');
      const wCrs = document.getElementById('tb-d-course-wrap');

      const selCol = document.getElementById('tb-d-college');
      const selPrg = document.getElementById('tb-d-program');
      const selCrs = document.getElementById('tb-d-course');

      const isSys = !!(sys && sys.checked);
      const isCol = !!(col && col.checked);
      const isPrg = !!(prg && prg.checked);
      const isCrs = !!(crs && crs.checked);

      // Show/hide wraps
      if (wCol) wCol.classList.toggle('d-none', !(isCol || isPrg || isCrs));
      if (wPrg) wPrg.classList.toggle('d-none', !(isPrg || isCrs));
      if (wCrs) wCrs.classList.toggle('d-none', !isCrs);

      // Required flags
      if (selCol) selCol.required = (isCol || isPrg || isCrs);
      if (selPrg) selPrg.required = (isPrg || isCrs);
      if (selCrs) selCrs.required = isCrs;

      // IMPORTANT: Always ensure placeholders + selectedIndex=0 for any newly visible select
      // College (visible for college/program/course)
      if ((isCol || isPrg || isCrs) && selCol) {
        // If already selected (e.g., we preselected/locked dean’s college), don’t override it.
        if (!selCol.value) {
          if (selCol.options.length === 0 || selCol.options[0].value !== '') {
            const opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = '— Select college —';
            selCol.insertBefore(opt0, selCol.firstChild);
          }
          // Only select placeholder when truly empty
          selCol.selectedIndex = 0;
        }
      }

      // Program (visible for program/course)
      if ((isPrg || isCrs) && selPrg) {
        if (!selPrg.value) {
          // Always show placeholder immediately (even if an async load is about to run)
          if (selPrg.options.length === 0 || selPrg.options[0].value !== '') {
            // reset to clean placeholder state
            selPrg.innerHTML = '';
            const opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = '— Select program —';
            selPrg.appendChild(opt0);
          } else {
            selPrg.selectedIndex = 0;
          }
        }
      }

      // Course (visible for course)
      if (isCrs && selCrs) {
        if (!selCrs.value) {
          if (selCrs.options.length === 0 || selCrs.options[0].value !== '') {
            selCrs.innerHTML = '';
            const opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = '— Select course —';
            selCrs.appendChild(opt0);
          } else {
            selCrs.selectedIndex = 0;
          }
        }
      }
    }

    function dupEnsurePlaceholdersForScopeBeforeLoads() {
      const col = document.getElementById('tb-d-scope-college');
      const prg = document.getElementById('tb-d-scope-program');
      const crs = document.getElementById('tb-d-scope-course');

      const selCol = document.getElementById('tb-d-college');
      const selPrg = document.getElementById('tb-d-program');
      const selCrs = document.getElementById('tb-d-course');

      // If the user is narrowing scope, make sure placeholders are visible immediately
      if (col && col.checked && selCol && !selCol.value) {
        if (selCol.options.length > 0) selCol.selectedIndex = 0;
      }
      if (prg && prg.checked) {
        if (selPrg) {
          selPrg.innerHTML = '<option value="">— Select program —</option>';
        }
        if (selCrs) {
          selCrs.innerHTML = '<option value="">— Select course —</option>';
        }
      }
      if (crs && crs.checked) {
        if (selPrg && selPrg.options.length === 0) {
          selPrg.innerHTML = '<option value="">— Select program —</option>';
        }
        if (selCrs) {
          selCrs.innerHTML = '<option value="">— Select course —</option>';
        }
      }
    }

    // ----- DUPLICATE MODAL (prefill + cascades; frontend-only for now) -----
    const dupModalEl = document.getElementById('tbDuplicateModal');

    function __tb_fillDuplicateModalFrom(tile) {
      if (!tile) return;
      const g = (k, d='') => (tile.dataset[k] ?? d);

      // Hidden source id
      const srcEl = document.getElementById('tb-d-src-id');
      if (srcEl) srcEl.value = g('templateId', '');

      // Title suggestion
      const titleEl = document.getElementById('tb-d-title');
      if (titleEl && !titleEl.value) {
        const baseTitle = g('title','Untitled');
        titleEl.value = `Copy of ${baseTitle}`;
      }

      // Prefer same scope as source if radio exists and not disabled
      const scope = (g('scope','system') || 'system').toLowerCase();
      const scopeIds = { system:'tb-d-scope-system', college:'tb-d-scope-college', program:'tb-d-scope-program', course:'tb-d-scope-course' };
      const pref = document.getElementById(scopeIds[scope] || scopeIds.system);
      if (pref && !pref.disabled) pref.checked = true;

      // Preselect cascading values from tile (only as initial hints)
      const depId = g('ownerDepartmentId','');
      const pid   = g('programId','');
      const cid   = g('courseId','');

      const deptSel = document.getElementById('tb-d-college');
      const progSel = document.getElementById('tb-d-program');
      const crsSel  = document.getElementById('tb-d-course');

      if (deptSel) deptSel.value = String(depId || '');

      (async () => {
        if (depId) {
          await loadProgramsForDepartmentTo(depId, pid || null, 'tb-d-program', 'tb-d-course');
          if (pid) {
            await loadCoursesForProgramTo(pid, cid || null, 'tb-d-course');
          } else {
            fillSelect(crsSel, [], '— Select course —');
          }
        } else {
          fillSelect(progSel, [], '— Select program —');
          fillSelect(crsSel, [],  '— Select course —');
        }
      })();
    }

    // Helpers to read the currently selected scope in the Duplicate modal
    function dupGetScope() {
      const ids = ['tb-d-scope-system','tb-d-scope-college','tb-d-scope-program','tb-d-scope-course'];
      for (const id of ids) {
        const el = document.getElementById(id);
        if (el && el.checked) return el.value;
      }
      return ''; // none yet
    }

    function dupNeedsCollege(scope) {
      return scope === 'college' || scope === 'program' || scope === 'course';
    }

    if (dupModalEl) {
      // When the Duplicate modal is about to show…
      dupModalEl.addEventListener('show.bs.modal', async () => {
        const tile = __tb_getActiveTile();

        // 0) Ensure placeholders are visible immediately (prevents “blank” look)
        fillSelect(document.getElementById('tb-d-program'), [], '— Select program —');
        fillSelect(document.getElementById('tb-d-course'),  [], '— Select course —');

        // 1) Fill from selected tile (title, src id, and try to preload dept/program/course ids)
        __tb_fillDuplicateModalFrom(tile);

        // Capture whatever __tb_fillDuplicateModalFrom just set (so we can preserve it)
        const progSel0 = document.getElementById('tb-d-program');
        const crsSel0  = document.getElementById('tb-d-course');
        let wantPid = progSel0?.value || '';   // may be '' for global dup
        let wantCid = crsSel0?.value  || '';

        // 2) Defaults coming from PHP (role-aware)
        const defScope    = (dupModalEl.dataset.defaultScope || '').toLowerCase();   // e.g., "college" for dean/chair
        const defCollege  = Number(dupModalEl.dataset.defaultCollege || 0);          // their college_id
        const lockCollege = ['1','true','yes'].includes(String(dupModalEl.dataset.lockCollege || '').toLowerCase());

        const rSys = document.getElementById('tb-d-scope-system');
        const rCol = document.getElementById('tb-d-scope-college');
        const rPrg = document.getElementById('tb-d-scope-program');
        const rCrs = document.getElementById('tb-d-scope-course');

        const deptSel = document.getElementById('tb-d-college');

        // 2a) If no radio is checked yet, pick a sane default:
        const anyChecked = !!dupGetScope();
        if (!anyChecked) {
          if (defScope === 'college' && rCol && !rCol.disabled) {
            rCol.checked = true;
          } else if (rCol && !rCol.disabled) {
            rCol.checked = true;
          } else if (rPrg && !rPrg.disabled) {
            rPrg.checked = true;
          } else if (rCrs && !rCrs.disabled) {
            rCrs.checked = true;
          } else if (rSys && !rSys.disabled) {
            rSys.checked = true;
          }
        }

        // 2b) If dean/chair and scope needs a college, force-select + lock BEFORE visibility/loads
        let scopeNow = dupGetScope(); // <-- recompute AFTER defaults are set
        if (deptSel && dupNeedsCollege(scopeNow)) {
          if ((lockCollege || !deptSel.value) && defCollege > 0) {
            await robustSelect(deptSel, defCollege, { injectIfMissing: true, labelIfInjected: '(Your College)' });
          }
          if (lockCollege) {
            deptSel.disabled = true;
            deptSel.setAttribute('aria-disabled','true');
            deptSel.setAttribute('data-locked','1');
          } else {
            deptSel.disabled = false;
            deptSel.removeAttribute('aria-disabled');
            deptSel.removeAttribute('data-locked');
          }
        }

        // 2c) Show correct rows + required flags
        dupUpdateVisRequired();

        // 2d) If we need program/course lists and have a college, populate them.
        //     Preserve any preselected program/course we captured earlier.
        if (dupNeedsCollege(scopeNow) && deptSel && deptSel.value) {
          await loadProgramsForDepartmentTo(deptSel.value, wantPid || null, 'tb-d-program', 'tb-d-course');
          const progSel = document.getElementById('tb-d-program');
          if (wantPid && progSel) progSel.value = String(wantPid);

          if (scopeNow === 'course' && wantPid) {
            await loadCoursesForProgramTo(wantPid, wantCid || null, 'tb-d-course');
            const crsSel = document.getElementById('tb-d-course');
            if (wantCid && crsSel) crsSel.value = String(wantCid);
          }
        }
      });

      // After the modal is fully shown, wire live interactions
      dupModalEl.addEventListener('shown.bs.modal', () => {
        const sys = document.getElementById('tb-d-scope-system');
        const col = document.getElementById('tb-d-scope-college');
        const prg = document.getElementById('tb-d-scope-program');
        const crs = document.getElementById('tb-d-scope-course');

        const deptSel = document.getElementById('tb-d-college');
        const progSel = document.getElementById('tb-d-program');

        const defCollege  = Number(dupModalEl.dataset.defaultCollege || 0);
        const lockCollege = ['1','true','yes'].includes(String(dupModalEl.dataset.lockCollege || '').toLowerCase());

        // Keep visibility/required flags correct as scope changes…
        [sys, col, prg, crs].forEach(el => el && el.addEventListener('change', async () => {
          // Always show placeholders first
          fillSelect(document.getElementById('tb-d-program'), [], '— Select program —');
          fillSelect(document.getElementById('tb-d-course'),  [], '— Select course —');

          const scopeNow = dupGetScope();

          // If narrowing to a scope that needs college, auto-select + lock dean/chair’s college immediately
          if (deptSel && dupNeedsCollege(scopeNow)) {
            if ((lockCollege || !deptSel.value) && defCollege > 0) {
              await robustSelect(deptSel, defCollege, { injectIfMissing: true, labelIfInjected: '(Your College)' });
            }
            if (lockCollege) {
              deptSel.disabled = true;
              deptSel.setAttribute('aria-disabled','true');
              deptSel.setAttribute('data-locked','1');
            } else {
              deptSel.disabled = false;
              deptSel.removeAttribute('aria-disabled');
              deptSel.removeAttribute('data-locked');
            }
          }

          // Now update visibility/required (after any forced selection)
          dupUpdateVisRequired();

          // Populate dependent selects (no preselect here on scope flip)
          if (dupNeedsCollege(scopeNow) && deptSel && deptSel.value) {
            await loadProgramsForDepartmentTo(deptSel.value, null, 'tb-d-program', 'tb-d-course');
          }
          if (scopeNow === 'course' && progSel && progSel.value) {
            await loadCoursesForProgramTo(progSel.value, null, 'tb-d-course');
          }
        }));

        // Cascading selects
        if (deptSel) {
          deptSel.onchange = async (e) => {
            const depId = String(e.target.value || '');
            fillSelect(document.getElementById('tb-d-program'), [], '— Select program —');
            fillSelect(document.getElementById('tb-d-course'),  [], '— Select course —');
            if (!depId || depId === '0') return;
            await loadProgramsForDepartmentTo(depId, null, 'tb-d-program', 'tb-d-course');
          };
        }

        if (progSel) {
          progSel.onchange = async (e) => {
            const pid = String(e.target.value || '');
            fillSelect(document.getElementById('tb-d-course'), [], '— Select course —');
            if (!pid || pid === '0') return;
            await loadCoursesForProgramTo(pid, null, 'tb-d-course');
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

  });

  console.log('[TB] module JS loaded');

  // Robustly select a value in a <select>. If the option isn't there yet,
  // retry a few times. If still missing and we *know* the value is correct,
  // we can optionally inject a fallback option.
  async function robustSelect(sel, value, { injectIfMissing = false, labelIfInjected = '(Selected)' } = {}) {
    if (!sel) return false;
    const v = String(value ?? '');
    if (!v) return false;

    const tryPick = () => {
      const opt = sel.querySelector(`option[value="${CSS.escape(v)}"]`);
      if (opt) {
        // Make it *explicitly* selected; some UIs keep first option selected otherwise.
        sel.value = v;
        Array.from(sel.options).forEach(o => { o.selected = (o.value === v); });
        return true;
      }
      return false;
    };

    if (tryPick()) return true;

    // microtask
    await Promise.resolve();
    if (tryPick()) return true;

    // next frame
    await new Promise(r => requestAnimationFrame(r));
    if (tryPick()) return true;

    if (injectIfMissing) {
      const opt = document.createElement('option');
      opt.value = v;
      opt.textContent = labelIfInjected;
      sel.appendChild(opt);
      sel.value = v;
      Array.from(sel.options).forEach(o => { o.selected = (o.value === v); });
      return true;
    }
    return false;
  }

})();
