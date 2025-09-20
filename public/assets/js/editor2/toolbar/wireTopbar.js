import getActiveEditor from '../core/getActiveEditor.js';
import execOnBlockOrEditor from './execOnBlockOrEditor.js';
import wrapSelectionWithSpan from './wrapSelectionWithSpan.js';
import isSelectionInsideTable from '../tables/isSelectionInsideTable.js';
import forceCaretOutsideTable from '../tables/forceCaretOutsideTable.js';
import putCaretAboveJustInsertedTable from '../tables/putCaretAboveJustInsertedTable.js';

export default function wireTopbar(){
  const toolbar = document.getElementById('tt-toolbar');
  if (!toolbar) return;

  toolbar.addEventListener('click', (e) => {
    const el = e.target.closest('[data-action]');
    if (!el) return;
    const action = el.dataset.action;
    const level = +el.dataset.level || undefined;
    let ed = getActiveEditor();

    if (action === 'setLineHeight') {
      if (!ed) return;
      let lh = el.dataset.lh || '';
      if (lh === 'custom') {
        const v = prompt('Enter line spacing (e.g., 1, 1.15, 1.5, 2, or CSS like "24px")', '1.5');
        if (v === null) return;
        lh = v.trim();
        if (!lh) return;
      }
      ed.chain().focus().setLineHeight(lh).run();
      return;
    }
    if (action === 'unsetLineHeight') { if (!ed) return; ed.chain().focus().unsetLineHeight().run(); return; }

    switch (action) {
      case 'toggleBold':         execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().toggleBold().run(),'bold'); break;
      case 'toggleItalic':       execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().toggleItalic().run(),'italic'); break;
      case 'toggleUnderline':    execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().toggleUnderline().run(),'underline'); break;
      case 'toggleStrike':       execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().toggleStrike().run(),()=>document.execCommand('strikethrough')); break;
      case 'setParagraph':       execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().setParagraph().run(),()=>document.execCommand('formatBlock', false, 'P')); break;
      case 'setHeading':         execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().toggleHeading({ level }).run(),()=>document.execCommand('formatBlock', false, 'H' + (level || 1))); break;
      case 'toggleBulletList':   execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().toggleBulletList().run(),'insertUnorderedList'); break;
      case 'toggleOrderedList':  execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().toggleOrderedList().run(),'insertOrderedList'); break;
      case 'toggleBlockquote':   execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().toggleBlockquote().run(),()=>document.execCommand('formatBlock', false, 'BLOCKQUOTE')); break;
      case 'toggleCodeBlock':    execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().toggleCodeBlock().run(),null); break;
      case 'alignLeft':          execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().setTextAlign('left').run(),'justifyLeft'); break;
      case 'alignCenter':        execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().setTextAlign('center').run(),'justifyCenter'); break;
      case 'alignRight':         execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().setTextAlign('right').run(),'justifyRight'); break;
      case 'alignJustify':       execOnBlockOrEditor(ed,(e2)=>e2.chain().focus().setTextAlign('justify').run(),'justifyFull'); break;

      case 'setColor': {
        const value = el.dataset.value || null;
        execOnBlockOrEditor(
          ed,
          (e2)=>{ value ? e2.chain().focus().setColor(value).run() : e2.chain().focus().unsetColor().run(); },
          ()=>{ value ? wrapSelectionWithSpan(`color:${value}`) : wrapSelectionWithSpan('color:inherit'); }
        );
        break;
      }
      case 'setHorizontalRule':  if (!ed) return; ed.chain().focus().setHorizontalRule().run(); break;

      case 'insertTable': {
        if (!ed) return;
        if (isSelectionInsideTable(ed)) forceCaretOutsideTable(ed, 'auto');
        ed = window.__mc?.ensureRoomForTable?.(240) || ed;
        ed.chain().focus().insertContent('<p>\u200B</p>').run();
        const paraPosTop = Math.max(1, ed.state.selection.from - 1);
        ed.chain().focus().insertTable({ rows:3, cols:4, withHeaderRow:false }).run();
        putCaretAboveJustInsertedTable(ed, paraPosTop);
        break;
      }

      case 'insertUploadBox': { if (!ed) return; ed.chain().focus().insertUploadBox().run(); break; }
      case 'setLink': {
        if (!ed) return;
        const prev = ed.getAttributes?.('link')?.href || '';
        const url = prompt('Enter URL', prev);
        if (url === null) return;
        if (url === '') ed.chain().focus().unsetLink().run();
        else ed.chain().focus().setLink({ href: url }).run();
        break;
      }
      case 'unsetLink':   if (!ed) return; ed.chain().focus().unsetLink().run(); break;
      case 'undo':        if (!ed) return; ed.commands.undo(); break;
      case 'redo':        if (!ed) return; ed.commands.redo(); break;
    }
  });

  const selFont = document.getElementById('ctl-font');
  const selSize = document.getElementById('ctl-size');
  selFont?.addEventListener('change', () => {
    const ed = getActiveEditor(); if (!ed) return;
    const v = selFont.value; const c = ed.chain().focus();
    v ? c.setFontFamily?.(v).run() : c.unsetFontFamily?.().run();
  });
  selSize?.addEventListener('change', () => {
    const ed = getActiveEditor(); if (!ed) return;
    const v = selSize.value; const c = ed.chain().focus();
    v ? c.setMark('textStyle', { fontSize: v }).run() : c.setMark('textStyle', { fontSize: null }).run();
  });
}
