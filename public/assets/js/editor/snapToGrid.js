/**
 * public/assets/js/editor/snaptoGrid.js
 * Snap a numeric value to a fixed grid.
 * Pure utility: no DOM access, no side effects.
 * @param {number} value
 * @param {number} grid defaults to 20
 * @returns {number}
 */
export function snapToGrid(value, grid = 20) {
  return Math.round(value / grid) * grid;
}
