import rebalanceAround from './rebalanceAround.js';
export default function bindFlowHandlers(ed){
  if (ed._mcFlowBound) return;
  ed._mcFlowBound = true;
  ed.on('update', () => { rebalanceAround(ed); });
}
