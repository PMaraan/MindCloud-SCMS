const MIN_GUARD = 24;
export default function getFlowGuardPx(ed){
  const pageEl = ed?.options?.element?.closest?.('.page');
  const box    = pageEl?.querySelector('[data-editor]');
  const prose  = box?.querySelector('.ProseMirror') || box;
  if (!prose) return MIN_GUARD;
  const cs = getComputedStyle(prose);
  let lh = parseFloat(cs.lineHeight);
  if (!isFinite(lh)) {
    const fs = parseFloat(cs.fontSize) || 16;
    lh = fs * 1.25;
  }
  return Math.max(MIN_GUARD, Math.ceil(lh + 4));
}
