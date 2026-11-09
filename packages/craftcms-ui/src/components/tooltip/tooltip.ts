import {css, html, LitElement, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {createPopper, type Instance as PopperInstance} from '@popperjs/core';
import {watchHover, type HoverWatcher} from '../../utilities/overlay-hover.js';
import {wireOverlayLifecycleEvents} from '../../utilities/overlay-events.js';
import {claimEscape, isEscapeClaimed} from '../../utilities/overlay-escape.js';
import {
  hideLayer,
  layerStyles,
  overlayModifiers,
  showLayer,
  supportsPopover,
} from '../../utilities/overlay-layer.js';
import {uniqueId} from '../../utilities/unique-id.js';

/**
 * craft-tooltip shows contextual text for an external trigger element
 * referenced by id.
 *
 * The content gets `role="tooltip"`, and the invoker refers to it with
 * `aria-describedby` (or `aria-labelledby`, with `invoker-relation="label"`),
 * alongside any references it already has.
 *
 * @example <craft-tooltip for="my-button" placement="right-start">Help text</craft-tooltip>
 *
 * @slot - The tooltip text.
 * @slot invoker - The trigger, when it isn't named with `for`.
 *
 * @cssproperty [--c-tooltip-max-width=min(45ch, 100vw - 2rem)] - Widest the
 *   tooltip gets before its text wraps. The default keeps lines short enough
 *   to read easily, and within narrow viewports.
 *
 * @fires opened-changed - `opened` changed. `detail.opened` is the new state.
 * @fires craft-show - The overlay is opening. Fires as the state flips.
 * @fires craft-after-show - The overlay has opened and its update has settled.
 * @fires craft-hide - The overlay is closing. Fires as the state flips.
 * @fires craft-after-hide - The overlay has closed and its update has settled.
 */
export default class CraftTooltip extends LitElement {
  static override styles = [
    layerStyles,
    css`
      :host {
        display: contents;
      }

      :host([hidden]) {
        display: none;
      }

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
        max-inline-size: var(--c-tooltip-max-width, min(45ch, 100vw - 2rem));
        overflow-wrap: break-word;
      }
    `,
  ];

  /** Whether the tooltip is showing. */
  @property({type: Boolean, reflect: true}) opened = false;

  /** Id of the trigger element within the same document/shadow root. */
  @property({reflect: true}) for?: string;

  /** Popper placement, e.g. `top`, `right-start`. */
  @property({reflect: true}) placement = 'top';

  /**
   * Space-separated triggers. `hover focus` (default) shows the tooltip while
   * the invoker is hovered or has keyboard focus; `click` toggles it on
   * invoker click; `manual` leaves it to `show()` and `hide()`.
   */
  @property({reflect: true}) trigger = 'hover focus';

  /**
   * Whether the tooltip describes its invoker (`description`, the default) or
   * names it (`label`), for an invoker with no text of its own.
   */
  @property({attribute: 'invoker-relation'}) invokerRelation:
    | 'description'
    | 'label' = 'description';

  #contentWrapper: HTMLElement | null = null;

  #contentId = uniqueId('tooltip');

  /** The invoker the tooltip is bound to, and the reference it added. */
  #invoker: {node: HTMLElement; attribute: string} | null = null;

  #hover: HoverWatcher | null = null;

  #popper: PopperInstance | null = null;

  #isSetUp = false;

  constructor() {
    super();
    wireOverlayLifecycleEvents(this);
  }

  override requestUpdate(
    name?: PropertyKey,
    oldValue?: unknown,
    options?: Parameters<LitElement['requestUpdate']>[2]
  ): void {
    super.requestUpdate(name, oldValue, options);

    if (name === 'opened' && this.opened !== oldValue) {
      this.dispatchEvent(
        new CustomEvent('opened-changed', {detail: {opened: this.opened}})
      );
    }
  }

  /** The trigger: the `for` element, or a slotted `slot="invoker"` child. */
  get invoker(): HTMLElement | undefined {
    if (this.for) {
      // When disconnected, getRootNode() may return a root (the element
      // itself or a bare DocumentFragment) without getElementById.
      const root = this.getRootNode() as Document | ShadowRoot;
      if (typeof root.getElementById !== 'function') {
        return undefined;
      }
      return (root.getElementById(this.for) as HTMLElement | null) ?? undefined;
    }

    return Array.from(this.children).find(
      (child): child is HTMLElement =>
        child instanceof HTMLElement && child.slot === 'invoker'
    );
  }

  get #content(): HTMLElement | null {
    return this.#contentWrapper;
  }

  get #layer(): HTMLElement | null {
    return (
      this.shadowRoot?.querySelector<HTMLElement>('.overlay-layer') ?? null
    );
  }

  get #positioned(): HTMLElement | null {
    return (
      this.shadowRoot?.querySelector<HTMLElement>('.overlay-position') ?? null
    );
  }

  get #triggers(): string[] {
    return this.trigger.split(' ');
  }

  /** Shows the tooltip, regardless of `trigger`. Resolves once it is open. */
  async show(): Promise<void> {
    this.opened = true;
    await this.updateComplete;
  }

  /** Re-measures the tooltip's position, for content that changed size. */
  reposition(): void {
    void this.#popper?.update();
  }

  /** Hides the tooltip. Resolves once it is closed. */
  async hide(): Promise<void> {
    this.opened = false;
    await this.updateComplete;
  }

  protected override render() {
    // Nested below the layer for Popper's containing-block search; see
    // craft-popover.
    return html`
      <slot name="invoker" @slotchange=${this.#onInvokerSlotChange}></slot>
      <div
        class="overlay-layer"
        role="none"
        popover=${supportsPopover ? 'manual' : nothing}
      >
        <div class="overlay-position">
          <slot name="content"></slot>
        </div>
      </div>
    `;
  }

  #onInvokerSlotChange = (): void => {
    if (this.#isSetUp && this.invoker !== this.#invoker?.node) {
      this.#bind();
    }
  };

  override connectedCallback() {
    this.#ensureContentWrapper();
    super.connectedCallback();

    if (this.hasUpdated && !this.#isSetUp) {
      this.#setup();
    }
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.#hidePane();

    void this.updateComplete.then(() => {
      if (this.isConnected) {
        // Moved: `for` may name another element in the root it landed in.
        if (this.invoker !== this.#invoker?.node) {
          this.#bind();
        }
        if (this.opened) {
          this.#showPane();
        }
        return;
      }

      this.#unbind();
      this.opened = false;
      this.#isSetUp = false;
    });
  }

  protected override firstUpdated(changed: PropertyValues): void {
    super.firstUpdated(changed);
    this.#setup();
  }

  protected override updated(changed: PropertyValues): void {
    super.updated(changed);

    if (!this.#isSetUp) {
      return;
    }

    if (
      changed.has('for') ||
      changed.has('trigger') ||
      changed.has('invokerRelation')
    ) {
      this.#bind();
    }

    if (changed.has('opened')) {
      if (this.opened) {
        this.#showPane();
      } else {
        this.#hidePane();
      }
    }

    if (changed.has('placement') && this.#popper) {
      void this.#popper.setOptions(this.#popperOptions());
    }
  }

  #setup(): void {
    this.#isSetUp = true;
    this.#bind();

    if (this.opened) {
      this.#showPane();
    }
  }

  /**
   * Tooltip content goes in the default slot, so it's wrapped on connect,
   * unless markup supplies a `slot="content"` element itself.
   */
  #ensureContentWrapper() {
    if (this.#contentWrapper?.isConnected) {
      return;
    }

    let wrapper = Array.from(this.children).find(
      (child): child is HTMLElement =>
        child instanceof HTMLElement && child.slot === 'content'
    );

    if (!wrapper) {
      wrapper = document.createElement('div');
      wrapper.slot = 'content';
      wrapper.append(
        ...Array.from(this.childNodes).filter(
          (node) => !(node instanceof Element) || node.slot === ''
        )
      );
      this.append(wrapper);
    }

    wrapper.id ||= this.#contentId;
    this.#contentId = wrapper.id;
    wrapper.setAttribute('role', 'tooltip');
    this.#contentWrapper = wrapper;
    wrapper.addEventListener('keyup', this.#onEscape, true);
  }

  /** Binds the current invoker, after dropping whatever was bound before. */
  #bind(): void {
    this.#unbind();

    const node = this.invoker;
    const content = this.#content;

    // Rebinding to an invoker shows it again while it's still open.
    if (!node || !content) {
      this.#hidePane();
      return;
    }

    const attribute =
      this.invokerRelation === 'label' ? 'aria-labelledby' : 'aria-describedby';
    this.#invoker = {node, attribute};
    this.#addReference(node, attribute);

    // Captured, so it runs before a popover sharing the invoker closes on the
    // same press.
    node.addEventListener('keyup', this.#onEscape, true);

    if (this.#triggers.includes('click')) {
      node.addEventListener('click', this.#onInvokerClick);
    } else if (!this.#triggers.includes('manual')) {
      // Shows promptly, and hides without a delay out so moving along a row
      // of icons doesn't leave the previous tooltip beside the new one.
      this.#hover = watchHover({
        invoker: node,
        content,
        delayIn: 200,
        delayOut: 0,
        show: () => void (this.opened = true),
        hide: () => void (this.opened = false),
      });
    }

    // An open tooltip may have no Popper yet, and one only follows the scroll
    // containers it found when created.
    if (this.opened) {
      this.#popper?.destroy();
      this.#popper = null;
      this.#showPane();
    }
  }

  #unbind(): void {
    this.#hover?.teardown();
    this.#hover = null;

    if (!this.#invoker) {
      return;
    }

    const {node, attribute} = this.#invoker;
    node.removeEventListener('keyup', this.#onEscape, true);
    node.removeEventListener('click', this.#onInvokerClick);
    this.#removeReference(node, attribute);
    this.#invoker = null;
  }

  #addReference(node: HTMLElement, attribute: string): void {
    const ids = (node.getAttribute(attribute) ?? '').split(/\s+/);

    if (!ids.includes(this.#contentId)) {
      node.setAttribute(
        attribute,
        [...ids, this.#contentId].filter(Boolean).join(' ')
      );
    }
  }

  #removeReference(node: HTMLElement, attribute: string): void {
    const ids = (node.getAttribute(attribute) ?? '')
      .split(/\s+/)
      .filter((id) => id && id !== this.#contentId);

    if (ids.length) {
      node.setAttribute(attribute, ids.join(' '));
    } else {
      node.removeAttribute(attribute);
    }
  }

  #onInvokerClick = (): void => {
    this.opened = !this.opened;
  };

  /**
   * Closes on Escape released on the invoker or the content, and claims the
   * press so a popover around the tooltip stays open.
   */
  #onEscape = (event: KeyboardEvent): void => {
    if (event.key !== 'Escape' || !this.opened || isEscapeClaimed(event)) {
      return;
    }

    claimEscape(event);
    this.opened = false;
  };

  #popperOptions() {
    return {
      // Position relative to the viewport, which is also what the top layer
      // places against.
      strategy: 'fixed' as const,
      placement: this.placement as PopperInstance['state']['placement'],
      // craft-popover's default distance, so a tooltip lines up with a flyout
      // opened from the same row.
      modifiers: overlayModifiers(4),
    };
  }

  #showPane(): void {
    const layer = this.#layer;
    const positioned = this.#positioned;
    const invoker = this.#invoker?.node;

    // An update queued before removal still runs, and a disconnected layer
    // can't be shown. Without an invoker there's nothing to show it beside.
    if (
      !this.isConnected ||
      !layer ||
      !positioned ||
      !invoker ||
      this.#popper
    ) {
      return;
    }

    showLayer(layer);
    this.#popper = createPopper(invoker, positioned, this.#popperOptions());
    this.#popper.forceUpdate();
  }

  #hidePane(): void {
    const layer = this.#layer;

    this.#popper?.destroy();
    this.#popper = null;
    this.#hover?.reset();

    if (layer) {
      hideLayer(layer);
    }
  }
}

if (!customElements.get('craft-tooltip')) {
  customElements.define('craft-tooltip', CraftTooltip);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-tooltip': CraftTooltip;
  }
}
