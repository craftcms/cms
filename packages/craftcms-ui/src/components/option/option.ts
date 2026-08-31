import styles from './option.styles.js';
import {LionOption} from '@lion/ui/listbox.js';
import {html, nothing} from 'lit';
import {property} from 'lit/decorators.js';

/**
 * @summary One option inside `craft-select-rich` or `craft-combobox`. Unlike a
 * native `<option>` it can hold arbitrary markup — an icon, a status, a
 * thumbnail — alongside its label.
 *
 * Options widen their layout once they have room for it, measured against the
 * option rather than the viewport, so the same list reads sensibly in a narrow
 * slideout and a wide page.
 *
 * @slot - The option's label and any markup that goes with it.
 * @slot suffix - Trailing content, shown after the label and hint.
 *
 * @cssproperty --c-option-wide-threshold - Width, in pixels, past which the
 *   option switches to its wide layout. Defaults to `640`.
 */
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

  /**
   * Secondary text shown after the label — a handle, a count, a short
   * qualifier. Rendered quieter than the label rather than as part of it.
   */
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
