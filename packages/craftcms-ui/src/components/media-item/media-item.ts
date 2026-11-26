import type {CSSResultGroup} from 'lit';
import {html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import {
  hasSlotted,
  LightDomController,
} from '@src/controllers/LightDomController';
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
 * @slot suffix - Content at the item's end, such as an avatar or a button.
 *
 * @csspart base - The item's layout wrapper.
 * @csspart thumbnail - The 40px square holding the thumbnail or icon.
 * @csspart content - The wrapper around the heading and supporting text.
 * @csspart heading - The heading region.
 * @csspart body - The supporting text region.
 * @csspart suffix - The region at the item's end.
 *
 * @cssproperty [--c-media-item-thumbnail-size=calc(40rem / 16)] - The thumbnail square’s size.
 */
export default class CraftMediaItem extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** An icon drawn faintly in the thumbnail's place when none is slotted. */
  @property() icon?: string;

  /** Keeps the heading and supporting text to one line each, ending in an ellipsis. */
  @property({type: Boolean, reflect: true}) truncate = false;

  /**
   * Regions render only when they have something in them, so their slots
   * can't report their own changes. The controller re-renders the item
   * whenever its light DOM changes.
   */
  private _lightDom = new LightDomController(this, {characterData: true});

  /** Whether the default slot has an element or non-blank text in it. */
  private _hasBody(): boolean {
    return (
      hasSlotted(this, '') ||
      [...this.childNodes].some(
        (node) => node.nodeType === Node.TEXT_NODE && !!node.textContent?.trim()
      )
    );
  }

  private _renderThumbnail() {
    if (hasSlotted(this, 'thumbnail')) {
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
        <div class="media-item__content" part="content">
          ${
            hasSlotted(this, 'heading')
              ? html`<div class="media-item__heading" part="heading">
                  <slot name="heading"></slot>
                </div>`
              : nothing
          }
          ${
            this._hasBody()
              ? html`<div class="media-item__body" part="body">
                  <slot></slot>
                </div>`
              : nothing
          }
        </div>
        ${
          hasSlotted(this, 'suffix')
            ? html`<div class="media-item__suffix" part="suffix">
                <slot name="suffix"></slot>
              </div>`
            : nothing
        }
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
