import {property} from 'lit/decorators.js';
import {html, LitElement, nothing} from 'lit';
import styles from './status.styles.js';
import type {CSSResultGroup} from 'lit';
import {classMap} from 'lit/directives/class-map.js';
import {t} from '@src/utilities/translate';

/**
 * @summary A coloured dot standing for an object's state — an entry that is
 * live, a user that is disabled, a draft that is pending.
 *
 * The dot carries no text. It is meant to sit beside the thing it describes,
 * in an index row or a chip, where the name is already present.
 *
 * Reach for `craft-indicator` instead when the dot means something the status
 * vocabulary does not cover: indicator takes any palette colour, where this
 * takes a fixed set of states.
 */
export default class CraftStatus extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** Accessible label for the status. */
  @property() label: string | null = null;

  /**
   * The state the dot stands for. Each renders in its own colour; leaving it
   * unset renders the neutral dot.
   */
  @property() status:
    | 'live'
    | 'pending'
    | 'expired'
    | 'disabled'
    | 'enabled'
    | null = null;

  protected getLabel() {
    if (!this.label && this.status) {
      return t('Status: {status}', {status: this.status});
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
        role="${this.getLabel() ? 'img' : nothing}"
        aria-label="${this.getLabel() ?? nothing}"
      ></span>
    `;
  }
}

if (!customElements.get('craft-status')) {
  customElements.define('craft-status', CraftStatus);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-status': CraftStatus;
  }
}
