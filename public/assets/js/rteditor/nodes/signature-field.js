// /public/assets/js/rteditor/nodes/signature-field.js
// Signature Field Node for TipTap Editor
import { Node } from "@tiptap/core";

const SignatureField = Node.create({
  name: 'signatureField',
  group: 'block',
  atom: true,
  selectable: true,
  draggable: true,

  addAttributes() {
    return {
      label: { default: 'Signature' },
      role: { default: '' },
      required: { default: true },
    };
  },

  parseHTML() {
    return [{ tag: 'div[data-signature-field]' }];
  },

  renderHTML({ HTMLAttributes }) {
    const { label, role, required } = HTMLAttributes;
    const attrs = {
      'data-signature-field': '1',
      'data-role': role || '',
      'data-required': String(!!required),
      class: 'rt-signature-field',
    };
    return [
      'div', attrs,
      ['div', { class: 'rt-signature-line' }],
      ['div', { class: 'rt-signature-meta' },
        `${label}${role ? ` — ${role}` : ''}${required ? ' (required)' : ''}`
      ],
    ];
  },

  addCommands() {
    return {
      insertSignatureField:
        (cfg = {}) => ({ commands }) =>
          commands.insertContent({ type: this.name, attrs: cfg }),
      updateSignatureField:
        (cfg = {}) => ({ state, dispatch }) => {
          const { tr, selection } = state;
          let updated = false;
          state.doc.nodesBetween(selection.from, selection.to, (node, pos) => {
            if (node.type.name === this.name) {
              const next = { ...node.attrs, ...cfg };
              tr.setNodeMarkup(pos, node.type, next, node.marks);
              updated = true;
            }
          });
          if (updated && dispatch) dispatch(tr);
          return updated;
        },
    };
  },
});

export default SignatureField;
