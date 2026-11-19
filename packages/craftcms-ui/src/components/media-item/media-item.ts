import type {CSSResultGroup, PropertyValues} from 'lit';
import {html, LitElement, nothing} from 'lit';
import {property, state} from 'lit/decorators.js';
import '../icon/icon.js';
import styles from './media-item.styles.js';

/**
 * @summary A thumbnail beside a heading and supporting text, for lists of
 * entries, notifications and the like.
 *
 * The host is intentionally non-semantic. Place it within the appropriate
 * semantic element, such as an `<li>` in a list, or an `<article>` for an item
 * that stands on its own.
 *
 * @slot - Supporting text below the heading, drawn smaller and quieter.
 * @slot thumbnail - An image or thumbnail, cropped to a 40px square. Without
 * one, `icon` is drawn in its place.
 * @slot heading - The item's name, such as a link to it.
 * @slot aside - Content at the item's end, such as an avatar or a button.
 *
 * @csspart base - The item's layout wrapper.
 * @csspart thumbnail - The 40px square holding the thumbnail or icon.
 * @csspart text - The wrapper around the heading and supporting text.
 * @csspart heading - The heading region.
 * @csspart details - The supporting text region.
 * @csspart aside - The region at the item's end.
 *
 * @cssproperty [--c-media-item-thumbnail-size=calc(40rem / 16)] - The thumbnail square’s size.
 */
export default class CraftMediaItem extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** An icon drawn faintly in the thumbnail's place when none is slotted. */
  @property() icon?: string;

  /** Keeps the heading and supporting text to one line each, ending in an ellipsis. */
  @property({type: Boolean, reflect: true}) truncate = false;

  @state() private _hasThumbnail = false;

  @state() private _hasHeading = false;

  @state() private _hasDetails = false;

  @state() private _hasAside = false;

  private _lightDomObserver = new MutationObserver(() =>
    this._syncSlotPresence()
  );

  override connectedCallback() {
    super.connectedCallback();
    this._syncSlotPresence();
    this._lightDomObserver.observe(this, {
      childList: true,
      characterData: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['slot'],
    });
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this._lightDomObserver.disconnect();
  }

  protected override willUpdate(changed: PropertyValues<this>) {
    super.willUpdate(changed);
    if (!this.hasUpdated) {
      this._syncSlotPresence();
    }
  }

  private _syncSlotPresence() {
    this._hasThumbnail = !!this.querySelector(':scope > [slot="thumbnail"]');
    this._hasHeading = !!this.querySelector(':scope > [slot="heading"]');
    this._hasAside = !!this.querySelector(':scope > [slot="aside"]');
    this._hasDetails = [...this.childNodes].some((node) => {
      if (node.nodeType === Node.TEXT_NODE) {
        return !!node.textContent?.trim();
      }

      return node instanceof HTMLElement && !node.slot;
    });
  }

  private _renderThumbnail() {
    if (this._hasThumbnail) {
      return html`<div class="media-item__thumbnail" part="thumbnail">
        <slot name="thumbnail"></slot>
      </div>`;
    }

    if (this.icon) {
      return html`<div
        class="media-item__thumbnail media-item__thumbnail--icon"
        part="thumbnail"
      >
        <craft-icon name="${this.icon}"></craft-icon>
      </div>`;
    }

    return nothing;
  }

  override render() {
    return html`
      <div class="media-item" part="base">
        ${this._renderThumbnail()}
        <div class="media-item__text" part="text">
          ${this._hasHeading
            ? html`<div class="media-item__heading" part="heading">
                <slot name="heading"></slot>
              </div>`
            : nothing}
          ${this._hasDetails
            ? html`<div class="media-item__details" part="details">
                <slot></slot>
              </div>`
            : nothing}
        </div>
        ${this._hasAside
          ? html`<div class="media-item__aside" part="aside">
              <slot name="aside"></slot>
            </div>`
          : nothing}
      </div>
    `;
  }
}

if (!customElements.get('craft-media-item')) {
  customElements.define('craft-media-item', CraftMediaItem);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-media-item': CraftMediaItem;
  }
}
