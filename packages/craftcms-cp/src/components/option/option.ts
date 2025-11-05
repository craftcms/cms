import styles from './option.styles.js';
import {LionOption} from '@lion/ui/listbox.js';

export default class CraftOption extends LionOption {
  static override get styles() {
    return [...LionOption.styles, styles];
  }
}

if (!customElements.get('craft-option')) {
  customElements.define('craft-option', CraftOption);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-option': CraftOption;
  }
}
