import styles from './textarea.styles.js';
import {inputStyles} from '../../styles/form.styles.js';
import {LionTextarea} from '@lion/ui/textarea.js';

export default class CraftTextarea extends LionTextarea {
  static override get styles() {
    return [...super.styles, inputStyles, styles];
  }
}

if (!customElements.get('craft-textarea')) {
  customElements.define('craft-textarea', CraftTextarea);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-textarea': CraftTextarea;
  }
}
