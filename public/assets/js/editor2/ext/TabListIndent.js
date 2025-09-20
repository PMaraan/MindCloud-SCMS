import { Extension } from '../vendor/tiptap-core.js';
export default Extension.create({
  name: 'tabListIndent',
  addKeyboardShortcuts(){
    return {
      Tab: () => {
        if (this.editor.isActive('listItem')) {
          const ok = this.editor.chain().focus().sinkListItem('listItem').run();
          return ok || true;
        }
        return false;
      },
      'Shift-Tab': () => {
        if (this.editor.isActive('listItem')) {
          const ok = this.editor.chain().focus().liftListItem('listItem').run();
          return ok || true;
        }
        return false;
      },
    };
  }
});
