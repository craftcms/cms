import {css, html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';

/**
 * @summary Hides its content visually while leaving it available to screen
 * readers. Use it for text that names something the design conveys another
 * way — an icon-only control, a table column whose header is implied by
 * position, a live region reporting progress.
 *
 * Hidden is not the same as removed: the content stays in the accessibility
 * tree and in the document order, which is the whole point.
 *
 * @slot - The content to hide visually.
 */
export default class CraftVisuallyHidden extends LitElement {
  static override styles = css`
    :host(:not([debug])) {
      position: absolute;
      width: 1px;
      height: 1px;
      overflow: hidden;
      clip: rect(0 0 0 0);
      clip-path: inset(50%);
      white-space: nowrap;
    }
  `;

  /**
   * Reveals the content, for checking what a screen reader would announce
   * without reaching for one.
   */
  @property({type: Boolean, reflect: true}) debug = false;

  protected override render(): unknown {
    return html`<slot></slot>`;
  }
}

if (!customElements.get('craft-visually-hidden')) {
  customElements.define('craft-visually-hidden', CraftVisuallyHidden);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-visually-hidden': CraftVisuallyHidden;
  }
}
