/**
 * 4×4 signature table (TipTap/PM compatible).
 * - Row 0: upload boxes (rendered by the UploadBox node via <div.mc-upload-box>)
 * - Row 1: Name
 * - Row 2: Date (plain text placeholder; inputs aren’t part of the schema)
 * - Row 3: Role
 * Marked with class "sig-table" and data-sig="1" so we can detect it in the DOM.
 */
export function signatureTableHTML() {
  const rows = 4, cols = 4;
  let html = '<table class="sig-table" data-sig="1"><tbody>';

  for (let r = 0; r < rows; r++) {
    html += '<tr>';
    for (let c = 0; c < cols; c++) {
      if (r === 0) {
        html += '<td><div class="mc-upload-box"></div></td>';
      } else if (r === 1) {
        html += '<td data-ph="Name"><p data-ph="Name">Name</p></td>';
      } else if (r === 2) {
        html += '<td><div class="mc-date-box"></div></td>';
      } else {
        html += '<td data-ph="Role"><p data-ph="Role">Role</p></td>';
      }
    }
    html += '</tr>';
  }

  // trailing paragraph so caret lands AFTER the table
  html += '</tbody></table><p></p>';
  return html;
}

export function signatureTableJSON() {
  // 4 rows × 4 cols
  const rows = [];
  for (let r = 0; r < 4; r++) {
    const cells = [];
    for (let c = 0; c < 4; c++) {
      if (r === 0) {
        // Upload row
        cells.push({
          type: 'tableCell',
          content: [{ type: 'uploadBox' }],
        });
      } else if (r === 1) {
        // Name row
        cells.push({
          type: 'tableCell',
          content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Name' }] }],
        });
      } else if (r === 2) {
        // Date row — custom SigDate node (renders <input type="date">)
        cells.push({
          type: 'tableCell',
          content: [{ type: 'sigDate', attrs: { value: '' } }],
        });
      } else {
        // Role row
        cells.push({
          type: 'tableCell',
          content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Role' }] }],
        });
      }
    }
    rows.push({ type: 'tableRow', content: cells });
  }

  return {
    type: 'table',
    attrs: { class: 'sig-table', 'data-sig': '1' },
    content: rows,
  };
}

