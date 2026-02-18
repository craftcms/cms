import styles from './option.styles.js';
import {LionOption} from '@lion/ui/listbox.js';
import {html, nothing} from 'lit';
import {property} from 'lit/decorators.js';

export default class CraftOption extends LionOption {
  static override get styles() {
    return [...LionOption.styles, styles];
  }

  @property()
  hint?: string | null = null;

  override render() {
    return html`
      <div class="choice-field__label">
        <slot></slot>
        ${this.hint ? html`<span class="hint">${this.hint}</span>` : nothing}
        <slot name="suffix"></slot>
      </div>
    `;
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
