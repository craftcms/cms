import styles from './option.styles.js';
import {LionOption} from '@lion/ui/listbox.js';
import {html, nothing} from 'lit';
import {property} from 'lit/decorators.js';

export default class CraftOption extends LionOption {
  static override get styles() {
    return [...LionOption.styles, styles];
  }

  /**
   * One observer shared by every option. Sizes arrive after layout, so
   * toggling `wide` here never forces a synchronous reflow the way
   * measuring in connectedCallback did, and hidden options are re-evaluated
   * automatically once they become visible.
   */
  static #wideObserver = new ResizeObserver((entries) => {
    for (const entry of entries) {
      const option = entry.target as CraftOption;
      const width =
        entry.borderBoxSize?.[0]?.inlineSize ?? entry.contentRect.width;
      option.toggleAttribute('wide', width >= option.#wideThreshold());
    }
  });

  @property()
  hint?: string | null = null;

  #cachedWideThreshold?: number;

  #wideThreshold(): number {
    this.#cachedWideThreshold ??= parseInt(
      getComputedStyle(this).getPropertyValue('--c-option-wide-threshold') ||
        '640',
      10
    );

    return this.#cachedWideThreshold;
  }

  override connectedCallback() {
    super.connectedCallback();
    CraftOption.#wideObserver.observe(this);
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    CraftOption.#wideObserver.unobserve(this);
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
