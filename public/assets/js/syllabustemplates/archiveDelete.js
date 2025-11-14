import { getActiveTile } from './state.js';
import { getBase, getCurrentCollegeParam } from './utils.js';
import { selectTile } from './tiles.js';

export default function initArchiveDelete() {
  const archiveButton = document.getElementById('tb-archive');
  const archiveModal = document.getElementById('tbArchiveModal');
  const archiveTitle = document.getElementById('tb-archive-title');
  const archiveBody = document.getElementById('tb-archive-body');
  const archiveConfirm = document.getElementById('tb-archive-confirm');
  const csrfSpan = document.getElementById('tb-csrf');

  if (archiveButton && archiveModal && archiveConfirm) {
    archiveButton.addEventListener('click', () => {
      const tile = getActiveTile();
      if (!tile) return;

      const title = tile.dataset.title || tile.querySelector('.tb-tile-title')?.textContent?.trim() || '—';
      const status = (tile.dataset.status || '').toLowerCase();
      const willUnarchive = status === 'archived';

      archiveTitle.textContent = title;
      archiveBody.textContent = willUnarchive
        ? 'This template is currently archived. Do you want to unarchive it?'
        : 'Are you sure you want to archive this template?';

      archiveConfirm.textContent = willUnarchive ? 'Yes, unarchive' : 'Yes, archive';
      archiveConfirm.disabled = false;

      archiveModal.dataset.templateId = tile.dataset.templateId || '';
      archiveModal.dataset.targetStatus = willUnarchive ? 'active' : 'archived';

      const instance = new bootstrap.Modal(archiveModal);
      instance.show();
    });

    archiveConfirm.addEventListener('click', async () => {
      const templateId = parseInt(String(archiveModal.dataset.templateId || ''), 10) || 0;
      const targetStatus = String(archiveModal.dataset.targetStatus || 'archived');
      if (!templateId) {
        alert('No template selected.');
        return;
      }

      const tile = document.querySelector(`.tb-tile[data-template-id="${CSS.escape(String(templateId))}"]`);
      const collegeParam = tile?.dataset.ownerDepartmentId || getCurrentCollegeParam();

      archiveConfirm.disabled = true;
      archiveConfirm.textContent = targetStatus === 'archived' ? 'Archiving…' : 'Unarchiving…';

      try {
        const formData = new FormData();
        formData.append('template_id', String(templateId));
        formData.append('target', targetStatus);
        formData.append('csrf_token', csrfSpan ? csrfSpan.dataset.token || '' : '');

        let endpoint = `${getBase()}/dashboard?page=syllabus-templates&action=archive`;
        if (collegeParam) endpoint += `&college=${encodeURIComponent(collegeParam)}`;

        const response = await fetch(endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          body: formData,
          headers: { Accept: 'application/json' }
        });

        const data = await response.json().catch(() => ({ success: false, message: 'Invalid response' }));

        if (response.ok && data?.success) {
          const card = tile || document.querySelector(`.tb-tile[data-template-id="${CSS.escape(String(templateId))}"]`);
          if (card) {
            card.dataset.status = data.status || targetStatus;
            if ((data.status || targetStatus) === 'archived') card.classList.add('tb-card--archived');
            else card.classList.remove('tb-card--archived');
            selectTile(card);
          }

          const instance = bootstrap.Modal.getInstance(archiveModal);
          instance?.hide();

          window.showFlashMessage?.(data.message || 'Template updated.', 'success');
        } else {
          alert(data?.message || 'Archive failed.');
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

  const deleteButton = document.getElementById('tb-delete');
  const deleteConfirm = document.getElementById('tb-delete-confirm');

  if (deleteButton && deleteConfirm) {
    deleteConfirm.addEventListener('click', () => {
      const tile = getActiveTile();
      const id = tile?.dataset.templateId;
      if (!id) {
        alert('No template selected to delete.');
        return;
      }

      const form = document.createElement('form');
      form.method = 'POST';
      form.style.display = 'none';

      const params = new URLSearchParams(window.location.search);
      const collegeId = params.get('college');
      const searchCollege = getCurrentCollegeParam();
      const collegeParam = tile?.dataset.ownerDepartmentId || searchCollege;
      let action = `${getBase()}/dashboard?page=syllabus-templates&action=delete`;
      if (collegeParam) {
        action += `&college=${encodeURIComponent(collegeParam)}`;
      }
      form.action = action;

      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'template_id';
      idInput.value = String(id);
      form.appendChild(idInput);

      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = 'csrf_token';
      csrfInput.value = csrfSpan ? csrfSpan.dataset.token || '' : '';
      form.appendChild(csrfInput);

      document.body.appendChild(form);
      form.submit();
    });
  }
}