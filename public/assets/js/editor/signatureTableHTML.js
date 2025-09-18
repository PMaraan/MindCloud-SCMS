/**
 * Build a 4×4 signature table as an HTML string for TipTap insertion.
 * Pure function: no DOM access, no globals.
 * Top row: upload boxes; next rows: Name / Date / Role labels & date-input shim.
 * @param {number} rows default 4
 * @param {number} cols default 4
 * @returns {string} HTML string
 */
export function signatureTableHTML(rows = 4, cols = 4) {
  let html = '<table class="sig-table" data-sig="1"><tbody>';

  for (let r = 0; r < rows; r++) {
    html += '<tr>';
    for (let c = 0; c < cols; c++) {
      if (r === 0) {
        html += '<td><upload-box></upload-box></td>';
      } else if (r === 1) {
        html += '<td data-ph="Name"><p data-ph="Name">Name</p></td>';
      } else if (r === 2) {
        html += '<td data-ph="Date"><p><date-input></date-input></p></td>';
      } else {
        html += '<td data-ph="Role"><p data-ph="Role">Role</p></td>';
      }
    }
    html += '</tr>';
  }

  html += '</tbody></table><p></p>';
  return html;
}
