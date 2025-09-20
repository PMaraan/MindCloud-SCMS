import nextPageNumber from './nextPageNumber.js';
import currentPaperClass from './currentPaperClass.js';
import updatePageNumbers from './updatePageNumbers.js';
import MCEditors from '../core/MCEditors.js';
import makeEditorFor from '../core/makeEditorFor.js';

export default async function createPage(){
  const workEl = document.getElementById('mc-work');
  const n = nextPageNumber();

  const page = document.createElement('section');
  page.className = `page ${currentPaperClass()}`;
  page.dataset.page = String(n);
  page.tabIndex = 0;
  page.id = `page-${n}`;
  page.innerHTML = `
    <div class="page-header">
      <label class="logo-upload" title="Upload logo">
        <input type="file" accept="image/*" hidden>
        <img alt="Logo" />
        <span class="logo-fallback"></span>
      </label>
      <div class="header-center">
        <h1 class="title" contenteditable="true">Enter Syllabus Title</h1>
        <p class="subtitle" contenteditable="true">Enter Subtitle</p>
      </div>
    </div>
    <div class="tiptap" data-editor aria-label="Document area"></div>
    <footer class="page-footer" aria-label="Page footer">
      <span class="footer-left" contenteditable="true" data-placeholder="Footer Text">Footer Text</span>
      <span class="footer-right">Page <span class="page-num">${n}</span></span>
    </footer>
  `;

  try {
    const firstPreview = document.getElementById('logoPreview');
    const src = firstPreview?.getAttribute('src');
    if (src) {
      const wrap = page.querySelector('.logo-upload');
      const img = wrap.querySelector('img');
      img.src = src;
      wrap.classList.add('has-image');
    }
  } catch {}

  workEl.appendChild(page);

  // re-mount editor on this page using globally available deps via entry
  const deps = window.__mcEditorDeps;
  const ed = await makeEditorFor(deps || window, page);
  const key = page.id || page.dataset.page;
  MCEditors.set(key, ed);

  requestAnimationFrame(() => { try { (MCEditors.get(key) || MCEditors.first())?.commands.focus('start'); } catch {} });
  try { ed?.chain().focus('start', { scrollIntoView: false }).run(); } catch { ed?.commands?.focus?.(); }
  setTimeout(() => { try { ed?.chain().focus('start', { scrollIntoView: false }).run(); } catch { ed?.commands?.focus?.(); } }, 0);

  window.__mc?.rewireDropTargets?.();
  updatePageNumbers();
}
