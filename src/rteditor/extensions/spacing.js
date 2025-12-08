// /src/rteditor/extensions/spacing.js
// Spacing Extension for TipTap Editor
import { Extension } from "@tiptap/core";

function patchBlocks(state, dispatch, typeNames, patch) {
  const { tr, selection } = state;
  const { from, to } = selection;
  const names = new Set(typeNames);

  state.doc.nodesBetween(from, to, (node, pos) => {
    if (!node?.type || !names.has(node.type.name)) return;
    const next = { ...node.attrs, ...patch };
    Object.keys(next).forEach(k => { if (next[k] === null) delete next[k]; });
    tr.setNodeMarkup(pos, node.type, next, node.marks);
  });

  if (tr.docChanged && dispatch) dispatch(tr);
  return tr.docChanged;
}

export default Extension.create({
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
      { types: ['paragraph', 'heading', 'blockquote', 'listItem'], attributes: { lineHeight: lineHeightAttr } },
      { types: ['paragraph', 'heading', 'blockquote'], attributes: { marginTop: marginTopAttr, marginBottom: marginBottomAttr } },
    ];
  },

  addCommands() {
    return {
      setLineHeight:
        value => ({ state, dispatch }) =>
          patchBlocks(state, dispatch, ['paragraph', 'heading', 'blockquote', 'listItem'], { lineHeight: String(value) }),

      unsetLineHeight:
        () => ({ state, dispatch }) =>
          patchBlocks(state, dispatch, ['paragraph', 'heading', 'blockquote', 'listItem'], { lineHeight: null }),

      setParagraphSpacingBefore:
        value => ({ state, dispatch }) =>
          patchBlocks(state, dispatch, ['paragraph', 'heading', 'blockquote'], { marginTop: String(value) }),

      setParagraphSpacingAfter:
        value => ({ state, dispatch }) =>
          patchBlocks(state, dispatch, ['paragraph', 'heading', 'blockquote'], { marginBottom: String(value) }),

      unsetParagraphSpacing:
        () => ({ state, dispatch }) =>
          patchBlocks(state, dispatch, ['paragraph', 'heading', 'blockquote'], { marginTop: null, marginBottom: null }),
    };
  },
});
