// /public/assets/js/rteditor/extensions/list-shortcuts.js
// List Shortcuts Extension for TipTap Editor
import { Extension } from "@tiptap/core";

const ListShortcuts = Extension.create({
  name: 'listShortcuts',
  addKeyboardShortcuts() {
    return {
      Tab: () => {
        const ed = this.editor;
        if (ed.can().sinkListItem?.('listItem')) return ed.commands.sinkListItem('listItem');
        return ed.commands.insertContent('    ');
      },
      'Shift-Tab': () => {
        const ed = this.editor;
        if (ed.can().liftListItem?.('listItem')) return ed.commands.liftListItem('listItem');
        return false;
      },
    };
  },
});

export default ListShortcuts;
