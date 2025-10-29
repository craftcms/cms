import {property} from 'lit/decorators.js';
import {html, LitElement, nothing} from 'lit';
import styles from './card.styles.js';
import type {CSSResultGroup} from 'lit';

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
export default class CraftCard extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** Label for the card. */
  @property() label = '';

  override render() {
    const hasSlottedHeader =
      !!this.label ||
      !!this.querySelector('[slot="header"]') ||
      !!this.querySelector('[slot="label"]') ||
      !!this.querySelector('[slot="actions"]');
    const hasSlottedFooter = !!this.querySelector('[slot="footer"]');

    return html`
      <div class="card">
        <div>
          ${hasSlottedHeader
            ? html`<div class="card__header">
                <slot name="header">
                  <slot name="label" class="card__label" part="label"
                    >${this.label}</slot
                  >
                  <slot name="actions"></slot>
                </slot>
              </div>`
            : nothing}

          <div class="card__body">
            <slot></slot>
          </div>

          ${hasSlottedFooter
            ? html`<div class="card__footer"><slot name="footer"></slot></div>`
            : nothing}
        </div>
      </div>
    `;
  }
}
