import {html, LitElement} from 'lit';
import {property, query, state} from 'lit/decorators.js';

import '../tooltip/tooltip';
import styles from './truncate.styles.js';

/**
 * craft-truncate renders its content on a single line, clipping it with an
 * ellipsis when it doesn't fit, and revealing the full text in a tooltip on
 * hover/focus. When the content fits, no tooltip is rendered.
 *
 * The element truncates against its own width, so give it a bounded width via
 * the surrounding layout (e.g. a fixed width, or `flex: 1; min-width: 0` in a
 * flex row).
 *
 * @example <craft-truncate>A label that might be too long to fit</craft-truncate>
 *
 * @slot - The content to truncate. Its text is used for the tooltip.
 */
export default class CraftTruncate extends LitElement {
  static override styles = styles;

  /** Popper placement for the overflow tooltip, e.g. `top`, `right-start`. */
  @property({reflect: true}) placement = 'top';

  /** Disables the overflow tooltip. The content still truncates visually. */
  @property({type: Boolean, reflect: true}) disabled = false;

  /** Whether the content currently overflows its container. */
  @state() private overflowing = false;

  /** The full, untruncated text shown in the tooltip. */
  @state() private text = '';

  @query('.truncate') private content?: HTMLElement;

  #resizeObserver =
    typeof ResizeObserver !== 'undefined'
      ? new ResizeObserver(() => this.#measure())
      : null;

  override connectedCallback() {
    super.connectedCallback();
    // Observing fires an initial callback once the element has been laid out,
    // which seeds the first measurement.
    this.#resizeObserver?.observe(this);
  }

  override disconnectedCallback() {
    this.#resizeObserver?.disconnect();
    super.disconnectedCallback();
  }

  protected override firstUpdated() {
    this.#measure();
  }

  #measure() {
    this.text = (this.textContent ?? '').replace(/\s+/g, ' ').trim();

    const el = this.content;
    this.overflowing = !!el && el.scrollWidth > el.clientWidth;
  }

  #onSlotChange() {
    this.#measure();
  }

  override render() {
    const showTooltip = this.overflowing && !this.disabled;

    return html`
      <span class="truncate" id="content">
        <slot @slotchange=${this.#onSlotChange}></slot>
      </span>
      ${showTooltip
        ? html`<craft-tooltip for="content" placement=${this.placement}
            >${this.text}</craft-tooltip
          >`
        : ''}
    `;
  }
}

if (!customElements.get('craft-truncate')) {
  customElements.define('craft-truncate', CraftTruncate);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-truncate': CraftTruncate;
  }
}
