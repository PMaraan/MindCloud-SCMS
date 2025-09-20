import moveCaretOutsideEnclosingTable from './moveCaretOutsideEnclosingTable.js';
export default function moveCaretAfterEnclosingTable(ed){
  return moveCaretOutsideEnclosingTable(ed, 'after') !== false;
}
