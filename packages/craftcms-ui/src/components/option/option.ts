import styles from './option.styles.js';
import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {uniqueId} from '@src/utilities/unique-id';
import '../icon/icon.js';

/**
 * @summary One option inside `craft-select-rich` or `craft-combobox`. Unlike a
 * native `<option>` it can hold arbitrary markup — an icon, a status, a
 * thumbnail — alongside its label.
 *
 * A selected option ends in a check mark, so it's told apart from the
 * highlighted option by shape as well as fill, including in forced colors.
 *
 * Options widen their layout once they have room for it, measured against the
 * option rather than the viewport, so the same list reads sensibly in a narrow
 * slideout and a wide page.
 *
 * The listbox that holds the option decides what a click on it does; the
 * option only carries its state.
 *
 * @slot - The option's label and any markup that goes with it.
 * @slot suffix - Trailing content, shown after the label and hint.
 *
 * @fires craft-selected-change - `selected` changed. Bubbles, so the listbox
 *   around the option hears it.
 *
 * @cssproperty --c-option-wide-threshold - Width, in pixels, past which the
 *   option switches to its wide layout. Defaults to `640`.
 */
export default class CraftOption extends LitElement {
  static override styles = styles;

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

  /** The value the option selects. */
  @property()
  value = '';

  #selected = false;

  /** Whether the option is selected. */
  @property({type: Boolean, reflect: true})
  get selected(): boolean {
    return this.#selected;
  }

  set selected(selected: boolean) {
    const old = this.#selected;
    this.#selected = selected;
    this.requestUpdate('selected', old);

    if (old !== selected) {
      this.dispatchEvent(new Event('craft-selected-change', {bubbles: true}));
    }
  }

  /** Whether the option is the listbox's active descendant. */
  @property({type: Boolean, reflect: true})
  active = false;

  /** Whether the option can't be selected. */
  @property({type: Boolean, reflect: true})
  disabled = false;

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
    this.setAttribute('role', 'option');
    this.id ||= uniqueId('option');
    CraftOption.#wideObserver.observe(this);
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    CraftOption.#wideObserver.unobserve(this);
  }

  protected override willUpdate(changed: PropertyValues) {
    if (changed.has('selected')) {
      this.setAttribute('aria-selected', String(this.selected));
    }

    if (changed.has('disabled')) {
      this.setAttribute('aria-disabled', String(this.disabled));
    }
  }

  override render() {
    return html`
      <div class="choice-field__label">
        <span class="content"><slot></slot></span>
        ${this.hint ? html`<span class="hint">${this.hint}</span>` : nothing}
        <slot name="suffix"></slot>
      </div>
      ${this.selected
        ? html`<craft-icon class="check" name="check"></craft-icon>`
        : nothing}
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
