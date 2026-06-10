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
        // Position relative to the viewport so the overlay escapes any
        // overflow-clipping / scrolling ancestor (e.g. a popover whose pane has
        // `overflow: auto`). Without this, a popover nested inside another
        // popover's scroll container gets clipped.
        strategy: 'fixed',
        placement: this.placement,
        modifiers: [
          {
            name: 'offset',
            options: {
              offset: [0, 4],
            },
          },
          {
            // Position with top/left instead of `transform`. A transformed
            // ancestor becomes the containing block for descendant
            // `position: fixed` overlays, which would re-trap a nested popover
            // inside this one's clipping pane. Using top/left keeps the viewport
            // as the containing block so nested overlays escape.
            name: 'computeStyles',
            options: {
              gpuAcceleration: false,
            },
          },
        ],
      },
    };
  }

  /**
   * The popover content lives in the shadow DOM so the component can provide
   * the scrollable `.content` wrapper (with body/footer slots). Lion's default
   * getter only looks for a light-DOM `[slot="content"]` child, so we point it
   * at the shadow node instead. Lion positions `#overlay-content-node-wrapper`
   * around it, mirroring how LionSelectRich handles shadow-DOM content.
   */
  protected override get _overlayContentNode(): HTMLElement {
    return this.shadowRoot?.querySelector('.popover-pane') as HTMLElement;
  }

  protected override render(): unknown {
    return html`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <div id="overlay-content-node-wrapper">
        <div class="popover-pane">
          <slot name="content">
            <slot name="content-body"></slot>
            <slot name="content-footer"></slot>
          </slot>
        </div>
      </div>
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
