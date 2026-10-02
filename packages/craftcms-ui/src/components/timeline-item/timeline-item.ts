import type {CSSResultGroup} from 'lit';
import {html, LitElement, nothing} from 'lit';
import {property, state} from 'lit/decorators.js';
import styles from './timeline-item.styles.js';

/**
 * @summary Shared timeline layout for activity streams and ordered processes.
 *
 * The host is intentionally non-semantic. Place it within the appropriate
 * semantic element, such as an `<article>` for an activity event or an `<li>`
 * within an ordered process.
 *
 * @slot - The timeline item's body content.
 * @slot marker - The icon or other marker displayed on the timeline.
 * @slot heading - The timeline item's heading.
 * @slot meta - Supporting metadata displayed after the heading.
 * @slot footer - Supporting content displayed below the body.
 *
 * @csspart base - The timeline item's layout wrapper.
 * @csspart marker - The circular marker wrapper.
 * @csspart content - The content wrapper.
 * @csspart header - The header region.
 * @csspart heading - The heading region.
 * @csspart meta - The metadata region.
 * @csspart body - The body region.
 * @csspart footer - The footer region.
 */
export default class CraftTimelineItem extends LitElement {
  static override styles: CSSResultGroup = [styles];

  /** Whether this is the final item, which hides the continuation line. */
  @property({type: Boolean, reflect: true}) last = false;

  @state() private _hasHeading = false;

  @state() private _hasMeta = false;

  @state() private _hasBody = false;

  @state() private _hasFooter = false;

  private _lightDomObserver = new MutationObserver(() =>
    this._syncSlotPresence()
  );

  override connectedCallback() {
    super.connectedCallback();
    this._syncSlotPresence();
    this._lightDomObserver.observe(this, {
      childList: true,
      subtree: true,
      attributes: true,
      characterData: true,
      attributeFilter: ['slot'],
    });
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this._lightDomObserver.disconnect();
  }

  private _syncSlotPresence() {
    this._hasHeading = !!this.querySelector(':scope > [slot="heading"]');
    this._hasMeta = !!this.querySelector(':scope > [slot="meta"]');
    this._hasBody = [...this.childNodes].some((node) => {
      if (node.nodeType === Node.TEXT_NODE) {
        return !!node.textContent?.trim();
      }

      return node instanceof HTMLElement && !node.slot;
    });
    this._hasFooter = !!this.querySelector(':scope > [slot="footer"]');
  }

  override render() {
    return html`
      <div class="timeline-item" part="base">
        <span class="timeline-item__marker" part="marker" aria-hidden="true">
          <slot name="marker"></slot>
        </span>
        <div class="timeline-item__content" part="content">
          ${this._hasHeading || this._hasMeta
            ? html`<div class="timeline-item__header" part="header">
                ${this._hasHeading
                  ? html`<div class="timeline-item__heading" part="heading">
                      <slot name="heading"></slot>
                    </div>`
                  : nothing}
                ${this._hasMeta
                  ? html`<div class="timeline-item__meta" part="meta">
                      ${this._hasHeading
                        ? html`<span
                            class="timeline-item__separator"
                            aria-hidden="true"
                            >•</span
                          >`
                        : nothing}
                      <slot name="meta"></slot>
                    </div>`
                  : nothing}
              </div>`
            : nothing}
          ${this._hasBody
            ? html`<div class="timeline-item__body" part="body">
                <slot></slot>
              </div>`
            : nothing}
          ${this._hasFooter
            ? html`<div class="timeline-item__footer" part="footer">
                <slot name="footer"></slot>
              </div>`
            : nothing}
        </div>
      </div>
    `;
  }
}

if (!customElements.get('craft-timeline-item')) {
  customElements.define('craft-timeline-item', CraftTimelineItem);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-timeline-item': CraftTimelineItem;
  }
}
