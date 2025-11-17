// /public/assets/js/syllabustemplates/tiles.js
import { capitalizeForDisplay, getBase } from './utils.js';
import { setSelectedTileId, getSelectedTileId, getActiveTile } from './state.js';

/** 
 * robustData(el, key)
 * - key = 'templateId' (camelCase) -> attribute 'data-template-id'
 * - returns string or ''
 */
function robustData(el, key) {
  if (!el) return '';
  // prefer dataset when present; check undefined explicitly (safer than 'in')
  try {
    if (el.dataset && typeof el.dataset[key] !== 'undefined') {
      const v = el.dataset[key];
      return (v === null || typeof v === 'undefined') ? '' : String(v).trim();
    }
  } catch (e) {
    // ignore and fall back to getAttribute
  }

  // convert camelCase like 'templateId' -> 'template-id'
  const kebab = key.replace(/([A-Z])/g, '-$1').toLowerCase();
  const attr = el.getAttribute('data-' + kebab);
  return (attr === null || typeof attr === 'undefined') ? '' : String(attr).trim();
}

/**
 * updateDetailsPane(card)
 * - Populates the sidebar detail panel with the active tile’s metadata.
 * - Called by selectTile() whenever the highlighted card changes.
 * - Reads dataset attributes (scope, status, timestamps, etc.) and toggles UI controls (edit/archive/delete buttons).
 */
function updateDetailsPane(card) {
  const info = document.getElementById('tb-info');
  const empty = document.getElementById('tb-info-empty');
  if (!info || !empty) return;

  document.getElementById('tb-i-title').textContent = robustData(card, 'title') || '';

  const scopeRaw = robustData(card, 'scope') || '';
  const scopeNorm = scopeRaw === 'system' ? 'global' : scopeRaw;
  const scopeLower = scopeNorm.toLowerCase();
  const scopeLabel = scopeNorm ? `${scopeNorm.charAt(0).toUpperCase()}${scopeNorm.slice(1)}` : '';
  const scopeEl = document.getElementById('tb-i-scope');
  if (scopeEl) scopeEl.textContent = scopeLabel;

  const status = (robustData(card, 'status') || '').toString().toLowerCase();
  const statusEl = document.getElementById('tb-i-status');
  if (statusEl) statusEl.textContent = capitalizeForDisplay(status);

  const updatedRaw = robustData(card, 'updated') || '';
  let updatedDisplay = updatedRaw;
  if (updatedRaw) {
    try {
      const iso = updatedRaw.replace(' ', 'T');
      const date = new Date(iso);
      if (!Number.isNaN(date.getTime())) {
        const y = date.getFullYear();
        const mo = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        let hh = date.getHours();
        const mm = String(date.getMinutes()).padStart(2, '0');
        const ampm = hh >= 12 ? 'PM' : 'AM';
        hh = hh % 12;
        if (hh === 0) hh = 12;
        updatedDisplay = `${y}-${mo}-${day} ${hh}:${mm} ${ampm}`;
      }
    } catch {
      updatedDisplay = updatedRaw;
    }
  }
  const updatedEl = document.getElementById('tb-i-updated');
  if (updatedEl) updatedEl.textContent = updatedDisplay;

  const collegeName = (robustData(card, 'collegeName') || '').trim();
  const programName = (robustData(card, 'programName') || '').trim();
  const courseName = (robustData(card, 'courseName') || '').trim();
  const needsCollege = ['college', 'program', 'course'].includes(scopeLower);

  const collegeDt = document.getElementById('tb-i-college-dt');
  const collegeDd = document.getElementById('tb-i-college');
  if (needsCollege && collegeName) {
    collegeDt?.classList.remove('d-none');
    if (collegeDd) {
      collegeDd.textContent = collegeName;
      collegeDd.classList.remove('d-none');
    }
  } else {
    collegeDt?.classList.add('d-none');
    if (collegeDd) {
      collegeDd.textContent = '';
      collegeDd.classList.add('d-none');
    }
  }

  const programDt = document.getElementById('tb-i-program-dt');
  const programDd = document.getElementById('tb-i-program');
  if (['program', 'course'].includes(scopeLower) && programName) {
    programDt?.classList.remove('d-none');
    if (programDd) {
      programDd.textContent = programName;
      programDd.classList.remove('d-none');
    }
  } else {
    programDt?.classList.add('d-none');
    if (programDd) {
      programDd.textContent = '';
      programDd.classList.add('d-none');
    }
  }

  const courseDt = document.getElementById('tb-i-course-dt');
  const courseDd = document.getElementById('tb-i-course');
  if (scopeLower === 'course' && courseName) {
    courseDt?.classList.remove('d-none');
    if (courseDd) {
      courseDd.textContent = courseName;
      courseDd.classList.remove('d-none');
    }
  } else {
    courseDt?.classList.add('d-none');
    if (courseDd) {
      courseDd.textContent = '';
      courseDd.classList.add('d-none');
    }
  }

  empty.classList.add('d-none');
  info.classList.remove('d-none');

  const perms = window.TB_PERMS || {};
  const canEdit =
    (scopeLower === 'global' && (perms.canEditGlobal || perms.canEditSystem)) ||
    (scopeLower === 'college' && perms.canEditCollege) ||
    ((scopeLower === 'program' || scopeLower === 'course') && perms.canEditProgram);

  const btnEdit = document.getElementById('tb-edit');
  if (btnEdit) btnEdit.style.display = canEdit ? '' : 'none';

  const btnArchive = document.getElementById('tb-archive');
  if (btnArchive) {
    const canArchive =
      (scopeLower === 'global' && (perms.canEditGlobal || perms.canEditSystem)) ||
      (scopeLower === 'college' && perms.canEditCollege) ||
      ((scopeLower === 'program' || scopeLower === 'course') && perms.canEditProgram);

    if (!canArchive) {
      btnArchive.style.display = 'none';
    } else {
      btnArchive.style.display = '';
      btnArchive.textContent = status === 'archived' ? 'Unarchive' : 'Archive';
    }
  }

  const btnDelete = document.getElementById('tb-delete');
  if (btnDelete) {
    const canDelete =
      status === 'archived' &&
      ((scopeLower === 'global' && perms.canEditGlobal) ||
        (scopeLower === 'college' && perms.canEditCollege) ||
        ((scopeLower === 'program' || scopeLower === 'course') && perms.canEditProgram));
    btnDelete.classList.toggle('d-none', !canDelete);
  }

  const deleteTitleEl = document.getElementById('tb-delete-title');
  if (deleteTitleEl) deleteTitleEl.textContent = robustData(card, 'title') || '—';
}

/**
 * selectTile(card)
 * - Central tile-selection handler used by click/keyboard interactions.
 * - Saves the selected template ID in shared state and refreshes the sidebar panel.
 */
export function selectTile(card) {
  if (!card) return;
  document.querySelectorAll('.tb-tile.tb-card--active').forEach((el) => el.classList.remove('tb-card--active'));
  card.classList.add('tb-card--active');
  setSelectedTileId(robustData(card, 'templateId') || null);
  updateDetailsPane(card);
}

/**
 * initTileClicks()
 * - Delegated click listener that activates tiles on single-click.
 * - Bound once during initTileInteractions().
 */
function initTileClicks() {
  document.body.addEventListener('click', (event) => {
    const card = event.target.closest('.tb-tile');
    if (card) selectTile(card);
  });
}

/**
 * initArrowNavigation()
 * - Enables arrow-key navigation across tiles.
 * - Keeps focus and selection in sync with ListView accessibility.
 */
function initArrowNavigation() {
  document.addEventListener('keydown', (event) => {
    if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;

    const tiles = Array.from(document.querySelectorAll('.tb-tile'));
    if (!tiles.length) return;

    let index = tiles.findIndex((tile) => tile.classList.contains('tb-card--active'));
    if (index < 0) index = Math.max(tiles.findIndex((tile) => robustData(tile, 'templateId') === getSelectedTileId()), 0);

    if (event.key === 'ArrowRight' || event.key === 'ArrowDown') index = Math.min(index + 1, tiles.length - 1);
    if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') index = Math.max(index - 1, 0);

    tiles[index].focus();
    tiles[index].click();
  });
}

/**
 * initOpenHandlers()
 * - Wires double-click, Enter key, and “Open” button to launch the template builder.
 * - Uses getBase() to compose the target URL.
 */
function initOpenHandlers() {
  const openBtn = document.getElementById('tb-open');
  if (openBtn) {
    openBtn.addEventListener('click', () => {
      const tile = getActiveTile();
      const id = robustData(tile, 'templateId');
      if (!id) {
        alert('Select a template first.');
        return;
      }
      const url = `${getBase()}/dashboard?page=rteditor&templateId=${encodeURIComponent(id)}`;
      window.open(url, '_blank', 'noopener');
    });
  }

  document.body.addEventListener('dblclick', (event) => {
    const tile = event.target.closest('.tb-tile');
    if (!tile) return;
    const id = robustData(tile, 'templateId');
    if (!id) return;
    const url = `${getBase()}/dashboard?page=rteditor&action=openTemplate&id=${encodeURIComponent(id)}`;
    window.open(url, '_blank', 'noopener');
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    const tile = document.activeElement?.closest?.('.tb-tile');
    if (!tile) return;
    const id = robustData(tile, 'templateId');
    if (!id) return;
    const url = `${getBase()}/dashboard?page=rteditor&action=openTemplate&id=${encodeURIComponent(id)}`;
    window.open(url, '_blank', 'noopener');
  });
}

/**
 * initTileInteractions()
 * - Entry point invoked from TemplateBuilder-Scaffold.js on DOMContentLoaded.
 * - Registers click, keyboard, and open handlers for the tiles grid.
 */
export default function initTileInteractions() {
  initTileClicks();
  initArrowNavigation();
  initOpenHandlers();
}