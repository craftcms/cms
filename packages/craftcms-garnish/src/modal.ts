/**
 * Modal — modern, jQuery-free TypeScript port of Garnish's `Modal`.
 *
 * This is the vertical-slice PoC component. It replaces every jQuery call in the
 * legacy `Modal.js` (doc 03 §2) with native DOM:
 *
 * - element creation/insertion → `document.createElement` + `appendChild`/`insertBefore`
 * - class/attr/style → `classList` / `setAttribute` / `.style`
 * - dimensions → `offsetWidth`/`offsetHeight`
 * - window size → `window.innerWidth`/`innerHeight`
 * - display → `.style.display`
 * - data storage → a module-level `WeakMap`
 * - plain-object check → a local `isPlainObject`
 * - **animation** → the Web Animations API (`element.animate`), gated on
 *   `prefersReducedMotion`. `.velocity('stop')` → `cancel()` of the tracked
 *   animation.
 *
 * Draggable/resizable are deliberately out of PoC scope: `BaseDrag`/`DragMove`
 * are not in the modern core, so `draggable`/`resizable` default to `false` and
 * the relevant code paths throw a clear "not yet supported" error if turned on.
 */

import {Base} from './base';
import {bod, win} from './globals';
import {ESC_KEY, FX_DURATION} from './constants';
import {getUiLayerManager} from './managers/registry';
import {
  addModalAttributes,
  hideModalBackgroundLayers,
  resetModalBackgroundLayerVisibility,
} from './utils/aria';
import {
  getFocusedElement,
  setFocusWithin,
  trapFocusWithin,
  releaseFocusWithin,
} from './utils/focus';
import {prefersReducedMotion} from './utils/animation';
import type {GarnishBaseSettings} from './types';

/** Duration (ms) of the shade fade, matching legacy. */
const SHADE_FX_DURATION = 50;

type ModalCallback = () => void;

/** A focus target may be an element, or a function returning one. */
type FocusTarget =
  | Element
  | null
  | undefined
  | (() => Element | null | undefined);

/**
 * Settings accepted by {@link Modal}. Pass a `Partial<ModalSettings>` to the
 * constructor; unset keys fall back to {@link Modal.defaults}.
 */
export interface ModalSettings extends GarnishBaseSettings {
  /** Show the modal immediately on construction (when a container is given). Default `true`. */
  autoShow: boolean;
  /**
   * Allow dragging the modal by its handle.
   * @remarks Not yet supported in the modern build — enabling it throws. Default `false`.
   */
  draggable: boolean;
  /** Selector for the drag handle (only relevant when `draggable`). Default `null`. */
  dragHandleSelector: string | null;
  /**
   * Allow resizing the modal.
   * @remarks Not yet supported in the modern build — enabling it throws. Default `false`.
   */
  resizable: boolean;
  /** Minimum gutter (px) between the modal and the viewport edge. Default `10`. */
  minGutter: number;
  /** Callback invoked when the modal is shown. Default no-op. */
  onShow: ModalCallback;
  /** Callback invoked when the modal is hidden. Default no-op. */
  onHide: ModalCallback;
  /** Callback invoked after the modal finishes fading in. Default no-op. */
  onFadeIn: ModalCallback;
  /** Callback invoked after the modal finishes fading out. Default no-op. */
  onFadeOut: ModalCallback;
  /** Hide any other visible modal when this one is shown. Default `false`. */
  closeOtherModals: boolean;
  /** Hide the modal when the Escape key is pressed. Default `true`. */
  hideOnEsc: boolean;
  /** Hide the modal when its shade (backdrop) is clicked. Default `true`. */
  hideOnShadeClick: boolean;
  /**
   * The element (or a function returning one) to restore focus to when the modal
   * hides. Defaults to whatever was focused at construction time.
   */
  triggerElement: FocusTarget;
  /** CSS class applied to the shade/backdrop element. Default `'modal-shade'`. */
  shadeClass: string;
}

/**
 * Maps a modal container element back to its `Modal` instance — the native
 * replacement for the legacy `$container.data('modal', this)`.
 */
const containerModals = new WeakMap<Element, Modal>();

const noop: ModalCallback = () => {};

/**
 * An accessible, animated modal dialog — the jQuery-free TypeScript port of
 * Garnish's `Modal`, and the vertical-slice reference component for this package.
 *
 * Handles shade/backdrop, centered sizing within the viewport, fade in/out via
 * the Web Animations API (respecting `prefers-reduced-motion`), focus trapping
 * and restoration, ARIA backgrounding of sibling layers, and Escape /
 * shade-click dismissal. Emits `show`, `hide`, `fadeIn`, `fadeOut`,
 * `updateSizeAndPosition`, `escape`, and `destroy` events (subscribe with
 * {@link Base.on}).
 *
 * The constructor accepts a container element and/or a settings object, with a
 * legacy-compatible param shift: `new Modal(settings)` is treated as
 * `new Modal(null, settings)` when the first argument is a plain object.
 *
 * @remarks `draggable` and `resizable` are **not yet supported** in the modern
 * build (the drag system is out of PoC scope); both default to `false` and
 * enabling either throws a descriptive error.
 *
 * @example Modern usage
 * ```ts
 * import {Modal} from '@craftcms/garnish';
 *
 * const el = document.querySelector('#my-modal')!;
 * const modal = new Modal(el, {
 *   closeOtherModals: true,
 *   onShow: () => console.log('shown'),
 * });
 *
 * modal.on('hide', () => console.log('hidden'));
 * // modal.hide();  modal.destroy();
 * ```
 */
export class Modal extends Base<ModalSettings> {
  /** Every live `Modal` instance (pushed on construction, removed on {@link destroy}). */
  static instances: Modal[] = [];

  /** The currently visible modal, or `null` if none is showing. */
  static visibleModal: Modal | null = null;

  /** Default {@link ModalSettings}, merged with the per-instance overrides. */
  static readonly defaults: ModalSettings = {
    autoShow: true,
    draggable: false,
    dragHandleSelector: null,
    resizable: false,
    minGutter: 10,
    onShow: noop,
    onHide: noop,
    onFadeIn: noop,
    onFadeOut: noop,
    closeOtherModals: false,
    hideOnEsc: true,
    hideOnShadeClick: true,
    triggerElement: null,
    shadeClass: 'modal-shade',
  };

  /** Legacy parity constant. */
  static readonly relativeElemPadding = 8;

  // --- Instance state (named with the legacy `$`-prefix for consumer parity) ---

  /** The modal's container element, or `null` until {@link setContainer} runs. */
  $container: HTMLElement | null = null;
  /** The shade/backdrop element created for this modal. */
  $shade: HTMLElement | null = null;
  /** Overrides the focus-restore target; takes precedence over `settings.triggerElement`. */
  $triggerElement: Element | null = null;
  /** The visually-hidden `role="status"` live region appended to the container. */
  $liveRegion: HTMLElement;

  /** Whether the modal is currently shown. */
  visible = false;

  // Draggable/resizable are unsupported in the PoC; kept for shape parity.
  dragger: unknown = null;
  resizeDragger: unknown = null;
  resizeStartWidth: number | null = null;
  resizeStartHeight: number | null = null;

  desiredWidth: number | null = null;
  desiredHeight: number | null = null;

  // Tracked WAAPI animations so we can `cancel()` them (legacy `.velocity('stop')`).
  private _containerAnim: Animation | null = null;
  private _shadeAnim: Animation | null = null;

  /**
   * @param container - The modal's container element, or a `Partial<ModalSettings>`
   *   object (param shift: when the first arg is a plain object and no second
   *   arg is given, it is treated as the settings). May be `null` to construct
   *   a modal whose container is set later via {@link setContainer}.
   * @param settings - Optional settings overrides (see {@link ModalSettings}).
   */
  constructor(
    container?: Element | Partial<ModalSettings> | null,
    settings?: Partial<ModalSettings>
  ) {
    super();

    // Param mapping: `new Modal(settings)` when the first arg is a plain object.
    let containerEl: Element | null = null;
    if (settings === undefined && isPlainObject(container)) {
      settings = container as Partial<ModalSettings>;
    } else {
      containerEl = (container as Element | null) ?? null;
    }

    this.setSettings(settings, Modal.defaults);

    if (!this.settings!.triggerElement) {
      this.settings!.triggerElement = getFocusedElement();
    }

    // Live region announcement element.
    this.$liveRegion = document.createElement('span');
    this.$liveRegion.className = 'visually-hidden';
    this.$liveRegion.setAttribute('role', 'status');

    // Create the shade.
    this.$shade = document.createElement('div');
    this.$shade.className = this.settings!.shadeClass;

    // If the container is already set, drop the shade below it.
    if (containerEl && containerEl.parentNode) {
      containerEl.parentNode.insertBefore(this.$shade, containerEl);
    } else {
      bod.appendChild(this.$shade);
    }

    if (containerEl) {
      this.setContainer(containerEl);
      addModalAttributes(containerEl);

      if (this.settings!.autoShow) {
        this.show();
      }
    }

    Modal.instances.push(this);
  }

  /** Append the live region ({@link $liveRegion}) to the container, if set. */
  addLiveRegion(): void {
    if (!this.$container) {
      return;
    }
    this.$container.appendChild(this.$liveRegion);
  }

  /**
   * Assign (or replace) the modal's container element, wiring up its ARIA
   * attributes, live region, and shade-dismiss listeners. If another modal was
   * already attached to this element, it is destroyed first.
   *
   * @param container - The element to use as the modal container.
   * @throws If `draggable` or `resizable` is enabled (not yet supported).
   */
  setContainer(container: Element): void {
    this.$container = container as HTMLElement;

    // Already a modal? Tear the old one down (legacy double-instantiation guard).
    const existing = containerModals.get(container);
    if (existing) {
      console.warn('Double-instantiating a modal on an element');
      existing.destroy();
    }

    containerModals.set(container, this);

    if (this.settings!.draggable) {
      throw new Error(
        'Garnish.Modal: `draggable` is not yet supported in the modern build (DragMove/BaseDrag are out of PoC scope).'
      );
    }

    if (this.settings!.resizable) {
      throw new Error(
        'Garnish.Modal: `resizable` is not yet supported in the modern build (BaseDrag is out of PoC scope).'
      );
    }

    this.addLiveRegion();

    // The DOM-listener registry passes the native Event augmented with Garnish
    // fields (core impl note #2), so `stopPropagation` is available at runtime.
    this.addListener(this.$container, 'click', (ev) => {
      (ev as unknown as Event).stopPropagation();
    });

    // Show it if we're late to the party.
    if (this.visible) {
      this.show();
    }
  }

  /**
   * Show the modal: move it to the top of `<body>`, fade in the shade then the
   * container, trap focus, register the Escape shortcut, and emit `show` (and
   * later `fadeIn`). No-op visually if already visible, but always re-enables.
   */
  show(): void {
    // Close other modals as needed.
    if (
      this.settings!.closeOtherModals &&
      Modal.visibleModal &&
      Modal.visibleModal !== this
    ) {
      Modal.visibleModal.hide();
    }

    if (this.$container) {
      // Move shade + container to the end of <body> for the highest sub-z-index.
      this._cancelAnim('shade');
      this._cancelAnim('container');
      bod.appendChild(this.$shade!);
      bod.appendChild(this.$container);

      this.$container.style.display = 'block';
      this.updateSizeAndPosition();

      // Shade fade-in, then container fade-in.
      this._fade(this.$shade!, 'in', SHADE_FX_DURATION, 'shade', () => {
        this._fade(this.$container!, 'in', FX_DURATION, 'container', () => {
          this.updateSizeAndPosition();
          setFocusWithin(this.$container!);
          this.onFadeIn();
        });
      });

      if (this.settings!.hideOnShadeClick) {
        this.addListener(this.$shade!, 'click', 'hide');
      }

      // Focus trap.
      trapFocusWithin(this.$container);

      this.addListener(win, 'resize', '_handleWindowResize');
    }

    this.enable();

    if (!this.visible) {
      this.visible = true;
      Modal.visibleModal = this;

      const manager = getUiLayerManager();
      manager?.addLayer(this.$container ?? undefined);
      hideModalBackgroundLayers();

      if (this.settings!.hideOnEsc) {
        manager?.registerShortcut(ESC_KEY, () => {
          this.trigger('escape');
          this.hide();
        });
      }

      bod.classList.add('no-scroll');
      this.onShow();
    }
  }

  /**
   * Hook called when the modal is shown: emits the `show` event and invokes the
   * `onShow` setting. Override to react to showing (call `super.onShow()` to
   * preserve the event + callback).
   */
  onShow(): void {
    this.trigger('show');
    this.settings!.onShow();
  }

  /** Show the modal immediately, skipping the fade animation. */
  quickShow(): void {
    this.show();

    if (this.$container) {
      this._cancelAnim('container');
      this.$container.style.display = 'block';
      this.$container.style.opacity = '1';

      this._cancelAnim('shade');
      this.$shade!.style.display = 'block';
      this.$shade!.style.opacity = '1';
    }
  }

  /**
   * Hide the modal: fade out the container and shade, release the focus trap,
   * pop the UI layer, restore background ARIA, emit `hide` (and later
   * `fadeOut`), and restore focus to the trigger element shortly after. No-op
   * if not currently visible.
   *
   * @param ev - The triggering DOM event, if any; its propagation is stopped.
   */
  hide(ev?: Event): void {
    if (!this.visible) {
      return;
    }

    this.disable();

    if (ev) {
      ev.stopPropagation();
    }

    if (this.$container) {
      this._cancelAnim('container');
      this._fade(this.$container, 'out', FX_DURATION, 'container');

      this._cancelAnim('shade');
      this._fade(this.$shade!, 'out', FX_DURATION, 'shade', () => {
        this.onFadeOut();
      });

      if (this.settings!.hideOnShadeClick) {
        this.removeListener(this.$shade!, 'click');
      }

      releaseFocusWithin(this.$container);
      this.removeListener(win, 'resize');
    }

    this.visible = false;
    bod.classList.remove('no-scroll');
    Modal.visibleModal = null;

    const manager = getUiLayerManager();
    manager?.removeLayer();
    resetModalBackgroundLayerVisibility();
    this.onHide();

    setTimeout(() => {
      this._restoreTriggerFocus();
    }, 200);
  }

  /**
   * Hook called when the modal is hidden: emits the `hide` event and invokes the
   * `onHide` setting. Override to react to hiding (call `super.onHide()` to
   * preserve the event + callback).
   */
  onHide(): void {
    this.trigger('hide');
    this.settings!.onHide();
  }

  /** Hide the modal immediately, skipping the fade animation. */
  quickHide(): void {
    this.hide();

    if (this.$container) {
      this._cancelAnim('container');
      this.$container.style.opacity = '0';
      this.$container.style.display = 'none';

      this._cancelAnim('shade');
      this.$shade!.style.opacity = '0';
      this.$shade!.style.display = 'none';

      this.onFadeOut();
    }
  }

  /**
   * Recompute the modal's width, height, and centered position so it fits within
   * the viewport (honoring `minGutter` and {@link desiredWidth} /
   * {@link desiredHeight}), then emit `updateSizeAndPosition`. Called
   * automatically on show and window resize; call it manually after changing the
   * modal's content.
   */
  updateSizeAndPosition(): void {
    if (!this.$container) {
      return;
    }

    const c = this.$container;

    // Reset sizing, then measure.
    c.style.width = this.desiredWidth
      ? `${Math.max(this.desiredWidth, 200)}px`
      : '';
    c.style.height = this.desiredHeight
      ? `${Math.max(this.desiredHeight, 200)}px`
      : '';
    c.style.minWidth = '';
    c.style.minHeight = '';

    // Width first so height can adjust for the width.
    const windowWidth = window.innerWidth;
    const width = Math.min(
      this.getWidth(),
      windowWidth - this.settings!.minGutter * 2
    );

    c.style.width = `${width}px`;
    c.style.minWidth = `${width}px`;
    c.style.left = `${Math.round((windowWidth - width) / 2)}px`;

    // Now the height.
    const windowHeight = window.innerHeight;
    const height = Math.min(
      this.getHeight(),
      windowHeight - this.settings!.minGutter * 2
    );

    c.style.height = `${height}px`;
    c.style.minHeight = `${height}px`;
    c.style.top = `${Math.round((windowHeight - height) / 2)}px`;

    this.trigger('updateSizeAndPosition');
  }

  /**
   * Hook called after the fade-in completes: emits `fadeIn` and invokes the
   * `onFadeIn` setting. Override and call `super.onFadeIn()` to extend.
   */
  onFadeIn(): void {
    this.trigger('fadeIn');
    this.settings!.onFadeIn();
  }

  /**
   * Hook called after the fade-out completes: emits `fadeOut` and invokes the
   * `onFadeOut` setting. Override and call `super.onFadeOut()` to extend.
   */
  onFadeOut(): void {
    this.trigger('fadeOut');
    this.settings!.onFadeOut();
  }

  /**
   * Measure the container's rendered height (px), temporarily displaying it if
   * hidden.
   *
   * @returns The container's `offsetHeight`.
   * @throws If the container has not been set.
   */
  getHeight(): number {
    if (!this.$container) {
      throw 'Attempted to get the height of a modal whose container has not been set.';
    }

    const hidden = !this.visible;
    if (hidden) {
      this.$container.style.display = 'block';
    }

    const height = this.$container.offsetHeight;

    if (hidden) {
      this.$container.style.display = 'none';
    }

    return height;
  }

  /**
   * Measure the container's rendered width (px), temporarily displaying it if
   * hidden.
   *
   * @returns The container's `offsetWidth` (+1px, matching legacy Chrome parity).
   * @throws If the container has not been set.
   */
  getWidth(): number {
    if (!this.$container) {
      throw 'Attempted to get the width of a modal whose container has not been set.';
    }

    const hidden = !this.visible;
    if (hidden) {
      this.$container.style.display = 'block';
    }

    // Chrome might be 1px shy here for some reason (legacy parity).
    const width = this.$container.offsetWidth + 1;

    if (hidden) {
      this.$container.style.display = 'none';
    }

    return width;
  }

  /**
   * Destroy the modal: remove its container and shade from the DOM, drop it from
   * {@link Modal.instances} (and clear {@link Modal.visibleModal} if it was the
   * visible one), then run the base teardown (emits `destroy`, removes all
   * listeners). Override and call `super.destroy()` to clean up extra state.
   */
  override destroy(): void {
    if (this.$container) {
      containerModals.delete(this.$container);
      this.$container.remove();
    }

    if (this.$shade) {
      this.$shade.remove();
    }

    Modal.instances = Modal.instances.filter((o) => o !== this);

    if (Modal.visibleModal === this) {
      Modal.visibleModal = null;
    }

    super.destroy();
  }

  // --- Internals ------------------------------------------------------------

  private _handleWindowResize(ev: Event): void {
    // Ignore propagated resize events.
    if (ev.target === window) {
      this.updateSizeAndPosition();
    }
  }

  /**
   * Restore focus to the trigger element after a hide (legacy behavior).
   */
  private _restoreTriggerFocus(): void {
    let target: FocusTarget =
      this.$triggerElement ?? this.settings!.triggerElement;

    if (typeof target === 'function') {
      target = target();
    }

    let el: Element | null = (target as Element | null) ?? null;

    // If the trigger is hidden, try its disclosure-menu controller.
    if (el && isElementHidden(el)) {
      const disclosure = el.closest('.menu--disclosure');
      if (disclosure && disclosure.id) {
        el = document.querySelector(`[aria-controls="${disclosure.id}"]`);
      } else {
        el = null;
      }
    }

    if (el && el instanceof HTMLElement) {
      el.focus();
    } else {
      console.warn(
        'There is no trigger element set for this modal. Set one with modal.$triggerElement = ...'
      );
    }
  }

  /**
   * Fade an element in or out with the Web Animations API.
   *
   * Gated on `prefersReducedMotion` (and on environments lacking
   * `element.animate`, e.g. happy-dom): in those cases the end state is applied
   * immediately and the completion callback runs synchronously, so tests are
   * deterministic and reduced-motion users get an instant transition while real
   * browsers still animate.
   */
  private _fade(
    el: HTMLElement,
    direction: 'in' | 'out',
    duration: number,
    track: 'container' | 'shade',
    complete?: ModalCallback
  ): void {
    const finalOpacity = direction === 'in' ? '1' : '0';

    if (direction === 'in') {
      el.style.display = 'block';
    }

    const finalize = (): void => {
      el.style.opacity = finalOpacity;
      if (direction === 'out') {
        el.style.display = 'none';
      }
      this._setAnim(track, null);
      complete?.();
    };

    // Feature-detect WAAPI; fall back to an immediate-complete path otherwise.
    // happy-dom does not implement `element.animate`, so this keeps tests
    // deterministic while letting real browsers animate.
    if (prefersReducedMotion() || typeof el.animate !== 'function') {
      finalize();
      return;
    }

    el.style.opacity = direction === 'in' ? '0' : '1';
    const anim = el.animate(
      [
        {opacity: direction === 'in' ? 0 : 1},
        {opacity: direction === 'in' ? 1 : 0},
      ],
      {duration, fill: 'forwards'}
    );
    this._setAnim(track, anim);
    anim.onfinish = finalize;
    anim.oncancel = (): void => {
      this._setAnim(track, null);
    };
  }

  private _setAnim(track: 'container' | 'shade', anim: Animation | null): void {
    if (track === 'container') {
      this._containerAnim = anim;
    } else {
      this._shadeAnim = anim;
    }
  }

  /** Cancel the tracked animation for a target (legacy `.velocity('stop')`). */
  private _cancelAnim(track: 'container' | 'shade'): void {
    const anim = track === 'container' ? this._containerAnim : this._shadeAnim;
    if (anim) {
      anim.cancel();
    }
    this._setAnim(track, null);
  }
}

function isPlainObject(val: unknown): val is Record<string, unknown> {
  return (
    typeof val === 'object' &&
    val !== null &&
    !(val instanceof Element) &&
    (Object.getPrototypeOf(val) === Object.prototype ||
      Object.getPrototypeOf(val) === null)
  );
}

/**
 * Native replacement for jQuery `:hidden`: an element is hidden if it has no
 * rendered boxes. In layout-less environments (happy-dom) this falls back to a
 * computed-style check.
 */
function isElementHidden(el: Element): boolean {
  const html = el as HTMLElement;
  if (html.offsetWidth || html.offsetHeight || el.getClientRects().length) {
    return false;
  }
  const style = window.getComputedStyle(el);
  return style.display === 'none' || style.visibility === 'hidden';
}

export default Modal;
