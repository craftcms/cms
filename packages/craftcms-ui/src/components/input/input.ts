import {LionInput} from '@lion/ui/input.js';
import {inputStyles} from '@src/styles/form.styles';
import styles from './input.styles.js';
import {property} from 'lit/decorators.js';

export default class CraftInput extends LionInput {
  static override get styles() {
    return [...super.styles, inputStyles, styles];
  }

  /** Maximum number of characters, applied to the native input's `maxLength`. */
  @property({type: Number, reflect: true}) maxlength?: number;

  /** Control size. */
  @property({type: String, reflect: true}) size?: 'small' | 'medium' | 'large' =
    'medium';

  /** Renders the input at a smaller size. */
  @property({reflect: true, type: Boolean}) small = false;

  /**
   * Overrides the inferred width behavior. By default the control spans its
   * column, unless `maxlength` is set — which shrinks the control to the
   * expected character width. `full` spans the column despite a `maxlength`;
   * `auto` shrinks to the input's intrinsic width without one.
   */
  @property({type: String, reflect: true}) width?: 'full' | 'auto';

  /** Center-aligns the input text. */
  @property({reflect: true, type: Boolean}) center = false;

  /** Renders the input value in a monospace font. */
  @property({reflect: true, type: Boolean}) monospace = false;

  /**
   * Visually hides the control while keeping it in the DOM and form-bound, so
   * its value still submits. Useful for fields that are populated/managed
   * programmatically but must round-trip with the form.
   */
  @property({reflect: true, type: Boolean, attribute: 'hidden-input'})
  hiddenInput = false;

  override connectedCallback() {
    super.connectedCallback();

    if (this._inputNode && this.maxlength && this.maxlength > 0) {
      this._inputNode.maxLength = this.maxlength;
      // Give the input a matching intrinsic width, so the shrunk control
      // (see `width`) fits the expected character count.
      this._inputNode.size = this.maxlength;
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
