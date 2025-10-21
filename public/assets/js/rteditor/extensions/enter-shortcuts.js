// /public/assets/js/rteditor/extensions/enter-shortcuts.js
// Enter Shortcuts Extension for TipTap Editor
import { Extension } from "@tiptap/core";

const EnterShortcuts = Extension.create({
  name: "enterShortcuts",
  addKeyboardShortcuts() {
    return {
      "Shift-Enter": () =>
        this.editor.commands.setHardBreak() ||
        this.editor.commands.insertContent("<br>"),
      Enter: () => {
        const ed = this.editor;
        if (ed.can().splitListItem?.('listItem')) return ed.commands.splitListItem('listItem');
        if (ed.can().liftListItem?.('listItem'))  return ed.commands.liftListItem('listItem');
        if (ed.commands.splitBlock()) return true;
        try {
          const { state } = ed;
          const $from = state.selection.$from;
          const insertPos = $from.end($from.depth);
          return ed.chain().focus()
            .insertContentAt(insertPos, { type: 'paragraph' }, { updateSelection: true })
            .run() || ed.commands.insertContent('<p></p>');
        } catch {
          return ed.commands.insertContent('<p></p>');
        }
      },
    };
  },
});

export default EnterShortcuts;
