import {property} from 'lit/decorators.js';
import type {CSSResultGroup} from 'lit';
import {html, LitElement, nothing} from 'lit';
import styles from './card.styles.js';
import {classMap} from 'lit/directives/class-map.js';
import {
  hasSlotted,
  LightDomController,
} from '@src/controllers/LightDomController';

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
 * The card renders its own chrome, so attributes set on the host are left
 * alone — an `id`, `style` custom properties, and `data-*` metadata can all be
 * spread onto it without the component interfering.
 *
 * @slot - The card's body content.
 * @slot header - The full header region. Replaces the default
 *   `label`/`actions` content when provided.
 * @slot label - Label content shown in the header; defaults to the `label`
 *   attribute.
 * @slot actions - Action content shown at the end of the header, e.g. buttons.
 * @slot footer - Footer content. The footer is only rendered when this slot is
 *   filled.
 * @slot thumbnail - Artwork shown in a fixed column beside the body. Rendered
 *   only while `show-thumb` is set; `thumb-alignment` puts the column at the
 *   start or the end of the body.
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
  // In the CP, an element's server-rendered card attributes are spread onto
  // the host with `attrs(element.cardAttributes)`, excluding `class` — the
  // component draws its own chrome rather than the server's `.card` classes.
  static override styles: CSSResultGroup = [styles];

  /** Label shown in the header when the `label` slot is not filled. */
  @property() label = '';

  /**
   * Whether the card is in an active/selected state. Reflected to the host as
   * an `active` attribute (e.g. ElementCards binds this to row selection).
   */
  @property({type: Boolean, reflect: true})
  active = false;

  /** Whether the thumbnail region renders at all, even with slotted content. */
  @property({attribute: 'show-thumb', type: Boolean}) showThumb: boolean = true;

  /**
   * Which side of the body the thumbnail column sits on. Has no effect unless
   * the `thumbnail` slot is filled and `show-thumb` is set.
   */
  @property({attribute: 'thumb-alignment'}) thumbAlignment: 'start' | 'end' =
    'start';

  /**
   * The header, footer, and thumbnail regions only render when they have
   * something in them, so their slots can't report their own changes — an
   * unrendered slot never fires `slotchange`. The controller re-renders the
   * card whenever its light DOM moves, which is how a thumbnail swapped in
   * by the element index shows up.
   */
  private _lightDom = new LightDomController(this);

  override render() {
    const hasSlottedHeader =
      !!this.label || hasSlotted(this, 'header', 'label', 'actions');
    const hasSlottedFooter = hasSlotted(this, 'footer');
    const showThumbnail = this.showThumb && hasSlotted(this, 'thumbnail');

    return html`
      <div
        class="${classMap({
          card: true,
          'card--has-thumbnail': showThumbnail,
        })}"
      >
        ${hasSlottedHeader
          ? html`<div class="card__header">
              <slot name="header">
                <slot name="label" class="card__label" part="label"
                  >${this.label}</slot
                >
                <slot name="actions" class="card__actions"></slot>
              </slot>
            </div>`
          : nothing}

        <div
          class="${classMap({
            'card-body': true,
            'card-body--thumb-start':
              showThumbnail && this.thumbAlignment === 'start',
            'card-body--thumb-end':
              showThumbnail && this.thumbAlignment === 'end',
          })}"
        >
          <div class="card-body__thumb" ?hidden="${!showThumbnail}">
            <slot name="thumbnail"></slot>
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
