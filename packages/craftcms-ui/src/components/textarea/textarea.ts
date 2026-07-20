import styles from './textarea.styles.js';
import {inputStyles} from '../../styles/form.styles.js';
import {LionTextarea} from '@lion/ui/textarea.js';
import {property} from 'lit/decorators.js';

export default class CraftTextarea extends LionTextarea {
  static override get styles() {
    return [...super.styles, inputStyles, styles];
  }

  @property({type: Boolean, reflect: true})
  monospace: boolean = false;
}

if (!customElements.get('craft-textarea')) {
  customElements.define('craft-textarea', CraftTextarea);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-textarea': CraftTextarea;
  }
}
