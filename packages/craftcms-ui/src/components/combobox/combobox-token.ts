import styles from './combobox-token.styles.js';
import {html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import {t} from '@src/utilities/translate';

/**
 * @summary A selected value in a `multiple` `craft-combobox`, shown
 * before the textbox. A removable token is a button that removes it.
 *
 * The button is named by its label and described by a hidden "Remove", so
 * the textbox, which is named after the list of tokens, takes each token's
 * label without "Remove", and voice control users can say the label they see.
 *
 * @slot - The token's label, and any icon or dot before it.
 *
 * @event craft-remove - The token's button was activated.
 */
export default class CraftComboboxToken extends LitElement {
  static override styles = styles;

  /** Whether the token is a button that removes it. */
  @property({type: Boolean, reflect: true})
  removable = false;

  override render() {
    return this.removable
      ? html`<button
            type="button"
            aria-describedby="remove-hint"
            @click=${this.#remove}
          >
            <slot></slot>
            <span class="remove" aria-hidden="true">×</span>
          </button>
          <span id="remove-hint" hidden>${t('Remove')}</span>`
      : html`<slot></slot>`;
  }

  #remove = () => {
    this.dispatchEvent(new CustomEvent('craft-remove', {bubbles: true}));
  };
}

if (!customElements.get('craft-combobox-token')) {
  customElements.define('craft-combobox-token', CraftComboboxToken);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-combobox-token': CraftComboboxToken;
  }
}
