(function () {
  const $ = (sel, ctx = document) => ctx.querySelector(sel);

  // Elements
  const avatarImg   = $('#pf-avatar');
  const avatarIn    = $('#pf-avatar-input');
  const avatarBtn   = $('#pf-avatar-edit-btn');

  const saveBtn     = $('#pf-btn-save');
  const cancelBtn   = $('#pf-btn-cancel');

  const pass1       = $('#pf-new-pass');
  const pass2       = $('#pf-new-pass2');
  const submitPass  = $('#pf-btn-submit-pass');

  const form        = $('#pf-form');

  // Bootstrap helpers
  function makeToast(message, type) {
    const existing = document.querySelector('.profile-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0 profile-toast`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.style.position = 'fixed';
    toast.style.right = '20px';
    toast.style.bottom = '20px';
    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>`;
    document.body.appendChild(toast);
    return new bootstrap.Toast(toast, { delay: 2500 });
  }

  // Avatar edit
  avatarBtn?.addEventListener('click', () => avatarIn?.click());
  avatarIn?.addEventListener('change', () => {
    const file = avatarIn.files && avatarIn.files[0];
    if (!file) return;
    const url = URL.createObjectURL(file);
    avatarImg.src = url;
  });

  // Save changes (demo only)
  saveBtn?.addEventListener('click', () => {
    // Basic client-side validation example: ensure names aren't absurdly short for demo
    const fname = form?.querySelector('input[name="fname"]')?.value?.trim() || '';
    const lname = form?.querySelector('input[name="lname"]')?.value?.trim() || '';
    if (fname.length < 1 || lname.length < 1) {
      makeToast('Please provide at least first and last name.', 'danger').show();
      return;
    }
    makeToast('Changes saved (demo only).', 'success').show();
  });

  // Cancel changes (demo only)
  cancelBtn?.addEventListener('click', () => {
    form?.reset?.();
    makeToast('Changes discarded (demo).', 'secondary').show();
  });

  // Change password modal submit (demo only)
  submitPass?.addEventListener('click', () => {
    const p1 = pass1?.value || '';
    const p2 = pass2?.value || '';
    if (p1.length < 6) {
      makeToast('Password must be at least 12 characters.', 'danger').show();
      return;
    }
    if (p1 !== p2) {
      makeToast('Passwords do not match.', 'danger').show();
      return;
    }
    makeToast('Password updated (demo only).', 'success').show();
    // Close modal
    const modalEl = document.getElementById('pfChangePassModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.hide();
    // Clear fields
    pass1.value = '';
    pass2.value = '';
  });
})();
