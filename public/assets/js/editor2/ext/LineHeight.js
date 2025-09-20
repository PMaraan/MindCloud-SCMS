import { Extension } from '../vendor/tiptap-core.js';
export default Extension.create({
  name: 'lineHeight',
  addGlobalAttributes(){
    return [{
      types: ['paragraph','heading','listItem','blockquote'],
      attributes: {
        lineHeight: {
          default: null,
          parseHTML: el => el.style.lineHeight || null,
          renderHTML: attrs => (attrs.lineHeight ? { style: `line-height:${attrs.lineHeight}` } : {})
        }
      }
    }];
  },
  addCommands(){
    const setLH = (value) => ({ tr, state, dispatch, editor }) => {
      const types = new Set(['paragraph','heading','listItem','blockquote'].map(n => editor.schema.nodes[n]).filter(Boolean));
      const { from, to } = state.selection;
      state.doc.nodesBetween(from, to, (node, pos) => {
        if (types.has(node.type)) tr.setNodeMarkup(pos, node.type, { ...node.attrs, lineHeight: value || null });
      });
      if (dispatch) dispatch(tr);
      return true;
    };
    return { setLineHeight: (v) => setLH(v), unsetLineHeight: () => setLH(null) };
  }
});
