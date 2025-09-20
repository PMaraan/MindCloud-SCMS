export default function updatePageNumbers(){
  document.querySelectorAll('.page .page-num').forEach((span, idx) => { span.textContent = String(idx + 1); });
}
