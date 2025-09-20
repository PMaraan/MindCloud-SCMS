import makeLabel from './makeLabel.js';
import makeParagraph from './makeParagraph.js';
import makeTextField from './makeTextField.js';
import makeTextArea from './makeTextArea.js';
import makeSignatureRow from './makeSignatureRow.js';

const FACTORY = {
  label:     () => makeLabel(),
  paragraph: () => makeParagraph(),
  text:      () => makeTextField(),
  textarea:  () => makeTextArea(),
  signature: () => makeSignatureRow(),
  table:     null,          // tables go into TipTap, not overlay
  textField: () => makeTextField(), // alias
};
export default FACTORY;
