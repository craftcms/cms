import {LionInput} from '@lion/ui/input.js';
import {inputStyles} from '@/styles/form.styles';
import styles from './input.styles.js';
import {property} from 'lit/decorators.js';

export default class CraftInput extends LionInput {
  static override get styles() {
    return [...super.styles, inputStyles, styles];
  }

  @property({type: Number, reflect: true}) size?: string;

  override connectedCallback() {
    super.connectedCallback();

    if (this._inputNode && this.size) {
      const sizeInt = parseInt(this.size, 10);
      if (sizeInt > 0) {
        this._inputNode.size = sizeInt;
      }
    }
  }
}

if (!customElements.get('craft-input')) {
  customElements.define('craft-input', CraftInput);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input': CraftInput;
  }
}
