import { getActiveTile, getSelectedTileId } from './state.js';
import { selectTile } from './tiles.js';
import { getBase, getCurrentCollegeParam } from './utils.js';

function getCsrfToken() {
  return document.getElementById('sy-csrf')?.dataset.token || '';
}

function resolveCollege(tile) {
  return tile?.dataset.collegeId ||
         tile?.dataset.ownerDepartmentId ||
         getCurrentCollegeParam() ||
         '';
}

export default function initArchiveDelete() {
  const archiveBtn = document.getElementById('sy-archive');
  const archiveModal = document.getElementById('syArchiveModal');
  const archiveTitle = document.getElementById('sy-archive-title');
  const archiveBody = document.getElementById('sy-archive-body');
  const archiveConfirm = document.getElementById('sy-archive-confirm');
  const deleteBtn = document.getElementById('sy-delete');
  const deleteConfirm = document.getElementById('sy-delete-confirm');

  if (archiveBtn && archiveModal && archiveConfirm) {
    archiveBtn.addEventListener('click', () => {
      const tile = getActiveTile();
      if (!tile) return;

      const status = (tile.dataset.status || 'draft').toLowerCase();
      const willUnarchive = status === 'archived';
      archiveTitle.textContent = tile.dataset.title || '—';
      archiveBody.textContent = willUnarchive
        ? 'This syllabus is currently archived. Do you want to unarchive it?'
        : 'Are you sure you want to archive this syllabus?';
      archiveConfirm.textContent = willUnarchive ? 'Yes, unarchive' : 'Yes, archive';
      archiveConfirm.disabled = false;

      archiveModal.dataset.syllabusId = tile.dataset.syllabusId || '';
      archiveModal.dataset.targetStatus = willUnarchive ? 'active' : 'archived';

      new bootstrap.Modal(archiveModal).show();
    });

    archiveConfirm.addEventListener('click', async () => {
      const syllabusId = parseInt(archiveModal.dataset.syllabusId || '', 10) || 0;
      const targetStatus = String(archiveModal.dataset.targetStatus || 'archived');
      if (!syllabusId) {
        alert('No syllabus selected.');
        return;
      }

      const tile = document.querySelector(`.sy-tile[data-syllabus-id="${CSS.escape(String(syllabusId))}"]`);
      const collegeId = resolveCollege(tile);

      archiveConfirm.disabled = true;
      archiveConfirm.textContent = targetStatus === 'archived' ? 'Archiving…' : 'Unarchiving…';

      try {
        const formData = new FormData();
        formData.append('syllabus_id', String(syllabusId));
        formData.append('target', targetStatus);
        formData.append('csrf_token', getCsrfToken());

        let endpoint = `${getBase()}/dashboard?page=syllabi&action=archive`;
        if (collegeId) endpoint += `&college=${encodeURIComponent(collegeId)}`;

        const response = await fetch(endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          body: formData,
          headers: { Accept: 'application/json' }
        });

        const payload = await response.json().catch(() => ({ success: false, message: 'Invalid response' }));

        if (response.ok && payload?.success) {
          const card = tile || document.querySelector(`.sy-tile[data-syllabus-id="${CSS.escape(String(syllabusId))}"]`);
          if (card) {
            card.dataset.status = payload.status || targetStatus;
            if ((payload.status || targetStatus) === 'archived') {
              card.classList.add('sy-card--archived');
            } else {
              card.classList.remove('sy-card--archived');
            }
            selectTile(card);
          }

          bootstrap.Modal.getInstance(archiveModal)?.hide();
          window.showFlashMessage?.(payload.message || 'Syllabus updated.', 'success');
        } else {
          alert(payload?.message || 'Archive request failed.');
        }
      } catch (err) {
        console.error(err);
        alert('Server error while archiving.');
      } finally {
        archiveConfirm.disabled = false;
        archiveConfirm.textContent = targetStatus === 'archived' ? 'Yes, archive' : 'Yes, unarchive';
      }
    });
  }

  if (deleteBtn && deleteConfirm) {
    deleteConfirm.addEventListener('click', () => {
      const tile = getActiveTile();
      const syllabusId = tile?.dataset.syllabusId || getSelectedTileId();
      if (!syllabusId) {
        alert('No syllabus selected to delete.');
        return;
      }

      const form = document.createElement('form');
      form.method = 'POST';
      form.style.display = 'none';

      const collegeId = resolveCollege(tile);
      let action = `${getBase()}/dashboard?page=syllabi&action=delete`;
      if (collegeId) action += `&college=${encodeURIComponent(collegeId)}`;
      form.action = action;

      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'syllabus_id';
      idInput.value = syllabusId;
      form.appendChild(idInput);

      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = 'csrf_token';
      csrfInput.value = getCsrfToken();
      form.appendChild(csrfInput);

      document.body.appendChild(form);
      form.submit();
    });
  }
}