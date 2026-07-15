import {html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './badge-indicator.styles.js';
import {classMap} from 'lit/directives/class-map.js';
import '@shoelace-style/shoelace/dist/components/visually-hidden/visually-hidden.js';

/**
 * @summary A badge indicator component. Used in various places to indicate that
 * something is new or has been updated. The indicator can have an optional
 * notification count.
 */

export default class CraftBadgeIndicator extends LitElement {
  static override styles = [styles];

  /** Alternative text if the badge is not decorative */
  @property({attribute: 'alt-text'}) altText: string | null = null;

  /** Displays a number on the badge. If using `badgeCount`, the `badgeCountSuffix` should also be used to describe what the number represents. */
  @property({attribute: 'badge-count'}) badgeCount: number | null = null;

  /** Visually hidden text that comes after the count to provide additional context. */
  @property({attribute: 'badge-count-suffix'}) badgeCountSuffix: string | null =
    null;

  /** Theme variant of the badge indicator. Defaults to "primary" */
  @property() variant: 'primary' | 'secondary' | 'inverse' = 'primary';

  @property()
  override id: string;

  constructor() {
    super();
    this.id =
      this.id || `badge-${Math.floor(Math.random() * 1000000000).toString()}`;
  }

  private showCount() {
    return this.badgeCount !== null && this.badgeCount > 0;
  }

  private truncatedNumber() {
    if (!this.showCount) return;

    // @ts-expect-error we're already checking that badgeCount is not null in showCount
    if (this.badgeCount > 99) {
      return '99+';
    } else {
      // @ts-expect-error we're already checking that badgeCount is not null in showCount
      return this.badgeCount.toString();
    }
  }

  private getBadgeRole() {
    return this.altText ? 'img' : nothing;
  }

  private getLabelId() {
    return `${this.id}-label`;
  }

  private renderBadgeContents() {
    return html`
      ${this.showCount()
        ? html`
            <span class="number">${this.truncatedNumber()}</span>
            <sl-visually-hidden>${this.badgeCountSuffix}</sl-visually-hidden>
          `
        : nothing}
      ${this.altText
        ? html`
            <sl-visually-hidden id=${this.getLabelId()}
              >${this.altText}</sl-visually-hidden
            >
          `
        : nothing}
    `;
  }

  override render() {
    return html`
      <div
        part="badge"
        id=${this.id}
        class="${classMap({
          'badge-indicator': true,
          'badge-indicator--with-number': this.showCount(),
          'badge-indicator--secondary': this.variant === 'secondary',
          'badge-indicator--inverse': this.variant === 'inverse',
        })}"
        role="${this.getBadgeRole()}"
        aria-labelledby="${this.altText ? this.getLabelId() : nothing}"
      >
        ${this.renderBadgeContents()}
      </div>
    `;
  }
}

if (!customElements.get('craft-badge-indicator')) {
  customElements.define('craft-badge-indicator', CraftBadgeIndicator);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-badge-indicator': CraftBadgeIndicator;
  }
}
