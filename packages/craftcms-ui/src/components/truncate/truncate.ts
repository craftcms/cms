import {html, LitElement} from 'lit';
import {property, query, state} from 'lit/decorators.js';

import {cancelIdle, whenIdle} from '@src/utilities/idle';
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
 *
 * @cssproperty --c-truncate-link-decoration - `text-decoration` for a link
 *   slotted into the truncate. Unset by default, leaving the link's own.
 *   `craft-chip` sets it to `none`.
 * @cssproperty --c-truncate-link-hover-decoration - The same, while the link
 *   is hovered. `craft-chip` sets it to `underline`.
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

  /** A measurement waiting for the browser to be idle. */
  #pendingMeasure: number | undefined;

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
    if (this.#pendingMeasure !== undefined) {
      cancelIdle(this.#pendingMeasure);
      this.#pendingMeasure = undefined;
    }
    super.disconnectedCallback();
  }

  protected override firstUpdated() {
    // `observe()` delivers an initial callback once the element is laid out,
    // which is the first measurement. Measuring here as well would set state
    // straight after an update completed — the double render Lit warns about.
    // Without a ResizeObserver there is nothing else to seed it.
    if (!this.#resizeObserver) {
      this.#measure();
    }
  }

  #measure() {
    this.text = (this.textContent ?? '').replace(/\s+/g, ' ').trim();

    const el = this.content;
    this.overflowing = !!el && el.scrollWidth > el.clientWidth;
  }

  /**
   * Re-measures once the browser is idle. A slot changes as content renders,
   * before it's laid out, so measuring then would lay out the page early, and
   * the overflow only decides whether there's a tooltip to hover.
   */
  #onSlotChange() {
    if (this.#pendingMeasure !== undefined) {
      return;
    }

    this.#pendingMeasure = whenIdle(() => {
      this.#pendingMeasure = undefined;
      this.#measure();
    });
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
