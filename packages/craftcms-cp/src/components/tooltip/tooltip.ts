import {css, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {LionTooltip} from '@lion/ui/tooltip.js';
import {wireOverlayLifecycleEvents} from '../../utilities/overlay-events.js';

/**
 * craft-tooltip (registered as `c-tooltip`) shows contextual text for an
 * external trigger element referenced by id.
 *
 * @example <c-tooltip for="my-button" placement="right-start">Help text</c-tooltip>
 *
 * Emits `craft-show`/`craft-after-show`/`craft-hide`/`craft-after-hide`.
 */
export default class CraftTooltip extends LionTooltip {
  /** Id of the trigger element within the same document/shadow root. */
  @property({reflect: true}) for?: string;

  /** Popper placement, e.g. `top`, `right-start`. */
  @property({reflect: true}) placement = 'top';

  /**
   * Space-separated triggers. `hover focus` (default) uses Lion's tooltip
   * interaction; `click` toggles on invoker click; `manual` disables
   * automatic triggers entirely.
   */
  @property({reflect: true}) trigger = 'hover focus';

  #contentWrapper: HTMLElement | null = null;

  #onInvokerClick = () => {
    this.opened = !this.opened;
  };

  constructor() {
    super();
    wireOverlayLifecycleEvents(this);
  }

  static override get styles() {
    return [
      ...super.styles,
      css`
        ::slotted([slot='content']) {
          background-color: var(--c-color-black-fill-loud);
          border: 1px solid var(--c-color-black-border-loud);
          color: var(--c-color-black-on-loud);
          padding: var(--c-tooltip-padding, calc(4rem / 16) calc(8rem / 16));
          font-family: var(--c-font-body);
          font-size: var(--c-text-base);
          font-weight: 400;
          line-height: 1.3;
          border-radius: var(--c-radius-sm);
          box-shadow: var(--c-shadow-md);
          width: max-content;
        }
      `,
    ];
  }

  override connectedCallback() {
    this.#ensureContentWrapper();
    super.connectedCallback();
  }

  /**
   * Lion expects tooltip content as a light-DOM child with `slot="content"`.
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

  get #isClickTriggered(): boolean {
    return this.trigger.split(' ').includes('click');
  }

  get #isManual(): boolean {
    return this.trigger.split(' ').includes('manual');
  }

  // Resolve the external invoker via `for`, falling back to Lion's slotted
  // `slot="invoker"` child.
  // @ts-ignore Lion's JSDoc types this getter as always-defined.
  override get _overlayInvokerNode(): HTMLElement | undefined {
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
  override _defineOverlayConfig() {
    const config = {...super._defineOverlayConfig()};

    if (this.#isClickTriggered || this.#isManual) {
      // Disable Lion's hover/focus interaction.
      delete config.visibilityTriggerFunction;
    }

    return {
      ...config,
      popperConfig: {
        ...config.popperConfig,
        placement: this.placement,
      },
    };
  }

  // @ts-ignore Lion's OverlayMixin is typed via JSDoc.
  override _setupOpenCloseListeners() {
    super._setupOpenCloseListeners();
    if (this.#isClickTriggered) {
      this._overlayInvokerNode?.addEventListener('click', this.#onInvokerClick);
    }
  }

  // @ts-ignore Lion's OverlayMixin is typed via JSDoc.
  override _teardownOpenCloseListeners() {
    super._teardownOpenCloseListeners();
    this._overlayInvokerNode?.removeEventListener(
      'click',
      this.#onInvokerClick
    );
  }

  protected override updated(changed: PropertyValues) {
    super.updated(changed);

    if (
      changed.has('for') &&
      changed.get('for') !== undefined &&
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

/**
 * @TODO rename this once I figure out what to do with the existing `craft-tooltip`
 */
if (!customElements.get('c-tooltip')) {
  customElements.define('c-tooltip', CraftTooltip);
}

declare global {
  interface HTMLElementTagNameMap {
    'c-tooltip': CraftTooltip;
    'craft-tooltip': CraftTooltip;
  }
}
