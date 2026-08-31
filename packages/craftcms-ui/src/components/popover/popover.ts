import {html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {OverlayMixin, withDropdownConfig} from '@lion/ui/overlays.js';
import type {VirtualElement} from '@popperjs/core';
import {wireOverlayLifecycleEvents} from '@src/utilities/overlay-events.js';
import {viewportEscapingModifiers} from '@src/utilities/overlay-position.js';
import styles from './popover.styles.js';

/**
 * @summary A non-modal popover built on Lion's overlay system.
 *
 * Overlays content on top of the page without affecting document flow.
 * The trigger can be a slotted `invoker` child, an external element
 * referenced by id (`for`), or passed directly (`anchor`). Clicking it
 * toggles the popover; Escape and outside clicks dismiss it.
 *
 * Emits `craft-show`/`craft-after-show`/`craft-hide`/`craft-after-hide`.
 *
 * @slot invoker - The element that triggers the popover (e.g. a button).
 * @slot content - The popup content shown when opened. Default-slot children
 *   are wrapped into a generated `slot="content"` element on connect.
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

  /** Id of the trigger element within the same document/shadow root. */
  @property({reflect: true}) for?: string;

  /** Explicit element or virtual positioning anchor; takes precedence over `for`. */
  @property({attribute: false}) anchor?: HTMLElement | VirtualElement;

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

  /** Distance in pixels between the popover and its anchor. */
  @property({type: Number}) distance = 4;

  /** Whether the overlay should match the invoker's width. */
  @property({attribute: 'match-invoker-width', type: Boolean})
  matchInvokerWidth = false;

  /** Accepted for API compatibility; craft-popover never renders an arrow. */
  @property({type: Boolean, attribute: 'without-arrow'}) withoutArrow = false;

  #contentWrapper: HTMLElement | null = null;

  constructor() {
    super();
    wireOverlayLifecycleEvents(this);
  }

  // @ts-expect-error – Lion expects this to return an OverlayConfig
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
              offset: [0, this.distance],
            },
          },
          ...viewportEscapingModifiers(),
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

  // Resolve the invoker: explicit anchor first, then `for`, then a slotted
  // `slot="invoker"` child.
  // @ts-ignore Lion's JSDoc types this getter as always-defined.
  override get _overlayInvokerNode(): HTMLElement | undefined {
    if (this.anchor instanceof HTMLElement) {
      return this.anchor;
    }
    if (this.anchor?.contextElement instanceof HTMLElement) {
      return this.anchor.contextElement;
    }
    if (this.for) {
      // When disconnected, getRootNode() may return a root (the element
      // itself or a bare DocumentFragment) without getElementById.
      const root = this.getRootNode() as Document | ShadowRoot;
      if (typeof root.getElementById !== 'function') {
        return undefined;
      }
      return (root.getElementById(this.for) as HTMLElement | null) ?? undefined;
    }
    return super._overlayInvokerNode;
  }

  get _overlayReferenceNode(): HTMLElement | undefined {
    return this.anchor as HTMLElement | undefined;
  }

  protected override render(): unknown {
    return html`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <div id="overlay-content-node-wrapper">
        <div class="popover-pane" part="popup">
          <slot name="content">
            <slot name="content-body"></slot>
            <slot name="content-footer"></slot>
          </slot>
        </div>
      </div>
    `;
  }

  override connectedCallback() {
    this.#ensureContentWrapper();
    super.connectedCallback();
  }

  /**
   * Lion expects popover content as a light-DOM child with `slot="content"`.
   * Consumers may put their content in the default slot, so wrap it on
   * connect. Whitespace-only default content is left alone — a generated
   * (empty) `slot="content"` element would otherwise suppress the shadow
   * slot's `content-body`/`content-footer` fallback.
   */
  #ensureContentWrapper() {
    if (this.#contentWrapper?.isConnected) {
      return;
    }

    const defaultSlotNodes = Array.from(this.childNodes).filter((node) => {
      if (node instanceof Element) {
        return node.slot === '';
      }
      return (node.textContent ?? '').trim() !== '';
    });

    if (!defaultSlotNodes.length) {
      return;
    }

    const wrapper = document.createElement('div');
    wrapper.slot = 'content';
    wrapper.append(...defaultSlotNodes);
    this.append(wrapper);
    this.#contentWrapper = wrapper;
  }

  protected override updated(changed: PropertyValues) {
    super.updated(changed);

    if ((changed.has('for') || changed.has('anchor')) && this._overlayCtrl) {
      this._overlayCtrl.updateConfig({
        invokerNode: this._overlayInvokerNode,
        referenceNode: this._overlayReferenceNode,
      });
    }
  }

  async show(): Promise<void> {
    this.opened = true;
    await this.updateComplete;
    await this.open();
  }

  async hide(): Promise<void> {
    this.opened = false;
    await this.updateComplete;
    await this.close();
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
