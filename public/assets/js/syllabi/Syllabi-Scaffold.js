// /public/assets/js/syllabi/Syllabi-Scaffold.js
import { installCssEscapeFallback } from './utils.js';
import initTileInteractions from './tiles.js';

installCssEscapeFallback();

document.addEventListener('DOMContentLoaded', () => {
  initTileInteractions();
});
