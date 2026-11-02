import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {
  createPopper,
  type Instance as PopperInstance,
  type Modifier,
  type Options as PopperOptions,
  type VirtualElement,
} from '@popperjs/core';
import {swallowHeldEnterKeyup} from '@src/utilities/enter-keyup.js';
import {isFocusable} from '@src/utilities/focus-return.js';
import {claimEscape, isEscapeClaimed} from '@src/utilities/overlay-escape.js';
import {wireOverlayLifecycleEvents} from '@src/utilities/overlay-events.js';
import {viewportEscapingModifiers} from '@src/utilities/overlay-position.js';
import {uniqueId} from '@src/utilities/unique-id.js';
import hostStyles from '@src/styles/host.styles';
import styles from './popover.styles.js';

/**
 * How far the pane stays from the viewport's edge when it's sized to fit,
 * matching the padding `flip` uses. Any less and the fitted pane would count
 * as overflowing, flipping it to the other side.
 */
const VIEWPORT_MARGIN = 16;

/**
 * Caps the pane at the room left between it and the viewport edge it opens
 * toward, through `--_popover-available-block-size`, so a popover with room
 * below it can use it rather than stopping at the stylesheet's `40vh`. An
 * explicit `--popover-max-block-size` still wins.
 *
 * Measured from where Popper has just placed the pane, so it follows a flip.
 * Changing the cap changes the pane's height, which a `top` placement is
 * positioned by, so the popper updates once more when it does.
 */
const fitAvailableBlockSize: Partial<Modifier<'fitAvailableBlockSize', {}>> = {
  name: 'fitAvailableBlockSize',
  enabled: true,
  phase: 'main',
  requiresIfExists: ['offset', 'preventOverflow', 'flip'],
  fn: ({state, instance}) => {
    const top = state.modifiersData.popperOffsets?.y ?? 0;
    const viewport = document.documentElement.clientHeight;
    const side = state.placement.split('-')[0];
    const available = Math.floor(
      side === 'top'
        ? top + state.rects.popper.height - VIEWPORT_MARGIN
        : side === 'bottom'
          ? viewport - VIEWPORT_MARGIN - top
          : viewport - VIEWPORT_MARGIN * 2
    );
    const style = state.elements.popper.style;
    const property = '--_popover-available-block-size';
    const previous = style.getPropertyValue(property);

    if (!viewport || available <= 0) {
      style.removeProperty(property);
    } else {
      style.setProperty(property, `${available}px`);
    }

    if (style.getPropertyValue(property) !== previous) {
      void instance.update();
    }
  },
};

/**
 * Whether the Escape now being pressed belongs to an open overlay built on the
 * older overlay controller (an `_overlayCtrl` host). Those close on the same
 * keyup but keep their record of it to themselves, so this is judged on
 * keydown, while they're still open.
 */
let escapeOwnedElsewhere = false;

if (typeof window !== 'undefined') {
  window.addEventListener(
    'keydown',
    (event) => {
      if (event.key !== 'Escape') {
        return;
      }

      escapeOwnedElsewhere = event
        .composedPath()
        .some(
          (node) =>
            (node as {_overlayCtrl?: {isShown?: boolean}})._overlayCtrl
              ?.isShown === true
        );
    },
    true
  );
  window.addEventListener('keyup', (event) => {
    if (event.key === 'Escape') {
      escapeOwnedElsewhere = false;
    }
  });
}

const supportsPopover =
  typeof HTMLElement !== 'undefined' &&
  typeof HTMLElement.prototype.showPopover === 'function';

/**
 * @summary A non-modal popover.
 *
 * Overlays content on top of the page without affecting document flow.
 * The trigger can be a slotted `invoker` child, an external element
 * referenced by id (`for`), or passed directly (`anchor`). Clicking it
 * toggles the popover; Escape and outside clicks dismiss it.
 *
 * The open pane is promoted to the browser's top layer, so it isn't clipped
 * by an ancestor's overflow or held under a sibling's stacking context. Its
 * content stays where it was slotted.
 *
 * A `close-overlay` event dispatched from inside the content closes it.
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
 *
 * @fires opened-changed - `opened` changed. `detail.opened` is the new state.
 * @fires before-opened - The invoker, Escape, an outside click or `show()` is
 *   about to open the popover. Cancelable. Setting `opened` doesn't fire it.
 * @fires before-closed - The invoker, Escape, an outside click or `hide()` is
 *   about to close the popover. Cancelable. Setting `opened` doesn't fire it.
 * @fires craft-show - The overlay is opening. Fires as the state flips.
 * @fires craft-after-show - The overlay has opened and its update has settled.
 * @fires craft-hide - The overlay is closing. Fires as the state flips.
 * @fires craft-after-hide - The overlay has closed and its update has settled.
 */
export default class CraftPopover extends LitElement {
  // `hostStyles` for its box-sizing reset, which a shadow root doesn't inherit
  // from the page: without it the pane's 1px border sits outside its width and
  // it overshoots whatever it was sized to.
  static override styles = [hostStyles, styles];

  /** Whether the popover is open. */
  @property({type: Boolean, reflect: true}) opened = false;

  /** Names the popup, which is a `role="dialog"`, for assistive tech. */
  @property() label?: string;

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

  /** Whether the overlay should be at least as wide as its invoker. */
  @property({attribute: 'match-invoker-width', type: Boolean, reflect: true})
  matchInvokerWidth = false;

  /**
   * Stops the popover writing `aria-expanded` onto its invoker and giving its
   * pane `role="dialog"`. Set it when the invoker is a positioning anchor
   * rather than the control itself: a generic element can't carry those
   * attributes, and only the consumer knows which of its own elements can.
   */
  @property({type: Boolean, attribute: 'without-invoker-aria', reflect: true})
  withoutInvokerAria = false;

  /**
   * Stops clicks on the invoker toggling the popover, leaving it to be opened
   * and closed from code. Escape and outside clicks still close it.
   */
  @property({type: Boolean, reflect: true}) manual = false;

  #contentWrapper: HTMLElement | null = null;

  #paneId = uniqueId('overlay-content');

  /**
   * The invoker the listeners are bound to, its own `aria-expanded`, and
   * whether the popover is the one writing that attribute now.
   */
  #invoker: {
    node: HTMLElement;
    ariaExpanded: string | null;
    ownsAria: boolean;
  } | null = null;

  #popper: PopperInstance | null = null;

  /** Whether the pane is showing. Trails `opened` until the update runs. */
  #shown = false;

  #isSetUp = false;

  #pointerDownInside = false;

  #pointerUpInside = false;

  constructor() {
    super();
    wireOverlayLifecycleEvents(this);
    this.addEventListener('keydown', this.#claimEscape);
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

  /** The element the popover is positioned against. */
  get #reference(): HTMLElement | VirtualElement | undefined {
    return this.anchor ?? this.invoker;
  }

  /** The element promoted to the top layer. */
  get #layer(): HTMLElement | null {
    return (
      this.shadowRoot?.querySelector<HTMLElement>('.overlay-layer') ?? null
    );
  }

  /** The element Popper positions, inside the layer. */
  get #wrapper(): HTMLElement | null {
    return (
      this.shadowRoot?.querySelector<HTMLElement>('.overlay-position') ?? null
    );
  }

  get #pane(): HTMLElement | null {
    return this.shadowRoot?.querySelector<HTMLElement>('.popover-pane') ?? null;
  }

  /**
   * The element that opens the popover: an explicit anchor first, then `for`,
   * then a slotted `slot="invoker"` child.
   */
  get invoker(): HTMLElement | undefined {
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
    return Array.from(this.children).find(
      (child): child is HTMLElement =>
        child instanceof HTMLElement && child.slot === 'invoker'
    );
  }

  /** Opens the popover. Resolves once it has opened. */
  async show(): Promise<void> {
    this.#request(true);
    await this.updateComplete;
  }

  /** Closes the popover. Resolves once it has closed. */
  async hide(): Promise<void> {
    this.#request(false);
    await this.updateComplete;
  }

  /** Re-measures the pane's position, for an anchor that has moved. */
  reposition(): void {
    void this.#popper?.update();
  }

  /** Opens the popover if it's closed, and closes it if it's open. */
  async toggle(): Promise<void> {
    await (this.opened ? this.hide() : this.show());
  }

  /**
   * Called once the popover is wired to its invoker, after its first render
   * and again after it's reconnected following a removal.
   */
  protected _setupOverlay(): void {}

  /** Called when the popover is removed from the document for good. */
  protected _teardownOverlay(): void {}

  /** Called once the pane is showing and positioned. */
  protected _overlayShown(): void {}

  /**
   * Re-binds the popover to its current invoker. Call it after replacing the
   * invoker by means a slot change doesn't report.
   */
  protected _syncInvoker(): void {
    const node = this.invoker ?? null;

    if (node === this.#invoker?.node) {
      this.#syncInvokerAria();
      return;
    }

    this.#unbindInvoker();

    if (node) {
      this.#invoker = {
        node,
        ariaExpanded: node.getAttribute('aria-expanded'),
        ownsAria: false,
      };
      node.addEventListener('click', this.#onInvokerClick);
      node.addEventListener('keyup', this.#onEscape);
      this.#syncInvokerAria();
    }

    if (this.#popper && this.#reference) {
      this.#popper.state.elements.reference = this.#reference as Element;
      void this.#popper.update();
    }
  }

  #unbindInvoker(): void {
    if (!this.#invoker) {
      return;
    }

    const {node, ariaExpanded, ownsAria} = this.#invoker;
    node.removeEventListener('click', this.#onInvokerClick);
    node.removeEventListener('keyup', this.#onEscape);
    this.#removePointerListeners(node);
    if (ownsAria) {
      this.#restoreAttribute(node, 'aria-expanded', ariaExpanded);
    }
    this.#invoker = null;
  }

  #restoreAttribute(node: HTMLElement, name: string, value: string | null) {
    if (value === null) {
      node.removeAttribute(name);
    } else {
      node.setAttribute(name, value);
    }
  }

  #syncInvokerAria(): void {
    if (!this.#invoker) {
      return;
    }

    const invoker = this.#invoker;

    if (!this.withoutInvokerAria) {
      invoker.node.setAttribute('aria-expanded', String(this.#shown));
      invoker.ownsAria = true;
    } else if (invoker.ownsAria) {
      this.#restoreAttribute(
        invoker.node,
        'aria-expanded',
        invoker.ariaExpanded
      );
      invoker.ownsAria = false;
    }
  }

  /**
   * Asks to open or close: announces it with a cancelable `before-*` event,
   * then flips `opened`, which the update applies.
   */
  #request(opened: boolean): void {
    if (opened === this.opened) {
      return;
    }

    const event = new CustomEvent(opened ? 'before-opened' : 'before-closed', {
      cancelable: true,
    });
    this.dispatchEvent(event);

    if (event.defaultPrevented) {
      return;
    }

    this.opened = opened;
  }

  #onInvokerClick = (): void => {
    const invoker = this.#invoker?.node;

    if (
      this.manual ||
      !invoker ||
      (invoker as HTMLButtonElement).disabled ||
      invoker.getAttribute('aria-disabled') === 'true'
    ) {
      return;
    }

    this.#request(!this.opened);
  };

  /**
   * The popover closes on Escape's keyup, so its keydown would otherwise reach
   * an ancestor's own Escape handling, such as a panel that closes on it.
   * Marking it handled leaves the key to the popover alone.
   */
  #claimEscape = (event: KeyboardEvent): void => {
    if (event.key === 'Escape' && this.opened) {
      event.preventDefault();
    }
  };

  /**
   * Closes on an Escape released in the content or on the invoker. A nested
   * popover that closes on it marks the event, so the one around it stays
   * open; a closed popover's invoker passes it on to the popover around it.
   */
  #onEscape = (event: KeyboardEvent): void => {
    if (event.key !== 'Escape' || isEscapeClaimed(event)) {
      return;
    }

    if (escapeOwnedElsewhere) {
      claimEscape(event);
      return;
    }

    if (!this.opened) {
      return;
    }

    claimEscape(event);
    this.#request(false);
  };

  #onCloseOverlay = (event: Event): void => {
    event.stopPropagation();
    this.#request(false);
  };

  #onPointerDownInside = (): void => {
    this.#pointerDownInside = true;
  };

  #onPointerUpInside = (): void => {
    this.#pointerUpInside = true;
  };

  /**
   * Closes on a click that both started and ended outside the content and the
   * invoker. The document sees the mouseup first, in its capture phase, so the
   * decision waits a task for the inside listeners to report.
   */
  #onDocumentPointerUp = (): void => {
    setTimeout(() => {
      if (!this.#pointerDownInside && !this.#pointerUpInside) {
        this.#request(false);
      }

      this.#pointerDownInside = false;
      this.#pointerUpInside = false;
    });
  };

  /** Focus leaving the window, into another frame or app, closes it too. */
  #onWindowBlur = (): void => {
    setTimeout(() => this.#request(false));
  };

  #addPointerListeners(node: HTMLElement): void {
    node.addEventListener('mousedown', this.#onPointerDownInside, true);
    node.addEventListener('mouseup', this.#onPointerUpInside, true);
  }

  #removePointerListeners(node: HTMLElement): void {
    node.removeEventListener('mousedown', this.#onPointerDownInside, true);
    node.removeEventListener('mouseup', this.#onPointerUpInside, true);
  }

  /**
   * Whether focus is in the popover's content, slotted or not, rather than on
   * its invoker.
   */
  #hasFocusInContent(): boolean {
    const active = (this.getRootNode() as Document | ShadowRoot).activeElement;
    if (!active) {
      return false;
    }

    const invoker = this.invoker;
    if (invoker && (invoker === active || invoker.contains(active))) {
      return false;
    }

    return active === this || this.contains(active);
  }

  #popperOptions(): Partial<PopperOptions> {
    return {
      // Position relative to the viewport, which is also what the top layer
      // places against.
      strategy: 'fixed',
      placement: this.placement,
      modifiers: [
        {name: 'preventOverflow', options: {padding: 8}},
        {name: 'flip', options: {padding: VIEWPORT_MARGIN}},
        {name: 'offset', options: {offset: [0, this.distance]}},
        {name: 'arrow', enabled: false},
        ...(viewportEscapingModifiers() as Partial<Modifier<string, object>>[]),
        fitAvailableBlockSize,
      ],
    };
  }

  /** Sizes the pane to at least its reference's width, when asked to. */
  #syncReferenceWidth(): void {
    const wrapper = this.#wrapper;
    const reference = this.#reference;

    if (!wrapper) {
      return;
    }

    if (this.matchInvokerWidth && reference) {
      wrapper.style.minWidth = `${reference.getBoundingClientRect().width}px`;
      wrapper.style.width = 'auto';
    } else {
      wrapper.style.removeProperty('min-width');
      wrapper.style.removeProperty('width');
    }
  }

  #showPane(): void {
    const layer = this.#layer;
    const wrapper = this.#wrapper;
    const reference = this.#reference;

    if (!layer || !wrapper || this.#shown) {
      return;
    }

    layer.toggleAttribute('data-open', true);

    if (supportsPopover && !layer.matches(':popover-open')) {
      layer.showPopover();
    }

    this.#shown = true;
    this.#syncInvokerAria();
    this.#syncReferenceWidth();

    if (reference) {
      this.#popper = createPopper(
        reference as Element,
        wrapper,
        this.#popperOptions()
      );
      this.#popper.forceUpdate();
    }

    this.#addPointerListeners(wrapper);
    if (this.#invoker) {
      this.#addPointerListeners(this.#invoker.node);
    }
    document.documentElement.addEventListener(
      'mouseup',
      this.#onDocumentPointerUp,
      true
    );
    window.addEventListener('blur', this.#onWindowBlur);

    this._overlayShown();
  }

  #hidePane({restoreFocus = true} = {}): void {
    const layer = this.#layer;
    const wrapper = this.#wrapper;

    if (!layer || !wrapper || !this.#shown) {
      return;
    }

    const focusWasInContent = restoreFocus && this.#hasFocusInContent();
    swallowHeldEnterKeyup();

    if (supportsPopover && layer.matches(':popover-open')) {
      layer.hidePopover();
    }

    layer.removeAttribute('data-open');
    this.#popper?.destroy();
    this.#popper = null;
    this.#shown = false;
    this.#syncInvokerAria();

    this.#removePointerListeners(wrapper);
    if (this.#invoker) {
      this.#removePointerListeners(this.#invoker.node);
    }
    document.documentElement.removeEventListener(
      'mouseup',
      this.#onDocumentPointerUp,
      true
    );
    window.removeEventListener('blur', this.#onWindowBlur);
    this.#pointerDownInside = false;
    this.#pointerUpInside = false;

    if (focusWasInContent) {
      this.#returnFocus();
    }
  }

  /**
   * Returns focus to the invoker once the popover has closed with focus in its
   * content, unless focus has since gone somewhere that can hold it. Judged the
   * same way as `craft-dialog`'s return, with `isFocusable()`.
   */
  #returnFocus(): void {
    if (isFocusable(document.activeElement) && !this.#hasFocusInContent()) {
      return;
    }

    const invoker = this.invoker;
    invoker?.focus();
    invoker?.scrollIntoView({block: 'nearest'});
  }

  protected override render(): unknown {
    const dialog = !this.withoutInvokerAria;

    return html`
      <slot name="invoker" @slotchange=${this.#onInvokerSlotChange}></slot>
      <slot name="backdrop"></slot>
      <!--
        Popper looks for the pane's containing block up its ancestors, and the
        shadow root ends that search. Nesting the positioned element below the
        layer keeps the search from leaving through the host and landing on a
        transformed ancestor the top layer has already escaped.
      -->
      <div
        class="overlay-layer"
        role="none"
        popover=${supportsPopover ? 'manual' : nothing}
      >
        <div class="overlay-position">
          <div
            class="popover-pane"
            part="popup"
            id=${dialog ? this.#paneId : nothing}
            role=${dialog ? 'dialog' : nothing}
            aria-label="${this.label ?? nothing}"
            @keyup=${this.#onEscape}
            @close-overlay=${this.#onCloseOverlay}
          >
            <slot name="content">
              <slot name="content-body"></slot>
              <slot name="content-footer"></slot>
            </slot>
          </div>
        </div>
      </div>
    `;
  }

  #onInvokerSlotChange = (): void => {
    if (this.#isSetUp) {
      this._syncInvoker();
    }
  };

  override connectedCallback() {
    this.#ensureContentWrapper();
    super.connectedCallback();

    // A popover moved in the DOM is disconnected and reconnected without
    // another first render.
    if (this.hasUpdated && !this.#isSetUp) {
      this.#setup();
    }
  }

  override disconnectedCallback() {
    super.disconnectedCallback();

    // Leaving the document drops the pane from the top layer. A popover that's
    // only being moved shows it again from where it lands.
    this.#hidePane({restoreFocus: false});

    void this.updateComplete.then(() => {
      if (this.isConnected) {
        if (this.opened) {
          this.#showPane();
        }
        return;
      }

      if (this.opened) {
        swallowHeldEnterKeyup();
      }

      this.#unbindInvoker();
      this._teardownOverlay();
      this.#isSetUp = false;
    });
  }

  protected override firstUpdated(changed: PropertyValues): void {
    super.firstUpdated(changed);
    this.#setup();
  }

  #setup(): void {
    this.#isSetUp = true;
    this._syncInvoker();
    this._setupOverlay();

    if (this.opened) {
      this.#showPane();
    }
  }

  /**
   * Popover content may be in the default slot rather than `slot="content"`,
   * so it's wrapped on connect. Whitespace-only default content is left alone:
   * a generated, empty `slot="content"` element would otherwise suppress the
   * shadow slot's `content-body`/`content-footer` fallback.
   */
  #ensureContentWrapper() {
    if (this.#contentWrapper?.isConnected) {
      return;
    }

    const defaultSlotNodes = Array.from(this.childNodes).filter((node) => {
      if (node instanceof Element) {
        return node.slot === '';
      }
      // A comment's text is in `textContent` too, but it renders nothing.
      return node instanceof Text && node.data.trim() !== '';
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

    if (!this.#isSetUp) {
      return;
    }

    if (changed.has('for') || changed.has('anchor')) {
      this._syncInvoker();
    }

    if (changed.has('withoutInvokerAria')) {
      this.#syncInvokerAria();
    }

    // The first render only reports the initial state.
    if (changed.has('opened') && changed.get('opened') !== undefined) {
      if (this.opened) {
        this.#showPane();
      } else {
        this.#hidePane();
      }
    }

    if (
      this.#popper &&
      (changed.has('placement') ||
        changed.has('distance') ||
        changed.has('matchInvokerWidth'))
    ) {
      this.#syncReferenceWidth();
      void this.#popper.setOptions(this.#popperOptions());
    }
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
