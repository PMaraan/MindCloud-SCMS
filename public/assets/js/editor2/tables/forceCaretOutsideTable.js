import moveCaretOutsideEnclosingTable from './moveCaretOutsideEnclosingTable.js';
export default function forceCaretOutsideTable(ed, evOrPref='auto'){
  const dir = moveCaretOutsideEnclosingTable(ed, evOrPref);
  if (dir === 'after') ed.chain().insertContent('<p>\u200B</p>').run();
  return dir;
}
