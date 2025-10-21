// /public/assets/js/rteditor/collab-editor.js
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

// Local nodes & extensions
import PageBreak        from "./nodes/page-break.js";
import SignatureField   from "./nodes/signature-field.js";
import EnterShortcuts   from "./extensions/enter-shortcuts.js";
import ListShortcuts    from "./extensions/list-shortcuts.js";
import FontSize         from "./extensions/font-size.js";
import SpacingExtension from "./extensions/spacing.js";
import AutoPageBreak    from "./extensions/auto-pagebreak.js";

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
      StarterKit.configure({ history: true }),

      // behavior
      EnterShortcuts,
      ListShortcuts,

      // marks & styles
      TextStyle,
      Color,
      Highlight,
      Underline,
      Strike,
      Subscript,
      Superscript,
      FontFamily,
      FontSize,
      TextAlign.configure({ types: ['heading','paragraph'], alignments: ['left','center','right','justify'] }),
      SpacingExtension,

      // table
      Table.configure({ resizable: true, lastColumnResizable: true, allowTableNodeSelection: true }),
      TableRow, TableHeader, TableCell,

      // custom nodes
      SignatureField,
      PageBreak,

      // auto break when overflowing the first page
      AutoPageBreak,
    ],
  });

  try { editor.commands.focus("end"); } catch {}

  return editor;
}

/** Tiny command bus for the toolbar */
export function bindBasicToolbar(editor, root = document) {
  let currentTextColor = '#000000';
  let currentHighlightColor = '#fff59d';

  const highlightInput = root.querySelector('[data-cmd-input="setHighlight"]');
  if (highlightInput?.value) currentHighlightColor = highlightInput.value;
  const textColorInput = root.querySelector('[data-cmd-input="setColor"]');
  if (textColorInput?.value) currentTextColor = textColorInput.value;

  const map = {
    toggleBold: () => editor.chain().focus().toggleBold().run(),
    toggleItalic: () => editor.chain().focus().toggleItalic().run(),
    toggleUnderline: () => editor.chain().focus().toggleUnderline().run(),
    toggleStrike: () => editor.chain().focus().toggleStrike().run(),
    toggleSubscript: () => editor.chain().focus().toggleSubscript().run(),
    toggleSuperscript: () => editor.chain().focus().toggleSuperscript().run(),

    bulletList: () => editor.chain().focus().toggleBulletList().run(),
    orderedList: () => editor.chain().focus().toggleOrderedList().run(),
    indentList: () => editor.chain().focus().sinkListItem('listItem').run(),
    outdentList: () => editor.chain().focus().liftListItem('listItem').run(),

    alignLeft: () => editor.chain().focus().setTextAlign('left').run(),
    alignCenter: () => editor.chain().focus().setTextAlign('center').run(),
    alignRight: () => editor.chain().focus().setTextAlign('right').run(),
    alignJustify: () => editor.chain().focus().setTextAlign('justify').run(),

    setColor: (hex) => { currentTextColor = hex || currentTextColor; return editor.chain().focus().setColor(currentTextColor).run(); },
    unsetColor: () => editor.chain().focus().unsetColor().run(),

    applyHighlight: () => {
      const color = currentHighlightColor || '#fff59d';
      if (editor.isActive('highlight', { color })) return editor.chain().focus().unsetHighlight().run();
      return editor.chain().focus().setHighlight({ color }).run();
    },
    setHighlight: (color) => { currentHighlightColor = color || currentHighlightColor; return editor.chain().focus().setHighlight({ color: currentHighlightColor }).run(); },
    unsetHighlight: () => editor.chain().focus().unsetHighlight().run(),

    undo: () => editor.chain().focus().undo().run(),
    redo: () => editor.chain().focus().redo().run(),

    setFontFamily: (family) => editor.chain().focus().setFontFamily(family).run(),
    unsetFontFamily: () => editor.chain().focus().setFontFamily(null).run(),
    setFontSize: (size) => editor.chain().focus().setFontSize(size).run(),
    unsetFontSize: () => editor.chain().focus().unsetFontSize().run(),

    setLineSpacing: (lh) => editor.chain().focus().setLineHeight(lh).run(),
    unsetLineSpacing: () => editor.chain().focus().unsetLineHeight().run(),
    setParaBefore: (pt) => editor.chain().focus().setParagraphSpacingBefore(pt).run(),
    setParaAfter:  (pt) => editor.chain().focus().setParagraphSpacingAfter(pt).run(),
    unsetParaSpacing: () => editor.chain().focus().unsetParagraphSpacing().run(),

    insertTable: () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
    addRowBefore: () => editor.chain().focus().addRowBefore().run(),
    addRowAfter:  () => editor.chain().focus().addRowAfter().run(),
    deleteRow:    () => editor.chain().focus().deleteRow().run(),
    addColumnBefore: () => editor.chain().focus().addColumnBefore().run(),
    addColumnAfter:  () => editor.chain().focus().addColumnAfter().run(),
    deleteColumn:    () => editor.chain().focus().deleteColumn().run(),
    toggleHeaderRow: () => editor.chain().focus().toggleHeaderRow().run(),
    mergeCells:      () => editor.chain().focus().mergeCells().run(),
    splitCell:       () => editor.chain().focus().splitCell().run(),
    deleteTable:     () => editor.chain().focus().deleteTable().run(),

    insertSignature: () => editor.chain().focus().insertSignatureField({ label: 'Signature', role: '', required: true }).run(),
    sigSetRole: (role) => editor.chain().focus().updateSignatureField({ role }).run(),
    sigToggleRequired: () => {
      const current = editor.getAttributes('signatureField')?.required;
      return editor.chain().focus().updateSignatureField({ required: !current }).run();
    },

    insertPageBreak: () => editor.chain().focus().insertPageBreak().run(),
  };

  root.querySelectorAll('[data-cmd]').forEach(btn => {
    const cmd = btn.getAttribute('data-cmd');
    if (!map[cmd]) return;
    btn.addEventListener('click', e => { e.preventDefault(); map[cmd](); });
  });

  root.querySelectorAll('[data-cmd-input]').forEach(inp => {
    const cmd = inp.getAttribute('data-cmd-input');
    if (!map[cmd]) return;
    inp.addEventListener('input', e => {
      const val = e.target.value;
      if (!val) return;
      if (cmd === 'setHighlight') currentHighlightColor = val;
      if (cmd === 'setColor') currentTextColor = val;
      map[cmd](val);
    });
  });
}
