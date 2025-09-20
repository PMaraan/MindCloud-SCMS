import ensureTTTablebar from './ensureTTTablebar.js';
export default function flashBarHint(msg){
  const bar = ensureTTTablebar();
  const hint = bar.querySelector('.tt-bar-hint');
  if (!hint) return;
  hint.textContent = msg;
  hint.style.display = 'block';
  clearTimeout(hint._t);
  hint._t = setTimeout(() => { hint.style.display = 'none'; }, 2200);
}
