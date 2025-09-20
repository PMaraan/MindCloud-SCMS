import { Node } from '../vendor/tiptap-core.js';
export default Node.create({
  name: 'uploadBox',
  group: 'block',
  atom: true,
  selectable: true,
  addAttributes(){ return { src:{ default:null }, alt:{ default:'' } }; },
  parseHTML(){ return [{ tag:'upload-box' }]; },
  renderHTML({ HTMLAttributes }){ return ['upload-box', HTMLAttributes]; },
  addCommands(){
    return { insertUploadBox: (attrs={}) => ({ chain }) => chain().insertContent({ type: this.name, attrs }).run() };
  },
  addNodeView(){
    return ({ node, updateAttributes }) => {
      const dom = document.createElement('div'); dom.className = 'mc-upload-box'; dom.setAttribute('contenteditable','false');
      const img = document.createElement('img'); img.className = 'mc-upload-img';
      const btn = document.createElement('button'); btn.type='button'; btn.className='mc-upload-btn'; btn.textContent='Upload';
      const input = document.createElement('input'); input.type='file'; input.accept='image/*'; input.style.display='none';

      let objectUrl = null;
      const show = (src) => {
        if (src) { dom.dataset.hasImage='1'; img.src = src; img.style.display='block'; btn.style.display='none'; }
        else { dom.dataset.hasImage='0'; img.removeAttribute('src'); img.style.display='none'; btn.style.display='inline-block'; }
      };
      const stop = (e) => { e.preventDefault(); e.stopPropagation(); };
      [dom, btn, img, input].forEach(el => el.addEventListener('mousedown', stop));
      btn.addEventListener('click', (e)=>{ e.preventDefault(); input.value=''; input.click(); });
      img.addEventListener('click', ()=>{ input.value=''; input.click(); });

      input.addEventListener('change', () => {
        const file = input.files && input.files[0]; if (!file) return;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        dom.dataset.hasImage='1'; img.src = objectUrl; img.style.display='block'; btn.style.display='none';
        updateAttributes({ src: objectUrl, alt: file.name || '' });
      });

      dom.append(img, btn, input);
      show(node.attrs.src);

      return {
        dom,
        update(updatedNode){ if (updatedNode.type.name !== 'uploadBox') return false; show(updatedNode.attrs.src); return true; },
        ignoreMutation: () => true,
        destroy(){ if (objectUrl) URL.revokeObjectURL(objectUrl); }
      };
    };
  },
});
