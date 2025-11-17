// /public/assets/js/syllabustemplates/utils.js
/**
 * installCssEscapeFallback()
 * - Ensures CSS.escape exists on older browsers.
 * - Called once from TemplateBuilder-Scaffold.js during bootstrap.
 * - No inbound arguments and no return value; mutates window.CSS.escape.
 */
export function installCssEscapeFallback() {
  if (!window.CSS || typeof window.CSS.escape !== 'function') {
    const pattern = /[{}|\\^~\[\]`"<>#%]/g;
    window.CSS = window.CSS || {};
    window.CSS.escape = (value) => String(value).replace(pattern, '\\$&');
  }
}

/**
 * showFlashMessage(message, level)
 * - Renders a Bootstrap toast to inform the user about CRUD outcomes.
 * - Invoked by archiveDelete.js and any other module via window.showFlashMessage.
 * - message: string to display, level: 'info'|'success'|'danger'.
 * - Creates DOM nodes and returns void.
 */
export function showFlashMessage(message = '', level = 'info') {
  let container = document.getElementById('tb-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'tb-toast-container';
    container.style.position = 'fixed';
    container.style.top = '1rem';
    container.style.right = '1rem';
    container.style.zIndex = 10800;
    document.body.appendChild(container);
  }

  const id = `tb-toast-${Date.now()}`;
  const toast = document.createElement('div');
  toast.className = `toast align-items-center text-bg-${level === 'danger' ? 'danger' : level === 'success' ? 'success' : 'secondary'} border-0`;
  toast.id = id;
  toast.setAttribute('role', 'alert');
  toast.setAttribute('aria-live', 'assertive');
  toast.setAttribute('aria-atomic', 'true');
  toast.style.minWidth = '220px';
  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${String(message)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `;
  container.appendChild(toast);
  const instance = new bootstrap.Toast(toast, { delay: 5000 });
  toast.addEventListener('hidden.bs.toast', () => toast.remove());
  instance.show();
}

/**
 * capitalizeForDisplay(value)
 * - Normalizes labels (e.g., status text) for UI display.
 * - Used by tiles.js when populating the details pane.
 * - Expects any value; returns a capitalized string.
 */
export function capitalizeForDisplay(value) {
  if (!value) return '';
  const str = String(value);
  return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * getBase()
 * - Resolves the BASE_PATH used for fetch/request URLs.
 * - Called by multiple modules (dataLoaders.js, archiveDelete.js).
 * - Looks at window.BASE_PATH or trims the current location; returns a string.
 */
export function getBase() {
  if (typeof window.BASE_PATH === 'string' && window.BASE_PATH) return window.BASE_PATH;
  const path = window.location.pathname;
  const cut = path.indexOf('/dashboard');
  return cut > -1 ? path.slice(0, cut) : '';
}

/**
 * fetchJSON(url)
 * - Thin wrapper around fetch() that enforces JSON responses.
 * - Consumed by dataLoaders.js (which powers modal cascading selects) and archive/delete flows.
 * - More tolerant: will attempt to parse JSON even if Content-Type header is missing/mis-set.
 * - url: absolute or base-relative endpoint; returns parsed JSON or throws.
 */
export async function fetchJSON(url) {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: { 'Accept': 'application/json' },
    cache: 'no-store'
  });

  if (!response.ok) throw new Error(`HTTP ${response.status}`);

  // Prefer correct header but be tolerant: try response.json() anyway and fallback to text->JSON.parse
  const contentType = (response.headers.get('content-type') || '').toLowerCase();

  try {
    // First attempt: native json parser (works even if content-type is wrong but body is valid JSON)
    const data = await response.json();
    return data;
  } catch (firstErr) {
    // If response.json() failed, try to read text and parse explicitly (gives better error messages)
    try {
      const txt = await response.text();
      // If body is empty, return empty array/object to avoid crashes
      if (!txt || !txt.trim()) throw firstErr;
      return JSON.parse(txt);
    } catch (secondErr) {
      // For debugging, include header info in the thrown error
      const headerInfo = contentType ? ` (content-type: ${contentType})` : ' (no content-type header)';
      throw new Error(`Invalid JSON response${headerInfo} — ${secondErr.message}`);
    }
  }
}

/**
 * fillSelect(select, items, placeholder)
 * - Populates a <select> with id/label pairs.
 * - Used by createModal.js, editModal.js, duplicateModal.js when injecting fetched data.
 * - select: DOM node, items: [{id,label}], placeholder: optional string.
 */
export function fillSelect(select, items, placeholder = '— Select —') {
  if (!select) return;
  select.innerHTML = '';
  const opt0 = document.createElement('option');
  opt0.value = '';
  opt0.textContent = placeholder;
  select.appendChild(opt0);

  (items || []).forEach((item) => {
    const option = document.createElement('option');
    option.value = String(item.id ?? '');
    option.textContent = item.label ?? '';
    select.appendChild(option);
  });
}

/**
 * robustSelect(select, value, options)
 * - Attempts to select a value, retrying across microtasks/frames for late-populated lists.
 * - Called by duplicateModal.js when pre-selecting college/program.
 * - select: DOM node, value: string|number, options: {injectIfMissing,labelIfInjected}.
 * - Returns true if value gets selected.
 */
export async function robustSelect(select, value, { injectIfMissing = false, labelIfInjected = '(Selected)' } = {}) {
  if (!select) return false;
  const target = String(value ?? '');
  if (!target) return false;

  const tryPick = () => {
    const option = select.querySelector(`option[value="${CSS.escape(target)}"]`);
    if (option) {
      select.value = target;
      Array.from(select.options).forEach((o) => { o.selected = o.value === target; });
      return true;
    }
    return false;
  };

  if (tryPick()) return true;
  await Promise.resolve();
  if (tryPick()) return true;
  await new Promise((resolve) => requestAnimationFrame(resolve));
  if (tryPick()) return true;

  if (injectIfMissing) {
    const opt = document.createElement('option');
    opt.value = target;
    opt.textContent = labelIfInjected;
    select.appendChild(opt);
    select.value = target;
    Array.from(select.options).forEach((o) => { o.selected = o.value === target; });
    return true;
  }

  return false;
}

/**
 * ensureOptionAndSelect(select, value)
 * - Convenience wrapper around robustSelect() that always injects if missing.
 * - Used by duplicateModal.js when enforcing college locks.
 */
export function ensureOptionAndSelect(select, value) {
  return robustSelect(select, value, { injectIfMissing: true, labelIfInjected: '(Your College)' });
}

/**
 * getCurrentCollegeParam()
 * - Reads the current ?college= query from the dashboard URL.
 * - Shared by create/edit/duplicate modals and archive/delete to keep redirection scoped.
 */
export function getCurrentCollegeParam() {
  const params = new URLSearchParams(window.location.search);
  return params.get('college') || '';
}

/**
 * lockSelectElement(select, locked, value, injectLabel)
 * - Applies a read-only UX to <select> when the role restricts scope changes.
 * - Used by all modals (create/edit/duplicate) during initialization.
 * - select: DOM node, locked: boolean flag, value: target option, injectLabel: text for inserted options.
 */
export function lockSelectElement(select, locked, value, injectLabel = '(Your College)') {
  if (!select) return;

  if (locked) {
    const targetValue = value !== undefined && value !== null ? String(value) : '';
    if (targetValue) {
      let option = select.querySelector(`option[value="${CSS.escape(targetValue)}"]`);
      if (!option) {
        option = document.createElement('option');
        option.value = targetValue;
        option.textContent = injectLabel;
        select.appendChild(option);
      }
      select.value = targetValue;
      select.dataset.lockedValue = targetValue;
    }

    select.dataset.locked = '1';
    select.setAttribute('aria-readonly', 'true');
    select.classList.add('tb-select-locked');

    if (!select.__tbReadonlyBound) {
      select.addEventListener('mousedown', (event) => {
        if (select.dataset.locked === '1') event.preventDefault();
      }, true);
      select.addEventListener('keydown', (event) => {
        if (select.dataset.locked === '1') event.preventDefault();
      }, true);
      select.addEventListener('change', () => {
        if (select.dataset.locked === '1' && select.dataset.lockedValue) {
          select.value = select.dataset.lockedValue;
        }
      });
      select.__tbReadonlyBound = true;
    }
  } else {
    select.dataset.locked = '0';
    select.dataset.lockedValue = '';
    select.removeAttribute('aria-readonly');
    select.classList.remove('tb-select-locked');
  }
}