(function () {
  function selectTile(card) {
    document.querySelectorAll('.tb-tile.tb-card--active')
      .forEach(el => el.classList.remove('tb-card--active'));
    card.classList.add('tb-card--active');

    const info  = document.getElementById('tb-info');
    const empty = document.getElementById('tb-info-empty');
    if (info && empty) {
      document.getElementById('tb-i-title').textContent   = card.dataset.title   || '';
      document.getElementById('tb-i-owner').textContent   = card.dataset.owner   || '';
      document.getElementById('tb-i-updated').textContent = card.dataset.updated || '';
      empty.classList.add('d-none');
      info.classList.remove('d-none');
    }

    window.__tb_selectedId = card.dataset.templateId || null;
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Folder rows are anchors now — no JS needed.

    // Template tile selection
    document.body.addEventListener('click', function (ev) {
      const card = ev.target.closest('.tb-tile');
      if (card) selectTile(card);
    });

    // Arrow key navigation across tiles
    document.addEventListener('keydown', function (ev) {
      if (!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(ev.key)) return;

      const tiles = Array.from(document.querySelectorAll('.tb-tile'));
      if (!tiles.length) return;

      let idx = tiles.findIndex(t => t.classList.contains('tb-card--active'));
      if (idx < 0) idx = 0;

      if (ev.key === 'ArrowRight' || ev.key === 'ArrowDown') {
        idx = Math.min(idx + 1, tiles.length - 1);
      }
      if (ev.key === 'ArrowLeft' || ev.key === 'ArrowUp') {
        idx = Math.max(idx - 1, 0);
      }

      tiles[idx].focus();
      tiles[idx].click();
    });

    const btnOpen = document.getElementById('tb-open');
    const btnDup  = document.getElementById('tb-duplicate');
    if (btnOpen) btnOpen.addEventListener('click', function () {
      if (!window.__tb_selectedId) return;
      alert('Open template ID: ' + window.__tb_selectedId);
    });
    if (btnDup) btnDup.addEventListener('click', function () {
      if (!window.__tb_selectedId) return;
      alert('Duplicate template ID: ' + window.__tb_selectedId);
    });
  });

  console.log('[TB] module JS loaded');
})();
