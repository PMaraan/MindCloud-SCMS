// /public/assets/js/rteditor/extensions/spacing.js
// Spacing Extension for TipTap Editor
import { Extension } from "@tiptap/core";

const Spacing = Extension.create({
  name: 'spacing',
  addGlobalAttributes() {
    const lineHeight = {
      default: null,
      parseHTML: el => el.style.lineHeight || null,
      renderHTML: a => a.lineHeight ? { style: `line-height:${a.lineHeight}` } : {},
    };
    const mTop = {
      default: null,
      parseHTML: el => el.style.marginTop || null,
      renderHTML: a => a.marginTop ? { style: `margin-top:${a.marginTop}` } : {},
    };
    const mBottom = {
      default: null,
      parseHTML: el => el.style.marginBottom || null,
      renderHTML: a => a.marginBottom ? { style: `margin-bottom:${a.marginBottom}` } : {},
    };
    return [
      { types: ['paragraph','heading','blockquote','listItem'], attributes: { lineHeight } },
      { types: ['paragraph','heading','blockquote'], attributes: { marginTop: mTop, marginBottom: mBottom } },
    ];
  },
  addCommands() {
    const patchBlocks = (editor, typeNames, patch) => {
      const { state } = editor;
      const { tr, selection } = state;
      const from = selection.from, to = selection.to;
      const allowed = new Set(typeNames);
      state.doc.nodesBetween(from, to, (node, pos) => {
        if (!node?.type || !allowed.has(node.type.name)) return;
        const next = { ...node.attrs, ...patch };
        Object.keys(next).forEach(k => { if (next[k] === null) delete next[k]; });
        tr.setNodeMarkup(pos, node.type, next, node.marks);
      });
      if (tr.docChanged) editor.view.dispatch(tr);
      return tr.docChanged;
    };
    return {
      setLineHeight: v => ({ editor }) =>
        patchBlocks(editor, ['paragraph','heading','blockquote','listItem'], { lineHeight: String(v) }),
      unsetLineHeight: () => ({ editor }) =>
        patchBlocks(editor, ['paragraph','heading','blockquote','listItem'], { lineHeight: null }),
      setParagraphSpacingBefore: v => ({ editor }) =>
        patchBlocks(editor, ['paragraph','heading','blockquote'], { marginTop: String(v) }),
      setParagraphSpacingAfter: v => ({ editor }) =>
        patchBlocks(editor, ['paragraph','heading','blockquote'], { marginBottom: String(v) }),
      unsetParagraphSpacing: () => ({ editor }) =>
        patchBlocks(editor, ['paragraph','heading','blockquote'], { marginTop: null, marginBottom: null }),
    };
  },
});

export default Spacing;
