import MCEditors from './MCEditors.js';
import isSelectionInsideTable from '../tables/isSelectionInsideTable.js';
import moveCaretOutsideEnclosingTable from '../tables/moveCaretOutsideEnclosingTable.js';

export default async function makeEditorFor(deps, pageEl){
  const {
    Editor, StarterKit, Underline, Link, TextAlign, Placeholder, TextStyle,
    Color, FontFamily, FontSize, LineHeight, Superscript, Subscript,
    TaskList, TaskItem, TableWithAttrs, TableRow, TableHeader, TableCellWithAttrs,
    TabListIndent, UploadBox, DateInput, NoNestedTables, Plugin
  } = deps;

  const holder = pageEl.querySelector('[data-editor]');
  if (!holder) return null;
  const pageId = pageEl.id || pageEl.dataset.page || `page-${Date.now()}`;

  const ed = new Editor({
    element: holder,
    extensions: [
      StarterKit.configure({ heading: { levels: [1,2,3,4,5,6] } }),
      Underline,
      Link.configure({ openOnClick:true, autolink:true, HTMLAttributes:{ rel:'noopener noreferrer', target:'_blank' } }),
      TextAlign.configure({ types: ['heading','paragraph'] }),
      Placeholder.configure({ placeholder: 'Start typing…' }),
      TextStyle, Color, FontFamily, FontSize, LineHeight, Superscript, Subscript,
      TaskList.configure({ HTMLAttributes: { class: 'tt-tasklist' } }),
      TaskItem.configure({ nested: true, HTMLAttributes: { class: 'tt-taskitem' } }),
      TableWithAttrs.configure({ resizable: true }), TableRow, TableHeader, TableCellWithAttrs,
      TabListIndent, UploadBox, DateInput, NoNestedTables,
    ],
    content: '<p></p>',
    autofocus: false,
  });

  ed.view.dom.addEventListener('paste', (e) => {
    try {
      const html = e.clipboardData?.getData('text/html') || '';
      if (!html) return;
      if (/<table[\s>]/i.test(html) && isSelectionInsideTable(ed)) {
        e.preventDefault();
        moveCaretOutsideEnclosingTable(ed, 'after');
        ed.chain().focus().insertContent(html).run();
        ed.chain().focus().insertContent('<p></p>').run();
      }
    } catch {}
  }, true);

  MCEditors.set(pageId, ed);
  return ed;
}
