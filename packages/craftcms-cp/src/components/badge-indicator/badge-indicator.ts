import {html, css, LitElement} from 'lit';
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

  /** Type of item being indicated (for screen reader users). Should take singular/plural into account. */
  @property() itemType: string | null = null;

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
    return html`
      <div class="${classMap({
        'badge-indicator': true,
        'badge-indicator--with-number': this.number !== null,
      })}">
        ${this.truncatedNumber()}
        <sl-visually-hidden> ${this.itemType}</sl-visually-hidden>
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
