export default function waitForEditor(){
  return new Promise((resolve) => {
    const get = () => window.__mc?.getActiveEditor?.();
    const ed = get();
    if (ed) return resolve(ed);
    const iv = setInterval(() => {
      const e2 = get();
      if (e2) { clearInterval(iv); resolve(e2); }
    }, 20);
  });
}
