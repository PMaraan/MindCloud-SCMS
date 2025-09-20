import { Extension } from '../vendor/tiptap-core.js';
import { Plugin } from '../vendor/prosemirror-state.js';
export default Extension.create({
  name: 'noNestedTables',
  addProseMirrorPlugins(){
    return [new Plugin({
      filterTransaction(tr){
        if (!tr.docChanged) return true;
        let ok = true;
        tr.doc.descendants((node, pos) => {
          if (!ok) return false;
          if (node.type.name === 'table') {
            const $pos = tr.doc.resolve(pos);
            for (let d = $pos.depth - 1; d >= 0; d--) {
              if ($pos.node(d).type.name === 'table') { ok = false; break; }
            }
          }
          return ok;
        });
        return ok;
      }
    })];
  }
});
