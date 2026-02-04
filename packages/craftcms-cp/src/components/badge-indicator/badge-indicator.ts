import {html, css, LitElement, nothing} from 'lit';
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

  /** Number of notifications */
  @property() number: number | null = null;

  /** Accessible text for screen reader users */
  @property() srText: string | null = null;

  /** Theme variant of the badge indicator. Defaults to "primary" */
  @property() variant: 'primary' | 'secondary' = 'primary';

  @property()
  override id: string;

  constructor() {
    super();
    this.id = this.id || Math.floor(Math.random() * 1000000000).toString();
  }

  private truncatedNumber() {
    if (!this.number) {
      return null;
    }

    if (this.number > 99) {
      return '99+';
    } else {
      return this.number.toString();
    }
  }
  override render() {
    const hasNumber = this.number !== null;
    const badgeId = this.id ?? nothing;
    const labelId = badgeId ? `${badgeId}-label` : nothing;

    return html`
      <div 
        id=${badgeId}
        class="${classMap({
          'secondary': this.variant === 'secondary',
          'badge-indicator': true,
          'badge-indicator--with-number': this.number !== null,
        })}"
        role="${!hasNumber ? 'img' : nothing }"
        aria-labelledby="${!hasNumber ? labelId : nothing }"
      >
        ${hasNumber
          ? html`<span class="number">${this.truncatedNumber()}</span>`
          : nothing}
        <sl-visually-hidden id=${labelId}> ${this.srText}</sl-visually-hidden>
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
