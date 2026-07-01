import WaIcon from '@awesome.me/webawesome/dist/components/icon/icon.js';
import {css, html, nothing} from 'lit';
import {property, state} from 'lit/decorators.js';

/**
 * craft-icon is just an alias to wa-icon from web awesome.
 *
 * Anything you can do over there you can do here.
 *
 * In addition to the name/src-based rendering inherited from wa-icon, you may
 * pass an inline `<svg>` (or other element) into the default slot to render
 * your own icon. When the slot has assigned content, the slotted markup is
 * rendered and the fetched (name-based) icon is suppressed.
 */
export default class CraftIcon extends WaIcon {
  @property({reflect: true}) appearance?: 'plain' | 'badge' = 'plain';

  @state() private _hasSlottedContent = false;

  override connectedCallback() {
    super.connectedCallback();

    if (this.appearance === 'badge' && !this.getAttribute('data-color')) {
      this.setAttribute('data-color', 'warning');
    }
  }

  private _handleSlotChange(event: Event) {
    const slot = event.target as HTMLSlotElement;
    this._hasSlottedContent = slot.assignedElements({flatten: true}).length > 0;
  }

  override render() {
    return html`
      <slot @slotchange=${this._handleSlotChange}></slot>
      ${this._hasSlottedContent ? nothing : super.render()}
    `;
  }

  static override get styles() {
    return [
      WaIcon.styles,
      css`
        :host {
          font-size: 0.8em;
        }

        ::slotted(svg) {
          height: 1em;
          width: auto;
          overflow: visible;
        }

        :host([appearance~='badge']) {
          border: 1px solid var(--c-color-border-quiet);
          color: var(--c-color-on-quiet);
          background-color: var(--c-color-fill-quiet);
          border-radius: var(--c-radius-sm);
          width: 1.6em;
          height: 1.6em;

          svg {
            width: 0.9em;
          }
        }

        :host([appearance~='badge']) ::slotted(svg) {
          width: 0.9em;
        }
      `,
    ];
  }
}

// Alias to `craft-icon`
if (!customElements.get('craft-icon')) {
  customElements.define('craft-icon', CraftIcon);
}
