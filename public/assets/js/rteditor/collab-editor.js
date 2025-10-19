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
import FontFamily from "@tiptap/extension-font-family";
import Table from "@tiptap/extension-table";
import TableRow from "@tiptap/extension-table-row";
import TableCell from "@tiptap/extension-table-cell";
import TableHeader from "@tiptap/extension-table-header";

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

const ListShortcuts = Extension.create({
  name: 'listShortcuts',
  addKeyboardShortcuts() {
    return {
      Tab: () => {
        const ed = this.editor;
        // Indent list item if possible
        if (ed.can().sinkListItem?.('listItem')) {
          return ed.commands.sinkListItem('listItem');
        }
        // Not a list: insert a few spaces (Word inserts a tab, we simulate)
        return ed.commands.insertContent('    '); // 4 spaces
      },
      'Shift-Tab': () => {
        const ed = this.editor;
        // Outdent list item if possible
        if (ed.can().liftListItem?.('listItem')) {
          return ed.commands.liftListItem('listItem');
        }
        // Not in a list: ignore (let browser move focus if any)
        return false;
      },
    };
  },
});

// Allow font-size via TextStyle (e.g., setFontSize('12pt') / unsetFontSize())
const FontSize = Extension.create({
  name: 'fontSize',
  addGlobalAttributes() {
    return [
      {
        types: ['textStyle'],
        attributes: {
          fontSize: {
            default: null,
            parseHTML: element => element.style.fontSize || null,
            renderHTML: attributes => {
              if (!attributes.fontSize) return {};
              return { style: `font-size: ${attributes.fontSize}` };
            },
          },
        },
      },
    ];
  },
  addCommands() {
    return {
      setFontSize:
        size => ({ chain }) =>
          chain().setMark('textStyle', { fontSize: size }).run(),
      unsetFontSize:
        () => ({ chain }) =>
          chain().setMark('textStyle', { fontSize: null }).removeEmptyTextStyle().run(),
    };
  },
});

// Spacing (line-height + paragraph spacing) for block nodes.
// - lineHeight on paragraph, heading, blockquote, listItem
// - marginTop/marginBottom on paragraph, heading, blockquote (not listItem)
const SpacingExtension = Extension.create({
  name: 'spacing',

  addGlobalAttributes() {
    const lineHeightAttr = {
      default: null,
      parseHTML: el => el.style.lineHeight || null,
      renderHTML: attrs => attrs.lineHeight ? { style: `line-height: ${attrs.lineHeight}` } : {},
    };
    const marginTopAttr = {
      default: null,
      parseHTML: el => el.style.marginTop || null,
      renderHTML: attrs => attrs.marginTop ? { style: `margin-top: ${attrs.marginTop}` } : {},
    };
    const marginBottomAttr = {
      default: null,
      parseHTML: el => el.style.marginBottom || null,
      renderHTML: attrs => attrs.marginBottom ? { style: `margin-bottom: ${attrs.marginBottom}` } : {},
    };

    return [
      // Apply line-height widely (incl. listItem so nested paragraphs inherit)
      { types: ['paragraph', 'heading', 'blockquote', 'listItem'], attributes: { lineHeight: lineHeightAttr } },
      // Apply margins to real block content (paragraph/heading/blockquote)
      { types: ['paragraph', 'heading', 'blockquote'], attributes: { marginTop: marginTopAttr, marginBottom: marginBottomAttr } },
    ];
  },

  addCommands() {
    // helper: patch attributes for blocks across the current selection
    const patchBlocks = (editor, typeNames, patch) => {
      const { state } = editor;
      const { tr, selection } = state;
      const from = selection.from, to = selection.to;
      const types = new Set(typeNames);
      state.doc.nodesBetween(from, to, (node, pos) => {
        if (!node || !node.type) return;
        if (!types.has(node.type.name)) return;
        const newAttrs = { ...node.attrs, ...patch };
        // remove nulls so we "unset" attributes
        Object.keys(newAttrs).forEach(k => { if (newAttrs[k] === null) delete newAttrs[k]; });
        tr.setNodeMarkup(pos, node.type, newAttrs, node.marks);
      });
      if (tr.docChanged) editor.view.dispatch(tr);
      return tr.docChanged;
    };

    return {
      setLineHeight:
        value => ({ editor }) => {
          const v = typeof value === 'number' ? String(value) : String(value);
          // Accepts '1', '1.15', '1.5', '2' (unitless) or CSS like '150%', '1.5em'
          return patchBlocks(editor, ['paragraph', 'heading', 'blockquote', 'listItem'], { lineHeight: v });
        },
      unsetLineHeight:
        () => ({ editor }) =>
          patchBlocks(editor, ['paragraph', 'heading', 'blockquote', 'listItem'], { lineHeight: null }),

      // Paragraph spacing expects values like '0pt', '6pt', '12pt', or CSS units '8px'
      setParagraphSpacingBefore:
        value => ({ editor }) =>
          patchBlocks(editor, ['paragraph', 'heading', 'blockquote'], { marginTop: String(value) }),
      setParagraphSpacingAfter:
        value => ({ editor }) =>
          patchBlocks(editor, ['paragraph', 'heading', 'blockquote'], { marginBottom: String(value) }),

      unsetParagraphSpacing:
        () => ({ editor }) =>
          patchBlocks(editor, ['paragraph', 'heading', 'blockquote'], { marginTop: null, marginBottom: null }),
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
      ListShortcuts,
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

      FontFamily,
      FontSize,

      SpacingExtension,

      Table.configure({
        resizable: true,    // column resizing UX
        lastColumnResizable: true,
        allowTableNodeSelection: true,
      }),
      TableRow,
      TableHeader,
      TableCell,
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
  // --- remember "current" colors (defaults)
  let currentTextColor = '#000000';
  let currentHighlightColor = '#fff59d';

  const highlightInput = root.querySelector('[data-cmd-input="setHighlight"]');
  if (highlightInput && highlightInput.value) {
    currentHighlightColor = highlightInput.value;
  }
  const textColorInput = root.querySelector('[data-cmd-input="setColor"]');
  if (textColorInput && textColorInput.value) {
    currentTextColor = textColorInput.value;
  }

  const map = {
    // text styles
    toggleBold: () => editor.chain().focus().toggleBold().run(),
    toggleItalic: () => editor.chain().focus().toggleItalic().run(),
    toggleUnderline: () => editor.chain().focus().toggleUnderline().run(),
    toggleStrike: () => editor.chain().focus().toggleStrike().run(),
    toggleSubscript: () => editor.chain().focus().toggleSubscript().run(),
    toggleSuperscript: () => editor.chain().focus().toggleSuperscript().run(),

    // lists
    bulletList: () => editor.chain().focus().toggleBulletList().run(),
    orderedList: () => editor.chain().focus().toggleOrderedList().run(),
    indentList: () => editor.chain().focus().sinkListItem('listItem').run(),
    outdentList: () => editor.chain().focus().liftListItem('listItem').run(),

    // alignment
    alignLeft: () => editor.chain().focus().setTextAlign('left').run(),
    alignCenter: () => editor.chain().focus().setTextAlign('center').run(),
    alignRight: () => editor.chain().focus().setTextAlign('right').run(),
    alignJustify: () => editor.chain().focus().setTextAlign('justify').run(),

    // colors
    setColor: (hex) => {
      currentTextColor = hex || currentTextColor;
      return editor.chain().focus().setColor(currentTextColor).run();
    },
    unsetColor: () => editor.chain().focus().unsetColor().run(),

    // highlight: one-click apply using "currentHighlightColor"
    applyHighlight: () => {
      const color = currentHighlightColor || '#fff59d';
      // If the exact same highlight is already active, toggle it off (quality-of-life)
      if (editor.isActive('highlight', { color })) {
        return editor.chain().focus().unsetHighlight().run();
      }
      return editor.chain().focus().setHighlight({ color }).run();
    },
    setHighlight: (color) => {
      currentHighlightColor = color || currentHighlightColor;
      return editor.chain().focus().setHighlight({ color: currentHighlightColor }).run();
    },
    unsetHighlight: () => editor.chain().focus().unsetHighlight().run(),

    // history
    undo: () => editor.chain().focus().undo().run(),
    redo: () => editor.chain().focus().redo().run(),

    setFontFamily: (family) => editor.chain().focus().setFontFamily(family).run(),
    unsetFontFamily: () => editor.chain().focus().setFontFamily(null).run(),

    setFontSize: (size) => editor.chain().focus().setFontSize(size).run(),
    unsetFontSize: () => editor.chain().focus().unsetFontSize().run(),

    // line spacing presets (unitless like Word UI)
    setLineSpacing: (lh) => editor.chain().focus().setLineHeight(lh).run(),
    unsetLineSpacing: () => editor.chain().focus().unsetLineHeight().run(),

    // paragraph spacing (use points to match Word presets)
    setParaBefore: (pt) => editor.chain().focus().setParagraphSpacingBefore(pt).run(),
    setParaAfter:  (pt) => editor.chain().focus().setParagraphSpacingAfter(pt).run(),
    unsetParaSpacing: () => editor.chain().focus().unsetParagraphSpacing().run(),

    // Tables
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
  };

  // Buttons with data-cmd
  root.querySelectorAll('[data-cmd]').forEach(btn => {
    const cmd = btn.getAttribute('data-cmd');
    if (!map[cmd]) return;
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      map[cmd]();
    });
  });

  // Inputs: keep state in sync (text color & highlight color)
  root.querySelectorAll('[data-cmd-input]').forEach(inp => {
    const cmd = inp.getAttribute('data-cmd-input');
    if (!map[cmd]) return;
    inp.addEventListener('input', (e) => {
      const val = e.target.value;
      if (!val) return;
      // update current color memory
      if (cmd === 'setHighlight') currentHighlightColor = val;
      if (cmd === 'setColor') currentTextColor = val;
      map[cmd](val);
    });
    // optional double-click to clear
    inp.addEventListener('dblclick', () => {
      const clearCmd = (cmd === 'setColor') ? 'unsetColor' : (cmd === 'setHighlight' ? 'unsetHighlight' : null);
      if (clearCmd && map[clearCmd]) map[clearCmd]();
    });
  });
}
