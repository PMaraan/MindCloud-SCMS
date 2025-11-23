// /public/assets/js/rteditor/modules/editorInstance.js
import { Editor } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";

import Underline    from "@tiptap/extension-underline";
import Strike       from "@tiptap/extension-strike";
import Subscript    from "@tiptap/extension-subscript";
import Superscript  from "@tiptap/extension-superscript";
import TextStyle    from "@tiptap/extension-text-style";
import Color        from "@tiptap/extension-color";
import Highlight    from "@tiptap/extension-highlight";
import TextAlign    from "@tiptap/extension-text-align";
import FontFamily   from "@tiptap/extension-font-family";

import Table        from "@tiptap/extension-table";
import TableRow     from "@tiptap/extension-table-row";
import TableCell    from "@tiptap/extension-table-cell";
import TableHeader  from "@tiptap/extension-table-header";

import EnterShortcuts   from "../extensions/enter-shortcuts.js";
import ListShortcuts    from "../extensions/list-shortcuts.js";
import FontSize         from "../extensions/font-size.js";
import SpacingExtension from "../extensions/spacing.js";

import PageBreak        from "../nodes/page-break.js";
import SignatureField   from "../nodes/signature-field.js";

/* Hydration helper reused inside this module (keeps editor init tidy) */
function readInitialDocFromScriptTag() {
  const tag = document.getElementById('rt-initial-json');
  if (!tag) return null;
  const raw = (tag.textContent || '').replace(/^\uFEFF/, '').trim();
  if (!raw || raw.startsWith('<')) return null;
  try {
    const parsed = JSON.parse(raw);
    if (parsed && typeof parsed === 'object' && parsed.json && typeof parsed.json === 'object') {
      return parsed.json;
    }
    return parsed;
  } catch (err) {
    console.warn('[RTEditor] hydration skipped:', err);
    return null;
  }
}

export function getBasePath() {
  if (typeof window.BASE_PATH !== 'undefined' && window.BASE_PATH) return window.BASE_PATH;
  const path = window.location.pathname;
  const cut = path.indexOf('/dashboard');
  return cut > -1 ? path.slice(0, cut) : '';
}

export default function initBasicEditor(opts) {
  const { selector, editable = true, initialHTML = "<p>Start typing…</p>" } = opts || {};
  const mount = document.querySelector(selector);
  if (!mount) throw new Error(`[RTEditor] mount not found: ${selector}`);

  const editor = new Editor({
    element: mount,
    editable,
    content: (() => {
      const doc = readInitialDocFromScriptTag();
      if (doc && doc.type) return doc;
      return initialHTML;
    })(),
    extensions: [
      StarterKit.configure({ history: true, strike: false }),
      EnterShortcuts,
      ListShortcuts,
      Underline, Strike, Subscript, Superscript,
      TextStyle, Color, Highlight,
      TextAlign.configure({ types: ['heading','paragraph'], alignments: ['left','center','right','justify'] }),
      FontFamily, FontSize, SpacingExtension,
      Table.configure({ resizable: true, lastColumnResizable: true, allowTableNodeSelection: true }),
      TableRow, TableHeader, TableCell,
      SignatureField, PageBreak
    ],
  });

  try { editor.commands.focus("end"); } catch {}

  window.editor = editor;
  return editor;
}
