import {property, state} from 'lit/decorators.js';
import type {CSSResultGroup} from 'lit';
import {html, LitElement, nothing} from 'lit';
import styles from './card.styles.js';
import {classMap} from 'lit/directives/class-map.js';

/**
 * @summary A surface that groups related content into a bordered, rounded
 * container with optional header and footer regions. Used for element cards in
 * index views and other standalone content blocks.
 *
 * The header region is only rendered when the `label` attribute is set or the
 * `header`, `label`, or `actions` slot is filled. When no `header` slot is
 * provided, the header falls back to showing the `label` and `actions` slots
 * (with the `label` attribute as the default label content). The footer region
 * is only rendered when the `footer` slot is filled.
 *
 * Host attributes are applied directly to the element, so the server-rendered
 * wrapper attributes that accompany an element's card HTML (its `id`, `style`
 * custom properties, and `data-*` metadata) can be spread onto it with the
 * `attrs()` utility — e.g. `v-bind="attrs(element.cardAttributes)"`. `class` is
 * usually excluded from that bind (`{exclude: ['class']}`), since the component
 * renders its own card chrome rather than the server's `.card` classes.
 *
 * @slot - The card's body content.
 * @slot header - The full header region. Replaces the default
 *   `label`/`actions` content when provided.
 * @slot label - Label content shown in the header; defaults to the `label`
 *   attribute.
 * @slot actions - Action content shown at the end of the header, e.g. buttons.
 * @slot footer - Footer content. The footer is only rendered when this slot is
 *   filled.
 *
 * @csspart label - The label slot within the header.
 *
 * @cssproperty --c-card-radius - Corner radius. Defaults to `--c-radius-md`.
 * @cssproperty --c-card-shadow - Box shadow. Defaults to `--c-shadow-sm`.
 * @cssproperty --c-card-padding-inline - Inline (horizontal) padding of the
 *   header, body, and footer. Defaults to `--c-spacing-md`.
 * @cssproperty --c-card-padding-block - Block (vertical) padding of the header,
 *   body, and footer. Defaults to `--c-spacing-sm` for the header/footer and
 *   `--c-spacing-md` for the body.
 */
export default class CraftCard extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** Label shown in the header when the `label` slot is not filled. */
  @property() label = '';

  /**
   * Whether the card is in an active/selected state. Reflected to the host as
   * an `active` attribute (e.g. ElementCards binds this to row selection).
   */
  @property({type: Boolean, reflect: true})
  active = false;

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
