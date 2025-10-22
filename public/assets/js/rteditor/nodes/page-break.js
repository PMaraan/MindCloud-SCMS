// /public/assets/js/rteditor/nodes/page-break.js
// Page Break Node for TipTap Editor
import { Node } from "@tiptap/core";

const PageBreak = Node.create({
  name: "pageBreak",
  group: "block",
  atom: true,
  selectable: true,
  draggable: false,

  parseHTML() {
    return [{ tag: 'div[data-page-break]' }, { tag: 'hr[data-page-break]' }];
  },

  renderHTML() {
    return ['div', { 'data-page-break': '1', class: 'rt-page-break' }];
  },

  addCommands() {
    return {
      insertPageBreak: () => ({ chain }) =>
        chain().focus().insertContent({ type: this.name }).run(),
    };
  },
});

export default PageBreak;