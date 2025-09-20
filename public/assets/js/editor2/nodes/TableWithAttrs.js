import Table from '../vendor/tiptap-table.js';
export default Table.extend({
  addAttributes(){
    return {
      class: { default: null, parseHTML: el => el.getAttribute('class'), renderHTML: attrs => (attrs.class ? { class: attrs.class } : {}) },
      'data-sig': { default: null, parseHTML: el => el.getAttribute('data-sig'), renderHTML: attrs => (attrs['data-sig'] ? { 'data-sig': attrs['data-sig'] } : {}) },
    };
  },
  addCommands(){
    const parent = this.parent?.();
    return { ...parent };
  },
  addKeyboardShortcuts(){
    const parentKeys = this.parent?.() || {};
    return { ...parentKeys };
  }
});
