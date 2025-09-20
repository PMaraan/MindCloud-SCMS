import { Node } from '../vendor/tiptap-core.js';
export default Node.create({
  name: 'dateInput',
  group: 'inline',
  inline: true,
  atom: true,
  selectable: true,
  draggable: false,
  addAttributes(){ return { value:{ default:'' }, placeholder:{ default:'YYYY-MM-DD' } }; },
  parseHTML(){ return [{ tag:'date-input' }]; },
  renderHTML({ HTMLAttributes }){ return ['date-input', HTMLAttributes]; },
  addCommands(){ return { insertDateInput: (attrs={}) => ({ commands }) => commands.insertContent({ type:this.name, attrs }) }; },
  addNodeView(){
    return ({ node, editor, getPos }) => {
      const input = document.createElement('input');
      input.type = 'date'; input.className='sig-date-input';
      input.value = node.attrs.value || ''; input.placeholder = node.attrs.placeholder || 'YYYY-MM-DD';
      input.setAttribute('data-tt', 'date-input'); input.contentEditable = 'false';
      const updateAttr = (val) => {
        const pos = getPos && getPos();
        if (typeof pos === 'number') {
          editor.view.dispatch(editor.state.tr.setNodeMarkup(pos, undefined, { ...node.attrs, value: val }));
        }
      };
      input.addEventListener('change', () => updateAttr(input.value));
      input.addEventListener('input',  () => updateAttr(input.value));
      input.addEventListener('mousedown', (e) => e.stopPropagation());
      return {
        dom: input,
        update(updated){ if (updated.type.name !== 'dateInput') return false; if (updated.attrs.value !== input.value) input.value = updated.attrs.value || ''; return true; },
        ignoreMutation: () => true,
      };
    };
  },
});
