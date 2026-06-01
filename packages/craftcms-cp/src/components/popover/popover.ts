import {html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import {OverlayMixin, withDropdownConfig} from '@lion/ui/overlays.js';
import styles from './popover.styles.js';

/**
 * A non-modal popover component built on Lion's overlay system.
 *
 * Overlays content on top of the page without affecting document flow.
 * Uses `aria-haspopup` on the invoker, dismisses on Escape and outside click.
 *
 * @slot invoker - The element that triggers the popover (e.g. a button).
 * @slot content - The popup content shown when opened.
 *
 * @example
 * ```html
 * <craft-popover>
 *   <button slot="invoker">Open</button>
 *   <div slot="content">Popover content here</div>
 * </craft-popover>
 * ```
 */
export default class CraftPopover extends OverlayMixin(LitElement) {
  static override styles = [styles];

  /** Popper.js placement for the overlay content. */
  @property({reflect: true}) placement:
    | 'top'
    | 'top-start'
    | 'top-end'
    | 'bottom'
    | 'bottom-start'
    | 'bottom-end'
    | 'left'
    | 'left-start'
    | 'left-end'
    | 'right'
    | 'right-start'
    | 'right-end' = 'bottom-start';

  /** Whether the overlay should match the invoker's width. */
  @property({attribute: 'match-invoker-width', type: Boolean})
  matchInvokerWidth = false;

  // @ts-ignore – Lion expects this to return an OverlayConfig
  _defineOverlayConfig() {
    return {
      ...withDropdownConfig(),
      inheritsReferenceWidth: this.matchInvokerWidth ? 'min' : 'none',
      popperConfig: {
        placement: this.placement,
        modifiers: [
          {
            name: 'offset',
            options: {
              offset: [0, 4],
            },
          },
        ],
      },
    };
  }

  protected override render(): unknown {
    return html`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
    `;
  }
}

if (!customElements.get('craft-popover')) {
  customElements.define('craft-popover', CraftPopover);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-popover': CraftPopover;
  }
}
