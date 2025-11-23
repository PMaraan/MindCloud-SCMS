// /public/assets/js/syllabi/tiles.js
import { getBase, capitalizeForDisplay } from './utils.js';
import { setSelectedSyllabusId, getSelectedSyllabusId, getActiveTile } from './state.js';

/** 
 * robustData(el, key)
 * - key = 'SyllabusId' (camelCase) -> attribute 'data-syllabus-id'
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

function setText(id, value) {
  const node = document.getElementById(id);
  if (node) node.textContent = value;
}

function toggleRow(dtId, ddId, show) {
  const dt = document.getElementById(dtId);
  const dd = document.getElementById(ddId);
  [dt, dd].forEach((el) => {
    if (!el) return;
    el.classList.toggle('d-none', !show);
  });
}

function formatUpdated(value) {
  if (!value) return '—';
  try {
    const iso = value.replace(' ', 'T');
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString();
  } catch {
    return value;
  }
}

function updateDetailsPane(card) {
  const info = document.getElementById('sy-info');
  const empty = document.getElementById('sy-info-empty');
  if (!info || !empty) return;

  const title = robustData(card, 'title') || 'Untitled syllabus';
  const status = robustData(card, 'status') || 'unknown';
  const program = robustData(card, 'programName') || '';
  const college = robustData(card, 'collegeName') || '';
  const updated = formatUpdated(robustData(card, 'updated'));

  console.log('updateDetailsPane', {
    card,
    dataset: {
      syllabusId: robustData(card, 'syllabusId'),
      title,
      status,
      program,
      college,
      updated
    }
  });

  setText('sy-i-title', title);
  setText('sy-i-status', capitalizeForDisplay(status));
  setText('sy-i-program', program || '—');
  setText('sy-i-college', college || '—');
  setText('sy-i-updated', updated);

  toggleRow('sy-i-program-label', 'sy-i-program', !!program);
  toggleRow('sy-i-college-label', 'sy-i-college', !!college);

  empty.classList.add('d-none');
  info.classList.remove('d-none');
}

export function selectTile(card) {
  if (!card) return;
  document.querySelectorAll('.tb-tile.tb-card--active').forEach((node) => node.classList.remove('tb-card--active'));
  card.classList.add('tb-card--active');
  setSelectedSyllabusId(robustData(card, 'syllabusId') || null);
  updateDetailsPane(card);
}

function initTileClicks() {
  document.body.addEventListener('click', (event) => {
    const tile = event.target.closest('.tb-tile');
    if (tile) selectTile(tile);
  });
}

function initArrowNavigation() {
  document.addEventListener('keydown', (event) => {
    if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;

    const tiles = Array.from(document.querySelectorAll('.tb-tile'));
    if (!tiles.length) return;

    let index = tiles.findIndex((tile) => tile.classList.contains('tb-card--active'));
    if (index < 0) {
      const stored = getSelectedSyllabusId();
      index = tiles.findIndex((tile) => robustData(tile, 'syllabusId') === stored);
      if (index < 0) index = 0;
    }

    const columns = 4;
    switch (event.key) {
      case 'ArrowRight':
        index = Math.min(index + 1, tiles.length - 1);
        break;
      case 'ArrowLeft':
        index = Math.max(index - 1, 0);
        break;
      case 'ArrowDown':
        index = Math.min(index + columns, tiles.length - 1);
        break;
      case 'ArrowUp':
        index = Math.max(index - columns, 0);
        break;
      default:
        break;
    }

    const next = tiles[index];
    if (!next) return;
    next.focus();
    selectTile(next);
  });
}

function openSyllabusById(id, { newTab = true } = {}) {
  if (!id) return;
  const url = `${getBase()}/dashboard?page=rteditor&syllabusId=${encodeURIComponent(id)}`;
  if (newTab) {
    window.open(url, '_blank', 'noopener');
  } else {
    window.location.href = url;
  }
}

function initOpenHandlers() {
  const openBtn = document.getElementById('sy-open');
  if (openBtn) {
    openBtn.addEventListener('click', () => {
      const activeTile = getActiveTile();
      const id = robustData(activeTile, 'syllabusId');
      if (!id) {
        alert('Select a syllabus first.');
        return;
      }
      openSyllabusById(id);
    });
  }

  document.body.addEventListener('dblclick', (event) => {
    const tile = event.target.closest('.tb-tile');
    if (!tile) return;
    const id = robustData(tile, 'syllabusId');
    if (id) openSyllabusById(id, { newTab: true });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    const tile = document.activeElement?.closest?.('.tb-tile');
    if (!tile) return;
    const id = robustData(tile, 'syllabusId');
    if (id) openSyllabusById(id, { newTab: true });
  });
}

function ensureInitialSelection() {
  const tile =
    document.querySelector('.tb-tile.tb-card--active') ||
    document.querySelector('.tb-tile');

  if (tile) selectTile(tile);
}

console.log('initTileInteractions running', document.querySelectorAll('.tb-tile').length);
export default function initTileInteractions() {
  console.log('initTileInteractions running', document.querySelectorAll('.tb-tile').length);
  initTileClicks();
  initArrowNavigation();
  initOpenHandlers();
  ensureInitialSelection();
}