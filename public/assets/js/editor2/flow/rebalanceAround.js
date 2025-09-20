import flowForward from './flowForward.js';
import flowBackward from './flowBackward.js';
import updatePageNumbers from '../pages/updatePageNumbers.js';
export default async function rebalanceAround(ed){
  if (!ed) return;
  await flowForward(ed);
  await flowBackward(ed);
  await flowForward(ed);
  updatePageNumbers();
}
