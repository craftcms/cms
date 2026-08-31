import {LionInputFile} from '@lion/ui/input-file.js';
import {inputStyles} from '@src/styles/form.styles';
import styles from './input-file.styles.js';
import CraftSelectedFileList from './selected-file-list.js';
import {html} from 'lit';

/**
 * @summary A file input: a button that opens the file dialog, and a list of
 * the files chosen so far.
 *
 * Built on Lion's file input rather than `craft-input`, since it manages a
 * file list rather than a text value — so the base control's `maxlength`,
 * `size`, `width`, and friends do not apply. It supplies its own
 * `craft-button` for the dialog and renders the selection through
 * `craft-selected-file-list`.
 *
 * @slot label - The control's label, as an alternative to the `label`
 *   attribute.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages, including Lion's own file-type and
 *   file-size errors.
 * @slot file-select-button - The button that opens the file dialog. Supplied
 *   by the component; slotting your own replaces it.
 */
export default class CraftInputFile extends LionInputFile {
  static override get styles() {
    return [...super.styles, inputStyles, styles];
  }

  override get slots() {
    return {
      ...super.slots,
      'file-select-button': () =>
        html`<craft-button
          type="button"
          id="select-button-${this._inputId}"
          @click="${this.__openDialogOnBtnClick}"
        >
          ${this.buttonLabel}
        </craft-button>`,
    };
  }

  static override get scopedElements() {
    return {
      ...super.scopedElements,
      'lion-selected-file-list': CraftSelectedFileList,
    };
  }
}

if (!customElements.get('craft-input-file')) {
  customElements.define('craft-input-file', CraftInputFile);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-file': CraftInputFile;
  }
}
