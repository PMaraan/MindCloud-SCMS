// /src/rteditor/pageConfig.js

export const PAGE_SIZES_MM = {
  A4:     { w: 210, h: 297 },
  Letter: { w: 215.9, h: 279.4 },
  Legal:  { w: 215.9, h: 355.6 },
  A5:     { w: 148, h: 210 },
};

export function getPageConfig(root = document) {
  const sizeSel = root.querySelector('[data-page-size]');
  const orientSel = root.querySelector('[data-page-orientation]');

  const sizeKey = sizeSel?.value || 'A4';
  const orientation = orientSel?.value || 'portrait';

  const base = PAGE_SIZES_MM[sizeKey] || PAGE_SIZES_MM.A4;

  const isLandscape = orientation === 'landscape';

  return {
    size: {
        wmm: isLandscape ? base.h : base.w,
        hmm: isLandscape ? base.w : base.h,
    },
    orientation,
    sizeKey,
    margins: {
        top:    '25.4mm',
        right:  '25.4mm',
        bottom: '25.4mm',
        left:   '25.4mm',
    },
  };
}
