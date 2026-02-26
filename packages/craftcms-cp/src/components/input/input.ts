import {LionInput} from '@lion/ui/input.js';
import {inputStyles} from '@src/styles/form.styles';
import styles from './input.styles.js';
import {property} from 'lit/decorators.js';

export default class CraftInput extends LionInput {
  static override get styles() {
    return [...super.styles, inputStyles, styles];
  }

  @property({type: Number, reflect: true}) maxlength?: string;
  @property({type: String, reflect: true}) size?: 'small' | 'medium' | 'large' =
    'medium';
  @property({reflect: true, type: Boolean}) small = false;
  @property({reflect: true, type: Boolean}) center = false;

  override connectedCallback() {
    super.connectedCallback();

    if (this._inputNode && this.maxlength) {
      const sizeInt = parseInt(this.maxlength, 10);
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
