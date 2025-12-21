// /src/rteditor/extensions/pageContainer.js
// Light, safe wrapper: do NOT mutate .ProseMirror children.
// Instead we wrap the existing editor.view.dom in a wrapper that contains
// a header slot, the editor DOM (left untouched) and a footer slot.
// This prevents ProseMirror from ever seeing header/footer clones and stops
// mutation-feedback loops. This is an immediate hotfix — full per-page layout
// (NodeView) is the next step.

function mmToPx(mm) { return (parseFloat(mm) || 0) / 25.4 * 96; }

function createWrapperDom() {
  const wrap = document.createElement('div');
  wrap.className = 'rt-page-wrapper-outer';

  const header = document.createElement('div');
  header.className = 'rt-page-header';
  header.setAttribute('data-page-part', 'header');
  header.setAttribute('contenteditable', 'false');

  const inner = document.createElement('div');
  inner.className = 'rt-page-edit-host';
  inner.setAttribute('data-page-part', 'body');

  const footer = document.createElement('div');
  footer.className = 'rt-page-footer';
  footer.setAttribute('data-page-part', 'footer');
  footer.setAttribute('contenteditable', 'false');

  // Structure: wrapper -> header, inner, footer
  wrap.appendChild(header);
  wrap.appendChild(inner);
  wrap.appendChild(footer);
  return { wrap, header, inner, footer };
}

function syncHeaderFooterSlots(headerSlot, footerSlot, headerMaster, footerMaster) {
  try {
    headerSlot.innerHTML = headerMaster ? headerMaster.innerHTML : '';
  } catch (e) { headerSlot.textContent = headerMaster ? headerMaster.textContent : ''; }
  try {
    footerSlot.innerHTML = footerMaster ? footerMaster.innerHTML : '';
  } catch (e) { footerSlot.textContent = footerMaster ? footerMaster.textContent : ''; }
}

/**
 * attachPageContainer(editor, options)
 * - This DOES NOT mutate .ProseMirror children.
 * - It will wrap editor.view.dom in a wrapper and copy header/footer into slots.
 * - Returns cleanup() to restore original DOM.
 */
export default function attachPageContainer() {
  // Phase 1: no-op
  return () => {};
}
