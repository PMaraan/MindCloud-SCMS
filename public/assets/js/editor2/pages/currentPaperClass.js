export default function currentPaperClass(){
  const val = (document.getElementById('ctl-paper')?.value || 'A4');
  return `size-${val}`;
}
