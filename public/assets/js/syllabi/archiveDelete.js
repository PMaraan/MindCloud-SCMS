// filepath: c:\xampp\htdocs\MindCloud-SCMS\public\assets\js\syllabi\archiveDelete.js
import { getActiveTile } from './state.js';
import { selectTile } from './tiles.js';
import { getBase, getCurrentCollegeParam } from './utils.js';

function getCsrfToken(csrfSpan) {
  return csrfSpan?.dataset?.token || '';
}

function resolveCollege(tile) {
  return (
    tile?.dataset.collegeId ||
    tile?.dataset.ownerDepartmentId ||
    getCurrentCollegeParam() ||
    ''
  );
}

/**
 * initArchiveDelete()
 * Mirrors the syllabus-templates behaviour:
 *  • opens the archive/delete modals using Bootstrap’s data API
 *  • posts archive/unarchive via fetch + FormData
 *  • submits a hidden form for deletes so redirects stay scoped
 */
export default function initArchiveDelete() {
  const archiveBtn = document.getElementById('sy-archive');
  const archiveModal = document.getElementById('syArchiveModal');
  const archiveTitle = document.getElementById('sy-archive-title');
  const archiveBody = document.getElementById('sy-archive-body');
  const archiveConfirm = document.getElementById('sy-archive-confirm');
  const csrfSpan = document.getElementById('sy-csrf');
  const deleteBtn = document.getElementById('sy-delete');
  const deleteConfirm = document.getElementById('sy-delete-confirm');

  if (archiveBtn && archiveModal && archiveConfirm) {
    archiveBtn.addEventListener('click', (event) => {
      const tile = getActiveTile();
      if (!tile) {
        event.preventDefault();
        return;
      }

      const title =
        tile.dataset.title ||
        tile.querySelector('.sy-name')?.textContent?.trim() ||
        '—';
      const status = (tile.dataset.status || '').toLowerCase();
      const willUnarchive = status === 'archived';

      archiveTitle.textContent = title;
      archiveBody.textContent = willUnarchive
        ? 'This syllabus is currently archived. Do you want to unarchive it?'
        : 'Are you sure you want to archive this syllabus?';

      archiveConfirm.textContent = willUnarchive ? 'Yes, unarchive' : 'Yes, archive';
      archiveConfirm.disabled = false;

      archiveModal.dataset.syllabusId = tile.dataset.syllabusId || '';
      archiveModal.dataset.targetStatus = willUnarchive ? 'active' : 'archived';
      archiveModal.dataset.collegeParam = resolveCollege(tile);
    });

    archiveModal.addEventListener('hidden.bs.modal', () => {
      archiveModal.dataset.syllabusId = '';
      archiveModal.dataset.targetStatus = '';
      archiveModal.dataset.collegeParam = '';
      archiveConfirm.disabled = false;
    });

    archiveConfirm.addEventListener('click', async () => {
      const syllabusId = parseInt(String(archiveModal.dataset.syllabusId || ''), 10) || 0;
      const targetStatus = String(archiveModal.dataset.targetStatus || 'archived');
      if (!syllabusId) {
        alert('No syllabus selected.');
        return;
      }

      const collegeParam = archiveModal.dataset.collegeParam || '';
      archiveConfirm.disabled = true;
      archiveConfirm.textContent = targetStatus === 'archived' ? 'Archiving…' : 'Unarchiving…';

      try {
        const formData = new FormData();
        formData.append('syllabus_id', String(syllabusId));
        formData.append('target', targetStatus);
        formData.append('csrf_token', getCsrfToken(csrfSpan));

        let endpoint = `${getBase()}/dashboard?page=syllabi&action=archive`;
        if (collegeParam) {
          endpoint += `&college=${encodeURIComponent(collegeParam)}`;
        }

        const response = await fetch(endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          body: formData,
          headers: { Accept: 'application/json' },
        });

        const data = await response.json().catch(() => ({
          success: false,
          message: 'Invalid response',
        }));

        if (response.ok && data?.success) {
          const card = document.querySelector(
            `.sy-tile[data-syllabus-id="${CSS.escape(String(syllabusId))}"]`
          );
          if (card) {
            const nextStatus = data.status || targetStatus;
            card.dataset.status = nextStatus;

            if (nextStatus === 'archived') {
              card.classList.add('sy-card--archived');
            } else {
              card.classList.remove('sy-card--archived');
            }
            selectTile(card);
          }

          bootstrap.Modal.getInstance(archiveModal)?.hide();
          window.showFlashMessage?.(data.message || 'Syllabus updated.', 'success');
        } else {
          alert(data?.message || 'Archive request failed.');
        }
      } catch (error) {
        console.error(error);
        alert('Server error while archiving.');
      } finally {
        archiveConfirm.disabled = false;
        archiveConfirm.textContent = targetStatus === 'archived' ? 'Yes, archive' : 'Yes, unarchive';
      }
    });
  }

  if (deleteBtn && deleteConfirm && csrfSpan) {
    deleteConfirm.addEventListener('click', () => {
      const tile = getActiveTile();
      const syllabusId = tile?.dataset.syllabusId;
      if (!syllabusId) {
        alert('No syllabus selected to delete.');
        return;
      }

      const form = document.createElement('form');
      form.method = 'POST';
      form.style.display = 'none';

      const collegeParam = resolveCollege(tile);
      let action = `${getBase()}/dashboard?page=syllabi&action=delete`;
      if (collegeParam) {
        action += `&college=${encodeURIComponent(collegeParam)}`;
      }
      form.action = action;

      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'syllabus_id';
      idInput.value = String(syllabusId);
      form.appendChild(idInput);

      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = 'csrf_token';
      csrfInput.value = getCsrfToken(csrfSpan);
      form.appendChild(csrfInput);

      document.body.appendChild(form);
      form.submit();
    });
  }
}