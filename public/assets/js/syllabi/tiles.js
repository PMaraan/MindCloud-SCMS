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

function parsePrograms(raw) {
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw);
    if (Array.isArray(parsed)) {
      return parsed.map((item) => String(item || '').trim()).filter(Boolean);
    }
  } catch (_) {
    // fall through
  }
  return String(raw)
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);
}

function updateDetailsPane(card) {
  const info = document.getElementById('sy-info');
  const empty = document.getElementById('sy-info-empty');
  if (!info || !empty) return;

  const title = robustData(card, 'title') || 'Untitled syllabus';
  const college = robustData(card, 'collegeName');
  const programsRaw = robustData(card, 'programs');
  const programList = parsePrograms(programsRaw);
  const programDisplay = programList.length ? programList.join('\n') : '';
  const course = robustData(card, 'courseCode');
  const courseName = robustData(card, 'courseName');
  const courseDisplay = course && courseName ? `${course} — ${courseName}` : (course || courseName || '—');
  const updated = formatUpdated(robustData(card, 'updated'));
  const status = (robustData(card, 'status') || '').toLowerCase();
  console.log('updateDetailsPane', {
    card,
    dataset: {
      syllabusId: robustData(card, 'syllabusId'),
      title,
      status,
      program: programDisplay,
      college,
      updated,
      course: courseDisplay,
    }
  });
  //console.log('courseCode:', course, 'courseName:', courseName, 'courseDisplay:', courseDisplay);
  setText('sy-i-title', title);
  setText('sy-i-college', college || '—');
  setText('sy-i-program', programDisplay || '—');
  setText('sy-i-course', courseDisplay);
  setText('sy-i-updated', updated);
  setText('sy-i-status', capitalizeForDisplay(status));
  
  toggleRow('sy-i-college-label', 'sy-i-college', !!college);
  toggleRow('sy-i-program-label', 'sy-i-program', programList.length > 0);
  toggleRow('sy-i-course-label', 'sy-i-course', !!courseDisplay && courseDisplay !== '—');

  empty.classList.add('d-none');
  info.classList.remove('d-none');

  // Show/hide Edit button based on permissions
  const perms = window.SY_PERMS || {};

  const editBtn = document.getElementById('sy-edit');
  if (editBtn) {
    const canEdit = !!perms.canEdit;
    editBtn.classList.toggle('d-none', !canEdit);
    if (canEdit) editBtn.dataset.syllabusId = robustData(card, 'syllabusId') || '';
  }

  const archiveBtn = document.getElementById('sy-archive');
  if (archiveBtn) {
    const canArchive = !!perms.canArchive;
    archiveBtn.classList.toggle('d-none', !canArchive);
    if (canArchive) {
      archiveBtn.textContent = status === 'archived' ? 'Unarchive' : 'Archive';
    }
  }

  const deleteBtn = document.getElementById('sy-delete');
  if (deleteBtn) {
    const canDelete = !!perms.canDelete && status === 'archived';
    deleteBtn.classList.toggle('d-none', !canDelete);
  }

  const archiveTitle = document.getElementById('sy-archive-title');
  if (archiveTitle) archiveTitle.textContent = robustData(card, 'title') || '—';

  const deleteTitle = document.getElementById('sy-delete-title');
  if (deleteTitle) deleteTitle.textContent = robustData(card, 'title') || '—';
}

export function selectTile(card) {
  if (!card) return;
  document
    .querySelectorAll('.sy-tile.sy-card--active')
    .forEach((node) => node.classList.remove('sy-card--active'));
  card.classList.add('sy-card--active');
  setSelectedSyllabusId(robustData(card, 'syllabusId') || null);
  updateDetailsPane(card);
}

function initTileClicks() {
  document.body.addEventListener('click', (event) => {
    const tile = event.target.closest('.sy-tile');
    if (tile) selectTile(tile);
  });
}

function initArrowNavigation() {
  document.addEventListener('keydown', (event) => {
    if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;

    const tiles = Array.from(document.querySelectorAll('.sy-tile'));
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
    const tile = event.target.closest('.sy-tile');
    if (!tile) return;
    const id = robustData(tile, 'syllabusId');
    if (id) openSyllabusById(id, { newTab: true });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    const tile = document.activeElement?.closest?.('.sy-tile');
    if (!tile) return;
    const id = robustData(tile, 'syllabusId');
    if (id) openSyllabusById(id, { newTab: true });
  });
}


console.log('initTileInteractions running', document.querySelectorAll('.sy-tile').length);
export default function initTileInteractions() {
  console.log('initTileInteractions running', document.querySelectorAll('.sy-tile').length);
  initTileClicks();
  initArrowNavigation();
  initOpenHandlers();
}