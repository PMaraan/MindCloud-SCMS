import { Extension } from '../vendor/tiptap-core.js';
export default Extension.create({
  name: 'fontSize',
  addGlobalAttributes(){
    return [{
      types: ['textStyle'],
      attributes: {
        fontSize: {
          default: null,
          parseHTML: el => el.style.fontSize || null,
          renderHTML: attrs => (attrs.fontSize ? { style: `font-size:${attrs.fontSize}` } : {})
        }
      }
    }];
  },
});
