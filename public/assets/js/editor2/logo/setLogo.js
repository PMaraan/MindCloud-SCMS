export default function setLogo(src){
  const preview = document.getElementById('logoPreviewInner');
  const wrapper = preview?.closest('.logo-upload');
  if (!preview || !wrapper) return;
  preview.src = src || '';
  wrapper.classList.toggle('has-image', !!src);
}
