export default function ensureTTTablebar(){
  let bar = document.body.querySelector('.tt-tablebar');
  if (!bar) {
    bar = document.createElement('div');
    bar.className = 'tt-tablebar';
    bar.innerHTML = `
      <button class="btn" data-act="row-above"   title="Insert row above">↥ Row</button>
      <button class="btn" data-act="row-below"   title="Insert row below">↧ Row</button>
      <button class="btn" data-act="col-left"    title="Insert col left">↤ Col</button>
      <button class="btn" data-act="col-right"   title="Insert col right">↦ Col</button>
      <span class="sep"></span>
      <button class="btn" data-act="del-row"     title="Delete row">✖ Row</button>
      <button class="btn" data-act="del-col"     title="Delete column">✖ Col</button>
      <span class="sep"></span>
      <button class="btn" data-act="merge"       title="Merge selected cells">Merge</button>
      <button class="btn" data-act="split"       title="Split cell">Split</button>
      <span class="sep"></span>
      <button class="btn" data-act="toggle-head" title="Toggle header row">H</button>
      <span class="sep"></span>
      <button class="btn" data-act="del-table"   title="Delete table">🗑</button>
      <div class="tt-bar-hint" aria-live="polite" style="display:none"></div>
    `;
    document.body.appendChild(bar);
  }
  if (!bar._mcGuarded) {
    bar._mcGuarded = true;
    const set = (v) => { window.__mc = window.__mc || {}; window.__mc._ttBarInteracting = !!v; };
    const clearSoon = () => setTimeout(() => set(false), 0);
    bar.addEventListener('pointerdown', () => set(true));
    bar.addEventListener('pointerup', clearSoon);
    bar.addEventListener('pointercancel', clearSoon);
    bar.addEventListener('pointerleave', clearSoon);
    bar.addEventListener('mouseenter', () => set(true));
    bar.addEventListener('mouseleave', clearSoon);
  }
  return bar;
}
