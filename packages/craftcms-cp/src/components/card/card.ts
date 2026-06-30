import {property, state} from 'lit/decorators.js';
import type {CSSResultGroup} from 'lit';
import {html, LitElement, nothing} from 'lit';
import styles from './card.styles.js';
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
export default class CraftCard extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** Label for the card. */
  @property() label = '';

  @property({attribute: 'show-thumb'}) showThumb: boolean = true;

  @property({attribute: 'thumb-alignment'}) thumbAlignment: 'start' | 'end' =
    'start';

  /**
   * Whether the thumbnail slot currently has assigned content. Tracked as reactive
   * state and updated from the slot's `slotchange` event, since Lit doesn't
   * re-render on slotted light-DOM changes on its own — without this the card's
   * presence-derived rendering would go stale (e.g. when the CVD swaps the thumb).
   */
  @state() private _hasThumbnail = false;

  private _handleThumbnailSlotChange(event: Event) {
    const slot = event.target as HTMLSlotElement;
    this._hasThumbnail = slot.assignedElements({flatten: true}).length > 0;
  }

  override render() {
    const hasSlottedHeader =
      !!this.label ||
      !!this.querySelector('[slot="header"]') ||
      !!this.querySelector('[slot="label"]') ||
      !!this.querySelector('[slot="actions"]');
    const hasSlottedFooter = !!this.querySelector('[slot="footer"]');

    return html`
      <div
        class="${classMap({
          card: true,
          'card--has-thumbnail': this._hasThumbnail,
        })}"
      >
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

        <div
          class="${classMap({
            'card-body': true,
            'card-body--thumb-start':
              this._hasThumbnail && this.thumbAlignment === 'start',
            'card-body--thumb-end':
              this._hasThumbnail && this.thumbAlignment === 'end',
          })}"
        >
          <div class="card-body__thumb">
            <slot
              name="thumbnail"
              @slotchange="${this._handleThumbnailSlotChange}"
            ></slot>
          </div>

          <div class="card-body__main">
            <slot></slot>
          </div>
        </div>

        ${hasSlottedFooter
          ? html`<div class="card__footer"><slot name="footer"></slot></div>`
          : nothing}
      </div>
    `;
  }
}

if (!customElements.get('craft-card')) {
  customElements.define('craft-card', CraftCard);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-card': CraftCard;
  }
}
