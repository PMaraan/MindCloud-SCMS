export default function frameBlock(el){
  el.classList.add('mc-block');
  el.dataset.rows = '1';
  Object.assign(el.style, {
    position:'absolute', left:'32px', width:'calc(100% - 32px)',
    boxSizing:'border-box', background:'transparent', border:'none',
    padding:'0 32px', pointerEvents:'auto',
  });
  if (!el.querySelector('.drag-handle')) {
    const grip = document.createElement('div');
    grip.className = 'drag-handle';
    Object.assign(grip.style, {
      position:'absolute', left:'6px', top:'0', bottom:'0', width:'18px',
      display:'grid', placeItems:'center', cursor:'grab', color:'#9ca3af', userSelect:'none'
    });
    grip.innerHTML = '⋮⋮';
    el.appendChild(grip);
  }
  if (!el.querySelector('.remove-btn')) {
    const btn = document.createElement('button');
    btn.className='remove-btn'; btn.type='button'; btn.innerHTML='×';
    Object.assign(btn.style, {
      position:'absolute', right:'6px', top:'0', width:'22px', height:'22px',
      borderRadius:'11px', border:'1px solid #e5e7eb', background:'#fff', cursor:'pointer',
      lineHeight:'20px', textAlign:'center'
    });
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const overlay = el.closest('.mc-block-overlay');
      el.remove();
      if (overlay) {
        const evt = new Event('mc:overlay-reflow');
        overlay.dispatchEvent(evt);
      }
    });
    el.appendChild(btn);
  }
}
