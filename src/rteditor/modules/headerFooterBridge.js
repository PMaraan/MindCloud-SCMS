// Filepath: /src/rteditor/modules/headerFooterBridge.js
/*
export function attachHeaderFooterEvents(container, view) {
  container.addEventListener('mousedown', e => {
    const target = e.target.closest('[data-role]');
    if (!target) return;

    e.preventDefault(); // 🚨 prevents caret jumping to ProseMirror

    const role = target.dataset.role;
    const pageIndex = Number(target.dataset.pageIndex);

    redirectClickToPM(role, pageIndex, e, view);
  });
}
*/