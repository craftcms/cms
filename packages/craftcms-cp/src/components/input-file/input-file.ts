import {LionInputFile} from '@lion/ui/input-file.js';
import {inputStyles} from '@/styles/form.styles';
import styles from './input-file.styles.js';
import CraftSelectedFileList from './selected-file-list.js';
import {html} from 'lit';

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
