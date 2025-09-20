import GRID from '../utils/GRID.js';
import frameBlock from './frameBlock.js';
import pushDownFrom from './pushDownFrom.js';

export default function makeSignatureRow(){
  const el = document.createElement('div');
  frameBlock(el);

  const row = document.createElement('div');
  Object.assign(row.style, { display:'grid', gridTemplateColumns:'repeat(4, 1fr)', gap:'12px', alignItems:'start' });

  for (let i = 0; i < 4; i++) {
    const cell = document.createElement('div');
    Object.assign(cell.style, { border:'1px dashed #cbd5e1', borderRadius:'6px', padding:'8px', display:'grid', gap:'6px' });

    const imgWrap = document.createElement('div');
    Object.assign(imgWrap.style, { aspectRatio:'4/3', background:'#f8fafc', borderRadius:'4px', display:'grid', placeItems:'center', overflow:'hidden' });
    const img = document.createElement('img');
    Object.assign(img.style, { display:'none', width:'100%', height:'100%', objectFit:'contain' });
    const btn = document.createElement('button'); btn.type='button'; btn.textContent='Upload';
    Object.assign(btn.style, { border:'1px solid #e5e7eb', background:'#fff', padding:'4px 8px', borderRadius:'4px', cursor:'pointer' });
    const inputFile = document.createElement('input'); inputFile.type='file'; inputFile.accept='image/*'; inputFile.style.display='none';
    btn.onclick = () => inputFile.click();
    inputFile.onchange = () => {
      if (inputFile.files[0]) {
        const reader = new FileReader();
        reader.onload = () => { img.src = reader.result; img.style.display='block'; btn.style.display='none'; };
        reader.readAsDataURL(inputFile.files[0]);
      }
    };
    imgWrap.appendChild(img); imgWrap.appendChild(btn); cell.appendChild(imgWrap);

    const line = document.createElement('div'); Object.assign(line.style, { borderBottom:'1px solid #9ca3af', marginTop:'4px' }); cell.appendChild(line);

    ['Name','Date','Role'].forEach((t) => {
      if (t === 'Date') {
        const wrap = document.createElement('div'); Object.assign(wrap.style, { display:'flex', alignItems:'center', gap:'6px' });
        const date = document.createElement('input'); date.type='date'; date.placeholder='YYYY-MM-DD'; date.title='Enter date (YYYY-MM-DD)';
        Object.assign(date.style, { width:'100%', padding:'4px 6px', border:'1px solid #cbd5e1', borderRadius:'6px', fontSize:'12px', color:'#111827', background:'#fff' });
        wrap.appendChild(date);
        const hint = document.createElement('span'); hint.textContent='Date'; Object.assign(hint.style, { fontSize:'12px', color:'#64748b', whiteSpace:'nowrap' });
        wrap.appendChild(hint);
        cell.appendChild(wrap);
      } else {
        const lab = document.createElement('div'); lab.textContent = t; lab.contentEditable = 'true';
        Object.assign(lab.style, { fontSize:'12px', color:'#64748b', whiteSpace:'nowrap', outline:'none' });
        lab.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
        cell.appendChild(lab);
      }
    });

    row.appendChild(cell);
  }

  el.appendChild(row);
  requestAnimationFrame(() => {
    const rows = Math.max(2, Math.ceil(el.scrollHeight / GRID));
    el.style.height = `${rows * GRID}px`;
    el.dataset.rows = String(rows);
    const overlay = el.closest('.mc-block-overlay');
    if (overlay) pushDownFrom(el, overlay);
  });

  return el;
}
