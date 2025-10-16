// /public/assets/js/rteditor/collab-editor.js
import { Editor, Extension } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";

import Underline from "@tiptap/extension-underline";
import Strike from "@tiptap/extension-strike";
import Subscript from "@tiptap/extension-subscript";
import Superscript from "@tiptap/extension-superscript";
import TextStyle from "@tiptap/extension-text-style";
import Color from "@tiptap/extension-color";
import Highlight from "@tiptap/extension-highlight";
import TextAlign from "@tiptap/extension-text-align";

/** Enter behavior stays inside TipTap to avoid multi-PM issues */
const EnterShortcuts = Extension.create({
  name: "enterShortcuts",
  addKeyboardShortcuts() {
    return {
      "Shift-Enter": () =>
        this.editor.commands.setHardBreak() ||
        this.editor.commands.insertContent("<br>"),
      Enter: () => {
        const ed = this.editor;

        // 1) If inside a list item, try to split the list item (creates a new bullet/number)
        if (ed.can().splitListItem?.('listItem')) {
          return ed.commands.splitListItem('listItem');
        }

        // 2) If the list item is empty, lift out of the list (end the list)
        if (ed.can().liftListItem?.('listItem')) {
          return ed.commands.liftListItem('listItem');
        }

        // 3) Otherwise, normal block split
        if (ed.commands.splitBlock()) return true;

        // 4) Fallbacks for odd contexts
        try {
          const { state } = ed;
          const $from = state.selection.$from;
          const insertPos = $from.end($from.depth);
          return ed
            .chain()
            .focus()
            .insertContentAt(insertPos, { type: 'paragraph' }, { updateSelection: true })
            .run() || ed.commands.insertContent('<p></p>');
        } catch {
          return ed.commands.insertContent('<p></p>');
        }
      },
    };
  },
});

/** Build editor with common word-like extensions */
export default function initBasicEditor(opts) {
  const { selector, editable = true, initialHTML = "<p>Start typing…</p>" } = opts || {};
  const mount = document.querySelector(selector);
  if (!mount) throw new Error(`[RTEditor] mount not found: ${selector}`);

  const editor = new Editor({
    element: mount,
    editable,
    content: initialHTML,
    extensions: [
      StarterKit.configure({
        history: true,
        // we keep lists, blockquote, code, etc., from StarterKit
      }),
      EnterShortcuts,

      // text styles
      Underline,
      Strike,
      Subscript,
      Superscript,
      TextStyle, // required for Color & future font-size/family
      Color,
      Highlight,

      // alignment for these node types:
      TextAlign.configure({
        types: ['heading', 'paragraph'],
        alignments: ['left', 'center', 'right', 'justify']
      }),
    ],
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

/** Tiny command bus for the toolbar */
export function bindBasicToolbar(editor, root = document) {
  const map = {
    toggleBold: () => editor.chain().focus().toggleBold().run(),
    toggleItalic: () => editor.chain().focus().toggleItalic().run(),
    toggleUnderline: () => editor.chain().focus().toggleUnderline().run(),
    toggleStrike: () => editor.chain().focus().toggleStrike().run(),
    toggleSubscript: () => editor.chain().focus().toggleSubscript().run(),
    toggleSuperscript: () => editor.chain().focus().toggleSuperscript().run(),

    bulletList: () => editor.chain().focus().toggleBulletList().run(),
    orderedList: () => editor.chain().focus().toggleOrderedList().run(),

    alignLeft: () => editor.chain().focus().setTextAlign('left').run(),
    alignCenter: () => editor.chain().focus().setTextAlign('center').run(),
    alignRight: () => editor.chain().focus().setTextAlign('right').run(),
    alignJustify: () => editor.chain().focus().setTextAlign('justify').run(),

    setColor: (hex) => editor.chain().focus().setColor(hex).run(),
    unsetColor: () => editor.chain().focus().unsetColor().run(),

    setHighlight: (color) => editor.chain().focus().setHighlight({ color }).run(),
    unsetHighlight: () => editor.chain().focus().unsetHighlight().run(),

    undo: () => editor.chain().focus().undo().run(),
    redo: () => editor.chain().focus().redo().run(),
  };

  // Buttons with data-cmd
  root.querySelectorAll("[data-cmd]").forEach(btn => {
    const cmd = btn.getAttribute("data-cmd");
    if (!map[cmd]) return;
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      map[cmd]();
    });
  });

  // Inputs: color pickers for font color & highlight
  root.querySelectorAll("[data-cmd-input]").forEach(inp => {
    const cmd = inp.getAttribute("data-cmd-input");
    if (!map[cmd]) return;
    inp.addEventListener("input", (e) => {
      const val = e.target.value;
      if (val) map[cmd](val);
    });
    // optional double-click to clear
    inp.addEventListener("dblclick", () => {
      const clearCmd = (cmd === 'setColor') ? 'unsetColor' : (cmd === 'setHighlight' ? 'unsetHighlight' : null);
      if (clearCmd && map[clearCmd]) map[clearCmd]();
    });
  });
}
