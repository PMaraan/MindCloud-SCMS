const MCEditors = {
  map: new Map(),
  get(k){ return this.map.get(k) || null; },
  set(k, v){ this.map.set(k, v); return v; },
  first(){ return this.map.values().next().value || null; },
  all(){ return Array.from(this.map.values()); }
};
export default MCEditors;
