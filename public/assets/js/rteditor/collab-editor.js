// Path: /public/assets/js/rteditor/collab-editor.js
// Minimal TipTap init (no Yjs). Uses ESM CDN for TipTap.

import { Editor } from "https://cdn.skypack.dev/@tiptap/core@2";
import StarterKit  from "https://cdn.skypack.dev/@tiptap/starter-kit@2";

/**
 * Initialize a basic TipTap editor.
 * @param {{selector: string, editable?: boolean, initialHTML?: string}} opts 
 */
export default function initBasicEditor(opts) {
  const { selector, editable = true, initialHTML = "<p>Start typing…</p>" } = opts || {};
  const mount = document.querySelector(selector);
  if (!mount) throw new Error(`[RTEditor] mount not found: ${selector}`);

  const editor = new Editor({
    element: mount,
    editable,
    extensions: [
      StarterKit.configure({ history: true }),
    ],
    content: initialHTML,
  });

  // Focus end defensively
  try { editor.commands.focus('end'); } catch (_) {}

  // Diagnostics
  const pm = mount.querySelector('.ProseMirror');
  if (pm) {
    const cs = getComputedStyle(pm);
    console.log('[RTEditor] ProseMirror ready:',
      'contenteditable=', pm.getAttribute('contenteditable'),
      'pointerEvents=', cs.pointerEvents
    );
  } else {
    console.warn('[RTEditor] ProseMirror element not found under', selector);
  }

  return editor;
}

/**
 * Wire a minimal toolbar using [data-cmd] buttons existing in the DOM.
 * @param {Editor} editor 
 * @param {Document|HTMLElement} root
 */
export function bindBasicToolbar(editor, root = document) {
  const map = {
    toggleBold: () => editor.chain().focus().toggleBold().run(),
    toggleItalic: () => editor.chain().focus().toggleItalic().run(),
    undo: () => editor.chain().focus().undo().run(),
    redo: () => editor.chain().focus().redo().run(),
  };

  root.querySelectorAll('[data-cmd]').forEach(btn => {
    const cmd = btn.getAttribute('data-cmd');
    if (!map[cmd]) return;
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      map[cmd]();
    });
  });
}
