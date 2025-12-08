// /public/assets/js/rteditor/modules/toolbarBinder.js
import { getBasePath } from "./editorInstance.js";

export function bindBasicToolbar(editor, rootEl = document) {
  let currentTextColor = '#000000';
  let currentHighlightColor = '#fff59d';

  const highlightInput = rootEl.querySelector('[data-cmd-input="setHighlight"]');
  if (highlightInput?.value) currentHighlightColor = highlightInput.value;
  const textColorInput = rootEl.querySelector('[data-cmd-input="setColor"]');
  if (textColorInput?.value) currentTextColor = textColorInput.value;

  const map = {
    toggleBold: () => editor.chain().focus().toggleBold().run(),
    toggleItalic: () => editor.chain().focus().toggleItalic().run(),
    toggleUnderline: () => editor.chain().focus().toggleUnderline().run(),
    toggleStrike: () => editor.chain().focus().toggleStrike().run(),
    toggleSubscript: () => editor.chain().focus().toggleSubscript().run(),
    toggleSuperscript: () => editor.chain().focus().toggleSuperscript().run(),

    bulletList: () => editor.chain().focus().toggleBulletList().run(),
    orderedList: () => editor.chain().focus().toggleOrderedList().run(),
    indentList: () => editor.chain().focus().sinkListItem('listItem').run(),
    outdentList: () => editor.chain().focus().liftListItem('listItem').run(),

    alignLeft: () => editor.chain().focus().setTextAlign('left').run(),
    alignCenter: () => editor.chain().focus().setTextAlign('center').run(),
    alignRight: () => editor.chain().focus().setTextAlign('right').run(),
    alignJustify: () => editor.chain().focus().setTextAlign('justify').run(),

    setColor: (hex) => { currentTextColor = hex || currentTextColor; return editor.chain().focus().setColor(currentTextColor).run(); },
    unsetColor: () => editor.chain().focus().unsetColor().run(),

    applyHighlight: () => {
      const color = currentHighlightColor || '#fff59d';
      if (editor.isActive('highlight', { color })) {
        return editor.chain().focus().unsetHighlight().run();
      }
      return editor.chain().focus().setHighlight({ color }).run();
    },
    setHighlight: (color) => { currentHighlightColor = color || currentHighlightColor; return editor.chain().focus().setHighlight({ color: currentHighlightColor }).run(); },
    unsetHighlight: () => editor.chain().focus().unsetHighlight().run(),

    undo: () => editor.chain().focus().undo().run(),
    redo: () => editor.chain().focus().redo().run(),

    setFontFamily: (family) => editor.chain().focus().setFontFamily(family).run(),
    unsetFontFamily: () => editor.chain().focus().setFontFamily(null).run(),

    setFontSize: (size) => editor.chain().focus().setFontSize(size).run(),
    unsetFontSize: () => editor.chain().focus().unsetFontSize().run(),

    setLineSpacing: (lh) => editor.chain().focus().setLineHeight(lh).run(),
    unsetLineSpacing: () => editor.chain().focus().unsetLineHeight().run(),
    setParaBefore: (pt) => editor.chain().focus().setParagraphSpacingBefore(pt).run(),
    setParaAfter:  (pt) => editor.chain().focus().setParagraphSpacingAfter(pt).run(),
    unsetParaSpacing: () => editor.chain().focus().unsetParagraphSpacing().run(),

    insertTable: () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
    addRowBefore: () => editor.chain().focus().addRowBefore().run(),
    addRowAfter:  () => editor.chain().focus().addRowAfter().run(),
    deleteRow:    () => editor.chain().focus().deleteRow().run(),
    addColumnBefore: () => editor.chain().focus().addColumnBefore().run(),
    addColumnAfter:  () => editor.chain().focus().addColumnAfter().run(),
    deleteColumn:    () => editor.chain().focus().deleteColumn().run(),
    toggleHeaderRow: () => editor.chain().focus().toggleHeaderRow().run(),
    mergeCells:      () => editor.chain().focus().mergeCells().run(),
    splitCell:       () => editor.chain().focus().splitCell().run(),
    deleteTable:     () => editor.chain().focus().deleteTable().run(),

    insertSignature: () => editor.chain().focus().insertSignatureField({ label: 'Signature', role: '', required: true }).run(),
    sigSetRole: (role) => editor.chain().focus().updateSignatureField({ role }).run(),
    sigToggleRequired: () => {
      const current = editor.getAttributes('signatureField')?.required;
      return editor.chain().focus().updateSignatureField({ required: !current }).run();
    },

    insertPageBreak: () => editor.chain().focus().insertPageBreak().run(),

    saveDoc: async () => {
      try {
        const params = new URLSearchParams(location.search);
        const _tpl = parseInt(params.get('templateId') || '0', 10);
        const _syl = parseInt(params.get('syllabusId') || '0', 10);

        let id   = parseInt(params.get('id') || '0', 10);
        let scope = '';

        if (_tpl > 0)        { id = _tpl; scope = 'template'; }
        else if (_syl > 0)   { id = _syl; scope = 'syllabus'; }
        else if (id > 0)     { scope = (params.get('scope') === 'syllabus') ? 'syllabus' : 'template'; }

        if (!id) {
          const base = getBasePath();
          window.location.href = `${base}/dashboard?page=syllabus-templates&flash=missing-id`;
          throw new Error('Missing id');
        }

        const json = editor.getJSON();
        const filename = null;
        const headers = {};
        if (window.CSRF_TOKEN) headers['X-CSRF-Token'] = window.CSRF_TOKEN;

        const base = getBasePath();
        const url  = `${base}/dashboard?page=rteditor&action=snapshot`;

        const resp = await fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', ...headers },
          body: JSON.stringify({ scope, id, json, filename }),
          credentials: 'same-origin'
        });
        const data = await resp.json();
        if (!resp.ok || !data?.ok) throw new Error(data?.error || `HTTP ${resp.status}`);

        // tiny UX feedback
        const btn = rootEl.querySelector('[data-cmd="saveDoc"]');
        if (btn) {
          const old = btn.innerHTML;
          btn.innerHTML = '<i class="bi bi-check2-circle"></i>';
          setTimeout(() => { btn.innerHTML = old; }, 1200);
        }
      } catch (e) {
        console.error('[RTEditor] Save failed:', e);
        const btn = rootEl.querySelector('[data-cmd="saveDoc"]');
        if (btn) {
          const old = btn.innerHTML;
          btn.innerHTML = '<i class="bi bi-x-circle"></i>';
          setTimeout(() => { btn.innerHTML = old; }, 1500);
        }
      }
    },

  };

  // wire buttons
  rootEl.querySelectorAll('[data-cmd]').forEach(btn => {
    const cmd = btn.getAttribute('data-cmd');
    if (!map[cmd]) return;
    btn.addEventListener('click', e => { e.preventDefault(); map[cmd](); });
  });

  // wire inputs
  rootEl.querySelectorAll('[data-cmd-input]').forEach(inp => {
    const cmd = inp.getAttribute('data-cmd-input');
    if (!map[cmd]) return;
    inp.addEventListener('input', e => {
      const val = e.target.value;
      if (!val) return;
      if (cmd === 'setHighlight') currentHighlightColor = val;
      if (cmd === 'setColor') currentTextColor = val;
      map[cmd](val);
    });
    inp.addEventListener('dblclick', () => {
      const clearCmd = (cmd === 'setColor') ? 'unsetColor' : (cmd === 'setHighlight' ? 'unsetHighlight' : null);
      if (clearCmd && map[clearCmd]) map[clearCmd]();
    });
  });

  // Ctrl+S / Cmd+S on rootEl
  rootEl.addEventListener('keydown', (ev) => {
    const isMac = /Mac|iPhone|iPad/.test(navigator.platform);
    const mod = isMac ? ev.metaKey : ev.ctrlKey;
    if (mod && ev.key.toLowerCase() === 's') {
      ev.preventDefault();
      map.saveDoc();
    }
  });
}
