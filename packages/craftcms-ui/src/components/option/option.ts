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

  #wideThreshold: number = 640;

  constructor() {
    super();
    this.#wideThreshold = parseInt(
      getComputedStyle(this).getPropertyValue('--c-option-wide-threshold') ||
        '640',
      10
    );
  }

  override connectedCallback() {
    super.connectedCallback();

    const width = this.getBoundingClientRect().width ?? 0;
    this.toggleAttribute('wide', width >= this.#wideThreshold);
  }

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
