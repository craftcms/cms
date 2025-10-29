import {property} from 'lit/decorators.js';
import {html, LitElement, nothing} from 'lit';
import styles from './status.styles.js';
import type {CSSResultGroup} from 'lit';
import {classMap} from 'lit/directives/class-map.js';

/**
 * @summary Short summary of the component's intended use.
 *
 * @event craft-event-name - Emitted as an example.
 *
 * @slot - The default slot.
 * @slot example - An example slot.
 *
 * @csspart base - The component's base wrapper.
 *
 * @cssproperty --example - An example CSS custom property.
 */
export default class CraftStatus extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** Accessible label for the status. */
  @property() label: string | null = null;

  /** The status of the indicator. */
  @property() status:
    | 'live'
    | 'pending'
    | 'expired'
    | 'disabled'
    | 'enabled'
    | null = null;

  getLabel() {
    if (!this.label && this.status) {
      return `Status: ${this.status}`;
    }

    return this.label;
  }

  override render() {
    return html`
      <span
        class="${classMap({
          status: true,
          'status--live': this.status === 'live',
          'status--enabled': this.status === 'enabled',
          'status--pending': this.status === 'pending',
          'status--expired': this.status === 'expired',
          'status--disabled': this.status === 'disabled',
        })}"
        role="img"
        aria-label="${this.getLabel()}"
      ></span>
    `;
  }
}
