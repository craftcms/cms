import styles from './textarea.styles.js';
import {inputStyles} from '../../styles/form.styles.js';
import {LionTextarea} from '@lion/ui/textarea.js';
import {property} from 'lit/decorators.js';

/**
 * @summary A multi-line text input, with the same label, help text, and
 * validation as `craft-input`.
 *
 * Use it where the value is genuinely prose — a description, a note. A
 * textarea for something short trains people to expect more room than the
 * value needs.
 *
 * @slot label - The field's label, as an alternative to the `label` attribute.
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages.
 */
export default class CraftTextarea extends LionTextarea {
  static override get styles() {
    return [...super.styles, inputStyles, styles];
  }

  /** Renders the value in a monospace font, for code or structured text. */
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
