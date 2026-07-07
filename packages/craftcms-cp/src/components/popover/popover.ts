import {css, html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {OverlayMixin, withDropdownConfig} from '@lion/ui/overlays.js';
import {wireOverlayLifecycleEvents} from '../../utilities/overlay-events.js';

/**
 * craft-popover shows rich interactive content anchored to an external
 * trigger element. The trigger is referenced by id (`for`) or passed directly
 * (`anchor`); clicking it toggles the popover, and clicking outside or
 * pressing Escape dismisses it.
 *
 * Emits `craft-show`/`craft-after-show`/`craft-hide`/`craft-after-hide`.
 */
export default class CraftPopover extends OverlayMixin(LitElement) {
  /** Id of the trigger element within the same document/shadow root. */
  @property({reflect: true}) for?: string;

  /** Explicit anchor element; takes precedence over `for`. */
  @property({attribute: false}) anchor?: HTMLElement;

  /** Popper placement, e.g. `bottom-start`. */
  @property({reflect: true}) placement = 'bottom';

  /** Distance in pixels between the popover and its anchor. */
  @property({type: Number}) distance = 8;

  /** Accepted for API compatibility; craft-popover never renders an arrow. */
  @property({type: Boolean, attribute: 'without-arrow'}) withoutArrow = false;

  #contentWrapper: HTMLElement | null = null;

  constructor() {
    super();
    wireOverlayLifecycleEvents(this);
  }

  static override get styles() {
    return [
      css`
        :host {
          display: inline-block;
        }

        :host([hidden]) {
          display: none;
        }

        ::slotted([slot='content']) {
          background-color: var(--c-surface-raised);
          border: 1px solid var(--c-color-neutral-border-quiet);
          border-radius: var(--c-radius-lg);
          box-shadow: var(--c-shadow-md);
          padding: var(--c-spacing-md);
        }
      `,
    ];
  }

  override render() {
    return html`
      <slot name="invoker"></slot>
      <div id="overlay-content-node-wrapper">
        <slot name="content"></slot>
      </div>
    `;
  }

  override connectedCallback() {
    this.#ensureContentWrapper();
    super.connectedCallback();
  }

  /**
   * Lion expects popover content as a light-DOM child with `slot="content"`.
   * Consumers put their content in the default slot, so wrap it on connect.
   */
  #ensureContentWrapper() {
    if (this.#contentWrapper?.isConnected) {
      return;
    }

    const wrapper = document.createElement('div');
    wrapper.slot = 'content';
    wrapper.append(
      ...Array.from(this.childNodes).filter(
        (node) => !(node instanceof Element) || node.slot === ''
      )
    );
    this.append(wrapper);
    this.#contentWrapper = wrapper;
  }

  // Resolve the invoker: explicit anchor first, then `for`, then a slotted
  // `slot="invoker"` child.
  override get _overlayInvokerNode(): HTMLElement | undefined {
    if (this.anchor) {
      return this.anchor;
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

  // @ts-ignore Lion's OverlayMixin is typed via JSDoc.
  _defineOverlayConfig() {
    return {
      ...withDropdownConfig(),
      // Popover width follows its content, not the trigger.
      inheritsReferenceWidth: 'none',
      popperConfig: {
        strategy: 'absolute',
        placement: this.placement,
        modifiers: [{name: 'offset', options: {offset: [0, this.distance]}}],
      },
    };
  }

  protected override updated(changed: PropertyValues) {
    super.updated(changed);

    if (
      (changed.has('for') || changed.has('anchor')) &&
      (changed.get('for') !== undefined ||
        changed.get('anchor') !== undefined) &&
      this._overlayCtrl
    ) {
      this._overlayCtrl.updateConfig({invokerNode: this._overlayInvokerNode});
    }
  }

  async show(): Promise<void> {
    this.opened = true;
    await this.updateComplete;
  }

  async hide(): Promise<void> {
    this.opened = false;
    await this.updateComplete;
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
