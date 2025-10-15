// Path: /public/assets/js/rteditor/collab-editor.js
// TipTap init (no Yjs), using a single import origin via import map.

import { Editor, Extension } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";

/**
 * Enter behavior implemented INSIDE TipTap (same PM instance).
 */
const EnterShortcuts = Extension.create({
  name: "enterShortcuts",
  addKeyboardShortcuts() {
    return {
      "Shift-Enter": () =>
        this.editor.commands.setHardBreak() ||
        this.editor.commands.insertContent("<br>"),

      Enter: () => {
        // Normal split
        if (this.editor.commands.splitBlock()) return true;

        // Fallback: insert empty paragraph after the current block
        try {
          const { state } = this.editor;
          const $from = state.selection.$from;
          const insertPos = $from.end($from.depth);
          return this.editor
            .chain()
            .focus()
            .insertContentAt(insertPos, { type: "paragraph" }, { updateSelection: true })
            .run() ||
            this.editor.commands.insertContent("<p></p>");
        } catch {
          return this.editor.commands.insertContent("<p></p>");
        }
      },
    };
  },
});

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
      EnterShortcuts,
    ],
    content: initialHTML,
  });

  try { editor.commands.focus("end"); } catch {}

  // Diagnostics
  const pm = mount.querySelector(".ProseMirror");
  if (pm) {
    const cs = getComputedStyle(pm);
    console.log("[RTEditor] ProseMirror ready:",
      "contenteditable=", pm.getAttribute("contenteditable"),
      "pointerEvents=", cs.pointerEvents
    );
  }

  return editor;
}

/**
 * Wire a minimal toolbar using [data-cmd] buttons existing in the DOM.
 */
export function bindBasicToolbar(editor, root = document) {
  const map = {
    toggleBold: () => editor.chain().focus().toggleBold().run(),
    toggleItalic: () => editor.chain().focus().toggleItalic().run(),
    undo: () => editor.chain().focus().undo().run(),
    redo: () => editor.chain().focus().redo().run(),
  };

  root.querySelectorAll("[data-cmd]").forEach(btn => {
    const cmd = btn.getAttribute("data-cmd");
    if (!map[cmd]) return;
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      map[cmd]();
    });
  });
}
