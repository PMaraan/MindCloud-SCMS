import GRID from './GRID.js';
export default function snap(v){ return Math.round(v / GRID) * GRID; }
