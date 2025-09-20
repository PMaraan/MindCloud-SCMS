import setLogo from './setLogo.js';
let logoUrl = null;
export default function wireLogoUploader(){
  const upload  = document.getElementById('logoInput');
  upload?.addEventListener('change', () => {
    const file = upload.files?.[0];
    if (!file) return;
    if (logoUrl) URL.revokeObjectURL(logoUrl);
    logoUrl = URL.createObjectURL(file);
    setLogo(logoUrl);
  });
}
