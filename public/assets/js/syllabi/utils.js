// /public/assets/js/syllabi/utils.js
export function installCssEscapeFallback() {
  if (!window.CSS || typeof window.CSS.escape !== 'function') {
    const pattern = /[{}|\\^~[\]`"<>#%]/g;
    window.CSS = window.CSS || {};
    window.CSS.escape = (value) => String(value).replace(pattern, '\\$&');
  }
}

export function getBase() {
  if (typeof window.BASE_PATH === 'string' && window.BASE_PATH) return window.BASE_PATH;
  const path = window.location.pathname;
  const cut = path.indexOf('/dashboard');
  return cut > -1 ? path.slice(0, cut) : '';
}

export async function fetchJSON(url, options = {}) {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: { 'Accept': 'application/json' },
    cache: 'no-store',
    ...options,
  });

  if (!response.ok) {
    const errText = await response.text().catch(() => '');
    throw new Error(`HTTP ${response.status}: ${errText}`);
  }

  const contentType = (response.headers.get('content-type') || '').toLowerCase();
  if (!contentType.includes('application/json')) {
    await response.text();
    throw new Error('Non-JSON response');
  }

  return response.json();
}

export function openSyllabus(templateId, { newTab = false } = {}) {
  if (!templateId) return;
  const href = `${getBase()}/dashboard?page=rteditor&syllabusId=${encodeURIComponent(templateId)}`;
  if (newTab) {
    window.open(href, '_blank', 'noopener');
  } else {
    window.location.href = href;
  }
}

export function capitalizeForDisplay(value) {
  if (!value) return '';
  const str = String(value);
  return str.charAt(0).toUpperCase() + str.slice(1);
}