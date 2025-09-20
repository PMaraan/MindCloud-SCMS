import TableCellBase from '../vendor/tiptap-tablecell.js';
export default TableCellBase.extend({
  addAttributes(){
    return {
      ...this.parent?.(),
      'data-ph': {
        default: null,
        parseHTML: el => el.getAttribute('data-ph'),
        renderHTML: attrs => attrs['data-ph'] ? { 'data-ph': attrs['data-ph'] } : {},
      },
      class: { default: null, parseHTML: el => el.getAttribute('class'), renderHTML: attrs => attrs.class ? { class: attrs.class } : {} },
    };
  },
});
