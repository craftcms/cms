/**
 * HUD — modern, jQuery-free TypeScript port of Garnish's `HUD`.
 *
 * A HUD ("heads-up display") is an anchored popover/bubble: it attaches to a
 * trigger element, picks one of four orientations (bottom / top / right / left)
 * based on the available clearance around the trigger, draws a tip/arrow that
 * points back at the trigger, and follows the trigger when the window or its
 * scroll container scrolls/resizes.
 *
 * This is the jQuery-free port of the legacy `HUD.js` (doc 13). It reuses the
 * same overlay infrastructure {@link Modal} already uses — the `UiLayerManager`
 * for layer stacking + the Escape shortcut, the focus utilities for the in/out
 * Tab cycle, and the native DOM utils that replace jQuery:
 *
 * - element creation/insertion → `document.createElement` + `appendChild` /
 *   `before` / `after`
 * - `$.data('hud', this)` → a module-level `WeakMap`
 * - `.scrollParent()` → {@link getScrollParent}
 * - `.offset()` / `Garnish.getOffset()` → an inline rect-based offset +
 *   {@link getOffset}
 * - `.outerWidth()` / `.outerHeight()` → {@link getOuterWidth} / {@link getOuterHeight}
 * - `:focusable` → {@link getFocusableElements} / {@link getKeyboardFocusableElements}
 * - `Garnish.within` → {@link within}
 * - `Garnish.requestAnimationFrame` → the native RAF re-export
 *
 * Unlike `Modal`, the legacy HUD does **not** fade with Velocity — its
 * `showContainer` / `hideContainer` are plain `.show()` / `.hide()` display
 * toggles, so there is no Velocity→WAAPI conversion to do here (doc 14).
 */

import {Base} from './base';
import {bod, globals, win} from './globals';
import {ESC_KEY, TAB_KEY} from './constants';
import {getUiLayerManager} from './managers/registry';
import {
  getElement,
  getOffset,
  getOuterHeight,
  getOuterWidth,
} from './utils/dom';
import {getScrollParent} from './utils/scroll';
import {focusIsInside, getKeyboardFocusableElements} from './utils/focus';
import {getFocusableElements} from './utils/focusable';
import {requestAnimationFrame} from './utils/animation';
import {isPlainObject, within} from './utils/misc';
import type {ElementInput, GarnishBaseSettings} from './types';
import type {GarnishEvent} from './events';

/** One of the four sides a HUD can be anchored on, relative to its trigger. */
export type HUDOrientation = 'top' | 'bottom' | 'left' | 'right';

/** Body contents accepted by the constructor / {@link HUD.updateBody}. */
export type HUDBodyContents =
  | string
  | Node
  | Node[]
  | NodeListOf<Node>
  | null
  | undefined;

type HUDCallback = (...args: unknown[]) => void;

const noop: HUDCallback = () => {};

/**
 * Settings accepted by {@link HUD}. Pass a `Partial<HUDSettings>` to the
 * constructor; unset keys fall back to {@link HUD.defaults}.
 */
export interface HUDSettings extends GarnishBaseSettings {
  /** CSS class for the shade/backdrop element. Default `'hud-shade'`. */
  shadeClass: string;
  /** CSS class(es) for the HUD container element. Default `'hud'`. */
  hudClass: string;
  /** CSS class for the tip/arrow element. Default `'tip'`. */
  tipClass: string;
  /** CSS class for the (form) body element. Default `'body'`. */
  bodyClass: string;
  /** CSS class identifying a header within the body content. Default `'hud-header'`. */
  headerClass: string;
  /** CSS class identifying a footer within the body content. Default `'hud-footer'`. */
  footerClass: string;
  /** CSS class for the scrollable main-content wrapper. Default `'main-container'`. */
  mainContainerClass: string;
  /** CSS class for the main-content element. Default `'main'`. */
  mainClass: string;
  /** Orientations to try, in order of preference. Default `['bottom','top','right','left']`. */
  orientations: HUDOrientation[];
  /** Gap (px) between the HUD and its trigger. Default `10`. */
  triggerSpacing: number;
  /** Min gap (px) between the HUD and the viewport edge. Default `10`. */
  windowSpacing: number;
  /** Width (px) of the tip/arrow. Default `30`. */
  tipWidth: number;
  /** Minimum HUD body width (px). Default `200`. */
  minBodyWidth: number;
  /** Minimum HUD body height (px). Default `0`. */
  minBodyHeight: number;
  /** Render a shade/backdrop behind the HUD. Default `true`. */
  withShade: boolean;
  /** Callback invoked when the HUD is shown. Default no-op. */
  onShow: HUDCallback;
  /** Callback invoked when the HUD is hidden. Default no-op. */
  onHide: HUDCallback;
  /** Callback invoked when the HUD's body form is submitted. Default no-op. */
  onSubmit: HUDCallback;
  /** An element whose `activate` should hide the HUD (e.g. a close button). Default `null`. */
  closeBtn: ElementInput | null;
  /** Reposition when the main-content element fires `resize`. Default `true`. */
  listenToMainResize: boolean;
  /** Show the HUD immediately on construction. Default `true`. */
  showOnInit: boolean;
  /** Hide every other open HUD when this one is shown. Default `true`. */
  closeOtherHUDs: boolean;
  /** Hide the HUD when Escape is pressed. Default `true`. */
  hideOnEsc: boolean;
  /** Hide the HUD when its shade is clicked (only with `withShade`). Default `true`. */
  hideOnShadeClick: boolean;
}

/** Mutable offset rectangle (page-relative) used by the positioning math. */
interface OffsetRect {
  top: number;
  left: number;
  right: number;
  bottom: number;
}

/**
 * Maps a HUD container element back to its `HUD` instance — the native
 * replacement for the legacy `$hud.data('hud', this)`.
 */
const hudElements = new WeakMap<Element, HUD>();

/**
 * An anchored, accessible popover — the jQuery-free TypeScript port of Garnish's
 * `HUD`. Attaches to a trigger element, picks the best of four orientations from
 * the available clearance, draws a tip pointing at the trigger, traps Tab focus
 * between the trigger and the HUD body, registers an Escape shortcut + UI layer
 * (via the shared `UiLayerManager`), and repositions on scroll/resize.
 *
 * The constructor accepts a trigger plus optional body contents and settings,
 * with a legacy-compatible param shift: `new HUD(trigger, settings)` is treated
 * as `new HUD(trigger, '', settings)` when the second argument is a plain object.
 *
 * Emits `show`, `hide`, `submit`, `updateSizeAndPosition`, and `destroy` events
 * (subscribe with {@link Base.on}). The `onShow` / `onHide` / `onSubmit` settings
 * callbacks are registered as handlers for those events at construction time.
 *
 * @example
 * ```ts
 * import {HUD} from '@craftcms/garnish';
 *
 * const trigger = document.querySelector('#add-btn')!;
 * const hud = new HUD(trigger, {
 *   hudClass: 'hud fld-library-hud',
 *   orientations: ['right', 'bottom', 'left'],
 *   showOnInit: false,
 * });
 * hud.on('show', () => populate(hud.$main!));
 * hud.on('hide', () => trigger.focus());
 * // trigger.addEventListener('click', () => hud.show());
 * ```
 */
export class HUD extends Base<HUDSettings> {
  /** Every live `HUD` instance (pushed on construction, removed on {@link destroy}). */
  static instances: HUD[] = [];

  /** Open HUDs keyed by their instance namespace (used by `closeOtherHUDs`). */
  static activeHUDs: Record<string, HUD> = {};

  /** Maps an orientation to the tip-class suffix (the tip points back at the trigger). */
  static readonly tipClasses: Record<HUDOrientation, string> = {
    bottom: 'top',
    top: 'bottom',
    right: 'left',
    left: 'right',
  };

  /** Default {@link HUDSettings}, merged with the per-instance overrides. */
  static readonly defaults: HUDSettings = {
    shadeClass: 'hud-shade',
    hudClass: 'hud',
    tipClass: 'tip',
    bodyClass: 'body',
    headerClass: 'hud-header',
    footerClass: 'hud-footer',
    mainContainerClass: 'main-container',
    mainClass: 'main',
    orientations: ['bottom', 'top', 'right', 'left'],
    triggerSpacing: 10,
    windowSpacing: 10,
    tipWidth: 30,
    minBodyWidth: 200,
    minBodyHeight: 0,
    withShade: true,
    onShow: noop,
    onHide: noop,
    onSubmit: noop,
    closeBtn: null,
    listenToMainResize: true,
    showOnInit: true,
    closeOtherHUDs: true,
    hideOnEsc: true,
    hideOnShadeClick: true,
  };

  // --- Instance state (named with the legacy `$`-prefix for consumer parity) ---

  /** The trigger/anchor element. */
  $trigger: HTMLElement | null = null;
  /** The nearest `position: fixed` ancestor of the trigger, if any (drives fixed positioning). */
  $fixedTriggerParent: HTMLElement | null = null;
  /** The HUD container element. */
  $hud: HTMLElement | null = null;
  /** The tip/arrow element. */
  $tip: HTMLElement | null = null;
  /** The HUD body — a `<form>` element. */
  $body: HTMLFormElement | null = null;
  /** The header pulled out of the body content, if present. */
  $header: HTMLElement | null = null;
  /** The footer pulled out of the body content, if present. */
  $footer: HTMLElement | null = null;
  /** The scrollable wrapper around {@link $main}. */
  $mainContainer: HTMLElement | null = null;
  /** The main content element (consumers append their content here). */
  $main: HTMLElement | null = null;
  /** The shade/backdrop element (only when `withShade`). */
  $shade: HTMLElement | null = null;
  /** The focusable element immediately after the trigger in the DOM, for the Tab cycle. */
  $nextFocusableElement: HTMLElement | null = null;

  /** Whether the HUD is currently shown. */
  showing = false;
  /** The currently chosen orientation, or `null` before the first positioning pass. */
  orientation: HUDOrientation | null = null;
  /** The currently applied tip class (e.g. `'tip-top'`), or `null`. */
  tipClass: string | null = null;

  // Reposition bookkeeping (legacy parity; powers the scroll-follow change detection).
  private updatingSizeAndPosition = false;
  windowWidth: number | null = null;
  windowHeight: number | null = null;
  scrollTop: number | null = null;
  scrollLeft: number | null = null;
  mainWidth: number | null = null;
  mainHeight: number | null = null;
  spWidth: number | null = null;
  spHeight: number | null = null;
  spScrollTop: number | null = null;
  spScrollLeft: number | null = null;

  /**
   * @param trigger - The trigger/anchor element (or selector/`ElementInput`).
   * @param bodyContents - Initial body contents (string HTML, node(s)), or a
   *   `Partial<HUDSettings>` object (param shift: when this is a plain object and
   *   no third arg is given, it is treated as the settings).
   * @param settings - Optional settings overrides (see {@link HUDSettings}).
   */
  constructor(
    trigger: ElementInput,
    bodyContents: HUDBodyContents | Partial<HUDSettings> = '',
    settings?: Partial<HUDSettings>
  ) {
    super();

    this.$trigger = (getElement(trigger) as HTMLElement | undefined) ?? null;

    // Param mapping: `new HUD(trigger, settings)` when the 2nd arg is a plain object.
    let contents: HUDBodyContents = '';
    if (settings === undefined && isPlainObject(bodyContents)) {
      settings = bodyContents as Partial<HUDSettings>;
    } else {
      contents = bodyContents as HUDBodyContents;
    }

    this.setSettings(settings, HUD.defaults);

    // Register the settings callbacks as event handlers (legacy behavior).
    this.on('show', this.settings!.onShow as never);
    this.on('hide', this.settings!.onHide as never);
    this.on('submit', this.settings!.onSubmit as never);

    this.$trigger?.setAttribute('aria-expanded', 'false');

    // Build the shade.
    if (this.settings!.withShade) {
      this.$shade = document.createElement('div');
      this.$shade.className = this.settings!.shadeClass;
    }

    // Build the HUD tree: hud > (tip, body > mainContainer > main).
    this.$hud = document.createElement('div');
    this.$hud.className = this.settings!.hudClass;
    hudElements.set(this.$hud, this);

    this.$tip = document.createElement('div');
    this.$tip.className = this.settings!.tipClass;
    this.$hud.appendChild(this.$tip);

    this.$body = document.createElement('form');
    this.$body.className = this.settings!.bodyClass;
    this.$hud.appendChild(this.$body);

    this.$mainContainer = document.createElement('div');
    this.$mainContainer.className = this.settings!.mainContainerClass;
    this.$body.appendChild(this.$mainContainer);

    this.$main = document.createElement('div');
    this.$main.className = this.settings!.mainClass;
    this.$mainContainer.appendChild(this.$main);

    this.updateBody(contents);

    // Detect a fixed-position ancestor → the HUD positions itself `fixed` too.
    let parent: Element | null = this.$trigger;
    while (parent && parent.nodeName !== 'HTML') {
      if (window.getComputedStyle(parent).position === 'fixed') {
        this.$fixedTriggerParent = parent as HTMLElement;
        break;
      }
      parent = (parent as HTMLElement).offsetParent;
    }

    this.$hud.style.position = this.$fixedTriggerParent ? 'fixed' : 'absolute';

    this.addListener(this.$body, 'submit', '_handleSubmit');

    if (this.settings!.withShade && this.settings!.hideOnShadeClick) {
      this.addListener(this.$shade!, 'click', 'hide');
    }

    if (this.settings!.closeBtn) {
      this.addListener(this.settings!.closeBtn, 'activate', 'hide');
    }

    this.addListener(win, 'resize', 'updateSizeAndPosition');
    if (!this.$fixedTriggerParent) {
      this.addListener(
        globals.scrollContainer,
        'scroll',
        'updateSizeAndPosition'
      );
    }
    if (this.settings!.listenToMainResize) {
      this.addListener(this.$main, 'resize', 'updateSizeAndPosition');
    }

    // Tabbing on the trigger while open should move focus into the HUD.
    if (this.$trigger) {
      this.addListener(this.$trigger, 'keydown', (event) => {
        const ev = event as unknown as KeyboardEvent;
        if (ev.keyCode === TAB_KEY && !ev.shiftKey && this.showing) {
          const focusable = getKeyboardFocusableElements(this.$hud!)[0];
          if (focusable) {
            ev.preventDefault();
            focusable.focus();
          }
        }
      });
    }

    // Manage focus wrapping within the HUD.
    this.addListener(this.$hud, 'keydown', (event) => {
      const ev = event as unknown as KeyboardEvent;
      if (ev.keyCode !== TAB_KEY) {
        return;
      }

      const focusable = getKeyboardFocusableElements(this.$hud!);
      const index = focusable.indexOf(ev.target as HTMLElement);

      if (index === 0 && ev.shiftKey) {
        ev.preventDefault();
        this.$trigger?.focus();
      } else if (
        index === focusable.length - 1 &&
        !ev.shiftKey &&
        this.$nextFocusableElement
      ) {
        ev.preventDefault();
        this.$nextFocusableElement.focus();
      }
    });

    if (this.settings!.showOnInit) {
      // Hide the HUD until it gets positioned to avoid a flash at (0,0).
      this.$hud.style.opacity = '0';
      this.show();
      this.$hud.style.opacity = '1';
    } else {
      bod.appendChild(this.$hud);
      this.hideContainer();
    }

    HUD.instances.push(this);
  }

  /**
   * Replace the HUD body contents, re-extracting any header/footer (matched by
   * `headerClass` / `footerClass`) and hoisting them out of the scrollable main
   * region.
   *
   * @param bodyContents - String HTML, a node, or a list of nodes.
   */
  updateBody(bodyContents: HUDBodyContents): void {
    // Cleanup.
    this.$main!.innerHTML = '';

    if (this.$header) {
      this.$hud!.classList.remove('has-header');
      this.$header.remove();
      this.$header = null;
    }

    if (this.$footer) {
      this.$hud!.classList.remove('has-footer');
      this.$footer.remove();
      this.$footer = null;
    }

    // Append the new body contents.
    appendContent(this.$main!, bodyContents);

    // Look for a header and footer (first match each).
    const header = this.$main!.querySelector<HTMLElement>(
      `.${this.settings!.headerClass}`
    );
    const footer = this.$main!.querySelector<HTMLElement>(
      `.${this.settings!.footerClass}`
    );

    if (header) {
      this.$mainContainer!.before(header);
      this.$header = header;
      this.$hud!.classList.add('has-header');
    }

    if (footer) {
      this.$mainContainer!.after(footer);
      this.$footer = footer;
      this.$hud!.classList.add('has-footer');
    }
  }

  /**
   * Show the HUD: append it (and the shade) to `<body>`, register the UI layer +
   * Escape shortcut, wire the trigger→HUD Tab cycle, emit `show`, and position
   * it against the trigger. No-op if already showing.
   *
   * @param ev - The triggering DOM event, if any; its propagation is stopped.
   */
  show(ev?: Event): void {
    if (ev && ev.stopPropagation) {
      ev.stopPropagation();
    }

    if (this.showing) {
      return;
    }

    if (this.settings!.closeOtherHUDs) {
      for (const hudID in HUD.activeHUDs) {
        if (!Object.prototype.hasOwnProperty.call(HUD.activeHUDs, hudID)) {
          continue;
        }
        HUD.activeHUDs[hudID]!.hide();
      }
    }

    // Blur the active element to prevent the page from jumping.
    if (
      document.activeElement &&
      document.activeElement !== document.body &&
      document.activeElement instanceof HTMLElement
    ) {
      document.activeElement.blur();
    }

    // Move it to the end of <body> for the highest sub-z-index.
    if (this.settings!.withShade) {
      bod.appendChild(this.$shade!);
      this.$shade!.style.display = 'block';
    }

    bod.appendChild(this.$hud!);
    this.$trigger?.setAttribute('aria-expanded', 'true');
    this.showContainer();

    this.showing = true;
    HUD.activeHUDs[this._namespace] = this;

    const manager = getUiLayerManager();
    manager?.addLayer(this.$hud ?? undefined);

    if (this.settings!.hideOnEsc) {
      manager?.registerShortcut(ESC_KEY, () => {
        this.hide();
      });
    }

    // Find the next focusable element after the trigger; Shift-Tab on it should
    // take focus back into the HUD.
    if (this.$trigger) {
      const focusable = getFocusableElements(bod);
      const triggerIndex = focusable.indexOf(this.$trigger);
      if (triggerIndex !== -1 && focusable.length > triggerIndex + 1) {
        this.$nextFocusableElement = focusable[triggerIndex + 1]!;
        this.addListener(this.$nextFocusableElement, 'keydown', (event) => {
          const kev = event as unknown as KeyboardEvent;
          if (kev.keyCode === TAB_KEY && kev.shiftKey) {
            const items = getKeyboardFocusableElements(this.$hud!);
            const last = items[items.length - 1];
            if (last) {
              kev.preventDefault();
              last.focus();
            }
          }
        });
      }
    }

    this.onShow();
    this.enable();

    if (this.updateRecords()) {
      // Prevent the browser from jumping.
      this.$hud!.style.top = `${this._scrollContainerScrollTop()}px`;
      this.updateSizeAndPosition(true);
    }
  }

  /** Reveal the HUD container (display toggle; legacy `$hud.show()`). */
  showContainer(): void {
    this.$hud!.style.display = 'block';
  }

  /**
   * Hook called when the HUD is shown: emits the `show` event. Override and call
   * `super.onShow()` to extend.
   */
  onShow(): void {
    this.trigger('show');
  }

  /**
   * Re-measure the window / scroll / main-content records, returning whether any
   * of them changed since the last measurement. Drives the scroll-follow
   * change-detection so repositioning only runs when something actually moved.
   */
  updateRecords(): boolean {
    let changed = false;

    changed =
      this.windowWidth !== (this.windowWidth = window.innerWidth) || changed;
    changed =
      this.windowHeight !== (this.windowHeight = window.innerHeight) || changed;
    changed =
      this.scrollTop !== (this.scrollTop = this._scrollContainerScrollTop()) ||
      changed;
    changed =
      this.scrollLeft !==
        (this.scrollLeft = this._scrollContainerScrollLeft()) || changed;
    changed =
      this.mainWidth !== (this.mainWidth = getOuterWidth(this.$main!)) ||
      changed;
    changed =
      this.mainHeight !== (this.mainHeight = getOuterHeight(this.$main!)) ||
      changed;

    const scrollParent = this.$trigger
      ? getScrollParent(this.$trigger)
      : globals.scrollContainer;
    if (
      scrollParent !== globals.scrollContainer &&
      scrollParent instanceof Element
    ) {
      changed =
        this.spWidth !== (this.spWidth = scrollParent.clientWidth) || changed;
      changed =
        this.spHeight !== (this.spHeight = scrollParent.clientHeight) ||
        changed;
      changed =
        this.spScrollTop !== (this.spScrollTop = scrollParent.scrollTop) ||
        changed;
      changed =
        this.spScrollLeft !== (this.spScrollLeft = scrollParent.scrollLeft) ||
        changed;
    }

    return changed;
  }

  /**
   * Schedule a reposition. Runs {@link updateSizeAndPositionInternal} on the next
   * animation frame when forced, or when {@link updateRecords} reports a change
   * and a reposition isn't already pending.
   *
   * @param force - Reposition unconditionally (used on show).
   */
  updateSizeAndPosition(force?: boolean | Event): void {
    if (
      force === true ||
      (this.updateRecords() && !this.updatingSizeAndPosition)
    ) {
      this.updatingSizeAndPosition = true;
      requestAnimationFrame(() => this.updateSizeAndPositionInternal());
    }
  }

  /**
   * The core 4-way positioning pass. Measures the trigger and HUD body, computes
   * the available clearance on each side, picks the first orientation (in
   * `orientations` order) that fits — falling back to the side with the most
   * clearance — then clamps the body within the viewport, centers the HUD on the
   * trigger, and positions the tip. Emits `updateSizeAndPosition` when done.
   */
  updateSizeAndPositionInternal(): void {
    const settings = this.settings!;
    const hud = this.$hud!;
    const tip = this.$tip!;
    const body = this.$body!;
    const mainContainer = this.$mainContainer!;

    // Window scroll + trigger metrics.
    let windowScrollLeft = window.scrollX;
    let windowScrollTop = window.scrollY;

    const triggerWidth = this.$trigger ? getOuterWidth(this.$trigger) : 0;
    const triggerHeight = this.$trigger ? getOuterHeight(this.$trigger) : 0;

    // Document-relative offset (jQuery `.offset()`).
    const triggerOffset: OffsetRect = this.$trigger
      ? jQueryOffset(this.$trigger)
      : {top: 0, left: 0, right: 0, bottom: 0};

    let scrollContainerTriggerOffset: OffsetRect;
    let scrollContainerScrollLeft: number;
    let scrollContainerScrollTop: number;

    if (this.$fixedTriggerParent) {
      triggerOffset.left -= windowScrollLeft;
      triggerOffset.top -= windowScrollTop;

      scrollContainerTriggerOffset = {...triggerOffset};

      windowScrollLeft = 0;
      windowScrollTop = 0;
      scrollContainerScrollLeft = 0;
      scrollContainerScrollTop = 0;
    } else {
      const off = this.$trigger ? getOffset(this.$trigger) : {top: 0, left: 0};
      scrollContainerTriggerOffset = {
        top: off.top,
        left: off.left,
        right: 0,
        bottom: 0,
      };
      scrollContainerScrollLeft = this._scrollContainerScrollLeft();
      scrollContainerScrollTop = this._scrollContainerScrollTop();
    }

    triggerOffset.right = triggerOffset.left + triggerWidth;
    triggerOffset.bottom = triggerOffset.top + triggerHeight;

    scrollContainerTriggerOffset.right =
      scrollContainerTriggerOffset.left + triggerWidth;
    scrollContainerTriggerOffset.bottom =
      scrollContainerTriggerOffset.top + triggerHeight;

    // Reset HUD sizing, then measure the body.
    hud.style.width = '';
    mainContainer.style.height = '';
    mainContainer.style.overflowX = '';
    mainContainer.style.overflowY = '';

    let hudBodyWidth = getContentWidth(body);
    let hudBodyHeight = getContentHeight(body);

    // Available clearance on each side of the trigger.
    const clearances: Record<HUDOrientation, number> = {
      bottom:
        this.windowHeight! +
        scrollContainerScrollTop -
        scrollContainerTriggerOffset.bottom,
      top: scrollContainerTriggerOffset.top - scrollContainerScrollTop,
      right:
        this.windowWidth! +
        scrollContainerScrollLeft -
        scrollContainerTriggerOffset.right,
      left: scrollContainerTriggerOffset.left - scrollContainerScrollLeft,
    };

    // Pick the first orientation with enough room, in order of preference.
    this.orientation = null;

    for (let i = 0; i < settings.orientations.length; i++) {
      const orientation = settings.orientations[i]!;
      const relevantSize =
        orientation === 'top' || orientation === 'bottom'
          ? hudBodyHeight
          : hudBodyWidth;

      if (
        clearances[orientation] -
          (settings.windowSpacing + settings.triggerSpacing) >=
        relevantSize
      ) {
        this.orientation = orientation;
        break;
      }

      if (
        !this.orientation ||
        clearances[orientation] > clearances[this.orientation]
      ) {
        // Fall back to the side with the most clearance so far.
        this.orientation = orientation;
      }
    }

    // Just in case.
    if (
      !this.orientation ||
      !['bottom', 'top', 'right', 'left'].includes(this.orientation)
    ) {
      this.orientation = 'bottom';
    }

    // Update the tip class.
    if (this.tipClass) {
      tip.classList.remove(this.tipClass);
    }
    this.tipClass = `${settings.tipClass}-${HUD.tipClasses[this.orientation]}`;
    tip.classList.add(this.tipClass);

    // Constrain the HUD body to the allowed size.
    let maxHudBodyWidth: number;
    let maxHudBodyHeight: number;

    if (this.orientation === 'top' || this.orientation === 'bottom') {
      maxHudBodyWidth = this.windowWidth! - settings.windowSpacing * 2;
      maxHudBodyHeight =
        clearances[this.orientation] -
        settings.windowSpacing -
        settings.triggerSpacing;
    } else {
      maxHudBodyWidth =
        clearances[this.orientation] -
        settings.windowSpacing -
        settings.triggerSpacing;
      maxHudBodyHeight = this.windowHeight! - settings.windowSpacing * 2;
    }

    if (maxHudBodyWidth < settings.minBodyWidth) {
      maxHudBodyWidth = settings.minBodyWidth;
    }
    if (maxHudBodyHeight < settings.minBodyHeight) {
      maxHudBodyHeight = settings.minBodyHeight;
    }

    if (
      hudBodyWidth > maxHudBodyWidth ||
      hudBodyWidth < settings.minBodyWidth
    ) {
      if (hudBodyWidth > maxHudBodyWidth) {
        hudBodyWidth = maxHudBodyWidth;
      } else {
        hudBodyWidth = settings.minBodyWidth;
      }

      hud.style.width = `${hudBodyWidth}px`;

      // Any horizontal overflow now?
      if (this.mainWidth! > maxHudBodyWidth) {
        mainContainer.style.overflowX = 'scroll';
      }

      // The height may have changed.
      hudBodyHeight = getContentHeight(body);
    }

    if (
      hudBodyHeight > maxHudBodyHeight ||
      hudBodyHeight < settings.minBodyHeight
    ) {
      if (hudBodyHeight > maxHudBodyHeight) {
        hudBodyHeight = maxHudBodyHeight;
      } else {
        hudBodyHeight = settings.minBodyHeight;
      }

      let mainHeight = hudBodyHeight;
      if (this.$header) {
        mainHeight -= getOuterHeight(this.$header);
      }
      if (this.$footer) {
        mainHeight -= getOuterHeight(this.$footer);
      }

      mainContainer.style.height = `${mainHeight}px`;

      // Any vertical overflow now?
      if (this.mainHeight! > mainHeight) {
        mainContainer.style.overflowY = 'scroll';
      }
    }

    // Position the HUD + tip.
    let triggerCenter: number;
    let left: number;
    let top: number;

    hud.style.borderTopLeftRadius = '';
    hud.style.borderTopRightRadius = '';
    hud.style.borderBottomRightRadius = '';
    hud.style.borderBottomLeftRadius = '';
    const borderRadius =
      parseInt(window.getComputedStyle(hud).borderRadius, 10) || 0;

    if (this.orientation === 'top' || this.orientation === 'bottom') {
      // Center horizontally.
      const maxLeft =
        this.windowWidth! +
        windowScrollLeft -
        (hudBodyWidth + settings.windowSpacing);
      const minLeft = windowScrollLeft + settings.windowSpacing;
      triggerCenter = triggerOffset.left + Math.round(triggerWidth / 2);
      left = triggerCenter - Math.round(hudBodyWidth / 2);

      if (left > maxLeft) {
        left = maxLeft;
      }
      if (left < minLeft) {
        left = minLeft;
      }

      hud.style.left = `${left}px`;

      const tipLeft = within(
        triggerCenter - left - settings.tipWidth / 2,
        0,
        hudBodyWidth - settings.tipWidth
      );
      tip.style.left = `${tipLeft}px`;
      tip.style.top = '';

      if (this.orientation === 'top') {
        top = triggerOffset.top - (hudBodyHeight + settings.triggerSpacing);
      } else {
        top = triggerOffset.bottom + settings.triggerSpacing;
      }
      hud.style.top = `${top}px`;

      const adjustRadius = this.orientation === 'top' ? 'bottom' : 'top';
      if (tipLeft < borderRadius) {
        this._setRadius(adjustRadius, 'left');
      } else if (tipLeft > hudBodyWidth - borderRadius - settings.tipWidth) {
        this._setRadius(adjustRadius, 'right');
      }
    } else {
      // Center vertically.
      const maxTop =
        this.windowHeight! +
        windowScrollTop -
        (hudBodyHeight + settings.windowSpacing);
      const minTop = windowScrollTop + settings.windowSpacing;
      triggerCenter = triggerOffset.top + Math.round(triggerHeight / 2);
      top = triggerCenter - Math.round(hudBodyHeight / 2);

      if (top > maxTop) {
        top = maxTop;
      }
      if (top < minTop) {
        top = minTop;
      }

      hud.style.top = `${top}px`;

      const tipTop = within(
        triggerCenter - top - settings.tipWidth / 2,
        0,
        hudBodyHeight - settings.tipWidth
      );
      tip.style.top = `${tipTop}px`;
      tip.style.left = '';

      if (this.orientation === 'left') {
        left = triggerOffset.left - (hudBodyWidth + settings.triggerSpacing);
      } else {
        left = triggerOffset.right + settings.triggerSpacing;
      }
      hud.style.left = `${left}px`;

      const adjustRadius = this.orientation === 'left' ? 'right' : 'left';
      if (tipTop < borderRadius) {
        this._setRadius('top', adjustRadius);
      } else if (tipTop > hudBodyHeight - borderRadius - settings.tipWidth) {
        this._setRadius('bottom', adjustRadius);
      }
    }

    this.updatingSizeAndPosition = false;
    this.trigger('updateSizeAndPosition');
  }

  /**
   * Hide the HUD: toggle the container off, restore focus to the trigger if focus
   * was inside, pop the UI layer, drop the Tab-cycle listener, and emit `hide`.
   * No-op if not currently showing.
   */
  hide(): void {
    if (!this.showing) {
      return;
    }

    this.disable();
    this.$trigger?.setAttribute('aria-expanded', 'false');
    this.hideContainer();

    if (this.settings!.withShade && this.$shade) {
      this.$shade.style.display = 'none';
    }

    this.showing = false;
    delete HUD.activeHUDs[this._namespace];
    getUiLayerManager()?.removeLayer();

    if (this.$hud && focusIsInside(this.$hud)) {
      this.$trigger?.focus();
    }

    if (this.$nextFocusableElement) {
      this.removeListener(this.$nextFocusableElement, 'keydown');
      this.$nextFocusableElement = null;
    }

    this.onHide();
  }

  /** Hide the HUD container (display toggle; legacy `$hud.hide()`). */
  hideContainer(): void {
    this.$hud!.style.display = 'none';
  }

  /**
   * Hook called when the HUD is hidden: emits the `hide` event. Override and call
   * `super.onHide()` to extend.
   */
  onHide(): void {
    this.trigger('hide');
  }

  /** Toggle the HUD's visibility. */
  toggle(): void {
    if (this.showing) {
      this.hide();
    } else {
      this.show();
    }
  }

  /** Submit the HUD (fires the `submit` event via {@link onSubmit}). */
  submit(): void {
    this.onSubmit();
  }

  /**
   * Hook called when the HUD's form is submitted: emits the `submit` event.
   * Override and call `super.onSubmit()` to extend.
   */
  onSubmit(): void {
    this.trigger('submit');
  }

  /**
   * Destroy the HUD: remove its container + shade from the DOM, drop it from
   * {@link HUD.instances} and {@link HUD.activeHUDs}, then run the base teardown
   * (emits `destroy`, removes all listeners). Override and call `super.destroy()`
   * to clean up extra state.
   */
  override destroy(): void {
    if (this.$hud) {
      hudElements.delete(this.$hud);
      this.$hud.remove();
    }

    if (this.settings!.withShade && this.$shade) {
      this.$shade.remove();
    }

    delete HUD.activeHUDs[this._namespace];
    HUD.instances = HUD.instances.filter((o) => o !== this);

    super.destroy();
  }

  // --- Internals ------------------------------------------------------------

  private _handleSubmit(ev: GarnishEvent): void {
    (ev as unknown as Event).preventDefault();
    this.submit();
  }

  /** The scroll container's vertical scroll offset (window or element). */
  private _scrollContainerScrollTop(): number {
    const sc = globals.scrollContainer;
    return sc === win ? window.scrollY : (sc as Element).scrollTop;
  }

  /** The scroll container's horizontal scroll offset (window or element). */
  private _scrollContainerScrollLeft(): number {
    const sc = globals.scrollContainer;
    return sc === win ? window.scrollX : (sc as Element).scrollLeft;
  }

  /** Set one of the HUD's corner border-radii to the legacy tip-clearance value. */
  private _setRadius(
    vertical: 'top' | 'bottom',
    horizontal: 'left' | 'right'
  ): void {
    const prop =
      vertical === 'top'
        ? horizontal === 'left'
          ? 'borderTopLeftRadius'
          : 'borderTopRightRadius'
        : horizontal === 'left'
          ? 'borderBottomLeftRadius'
          : 'borderBottomRightRadius';
    this.$hud!.style[prop] = '2px';
  }
}

/**
 * Append body contents to an element — the native replacement for jQuery
 * `.append()`. Strings are parsed as HTML; nodes are appended; lists are
 * appended in order. Empty/nullish content is a no-op.
 */
function appendContent(parent: HTMLElement, content: HUDBodyContents): void {
  if (content === null || content === undefined || content === '') {
    return;
  }

  if (typeof content === 'string') {
    parent.insertAdjacentHTML('beforeend', content);
  } else if (content instanceof Node) {
    parent.appendChild(content);
  } else {
    for (const node of Array.from(content as ArrayLike<Node>)) {
      if (node) {
        parent.appendChild(node);
      }
    }
  }
}

/** Document-relative offset rectangle (jQuery `.offset()` parity). */
function jQueryOffset(el: Element): OffsetRect {
  const rect = el.getBoundingClientRect();
  return {
    top: rect.top + window.scrollY,
    left: rect.left + window.scrollX,
    right: 0,
    bottom: 0,
  };
}

/**
 * Content-box width (jQuery `.width()` parity): the border-box width from
 * `getBoundingClientRect()` minus horizontal padding and border. Using the rect
 * (rather than `clientWidth`) keeps it mockable in layout-less test environments.
 */
function getContentWidth(el: HTMLElement): number {
  const cs = window.getComputedStyle(el);
  return (
    el.getBoundingClientRect().width -
    (parseFloat(cs.paddingLeft) || 0) -
    (parseFloat(cs.paddingRight) || 0) -
    (parseFloat(cs.borderLeftWidth) || 0) -
    (parseFloat(cs.borderRightWidth) || 0)
  );
}

/**
 * Content-box height (jQuery `.height()` parity): the border-box height from
 * `getBoundingClientRect()` minus vertical padding and border.
 */
function getContentHeight(el: HTMLElement): number {
  const cs = window.getComputedStyle(el);
  return (
    el.getBoundingClientRect().height -
    (parseFloat(cs.paddingTop) || 0) -
    (parseFloat(cs.paddingBottom) || 0) -
    (parseFloat(cs.borderTopWidth) || 0) -
    (parseFloat(cs.borderBottomWidth) || 0)
  );
}

export default HUD;
