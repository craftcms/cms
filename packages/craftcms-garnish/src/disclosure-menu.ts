/**
 * DisclosureMenu — modern, jQuery-free TypeScript port of Garnish's
 * `DisclosureMenu`.
 *
 * A disclosure menu is a trigger button (carrying `aria-controls` /
 * `aria-expanded`) paired with a menu panel of items/groups. Unlike {@link HUD},
 * the panel is **pre-existing markup** referenced by the trigger's
 * `aria-controls` (not built by the component); the component anchors it under
 * the trigger, manages keyboard navigation + type-ahead search, focus, and the
 * `UiLayerManager` layer + Escape shortcut, and exposes item/group builders
 * (`addItem` / `addGroup` / `addHr`) that consumers across the CP rely on.
 *
 * This is the jQuery-free port of the legacy `DisclosureMenu.js` (doc 15). It
 * follows the same overlay patterns {@link HUD}/{@link Modal} already use and
 * replaces every jQuery call with native DOM:
 *
 * - `$(trigger)` → {@link getElement}
 * - `$('#id')` / `.next()` → `document.getElementById` / `nextElementSibling`
 * - `.data('disclosureMenu', …)` → a module-level `WeakMap`
 * - `:focusable` → {@link getFocusableElements} / {@link isFocusable}
 * - `.scrollParent()` → {@link getScrollParent}
 * - `.offset()` / `.outerWidth()/.outerHeight()` → rect-based helpers +
 *   {@link getOuterWidth} / {@link getOuterHeight}
 * - `.velocity('fadeOut')` → the Web Animations API (gated on
 *   {@link prefersReducedMotion}, like {@link Modal})
 * - `$o.data('searchText')` (type-ahead cache) → a module-level `WeakMap`
 * - `.find/closest/filter/parent/prevUntil/nextUntil` → native DOM walks
 *
 * The few legacy Craft touchpoints (`Craft.getUrl` / `Craft.t` /
 * `Craft.initUiElements`) are resolved through an optional global so the
 * component runs standalone (tests/playground) and defers to Craft when present
 * in the CP. The legacy `$(el).formsubmit()` jQuery registration is intentionally
 * not called here (it would re-introduce jQuery); the `formsubmit` class +
 * `data-action` attributes are still set so the CP's form-submit machinery picks
 * the item up (doc 16).
 */

import {Base} from './base';
import {bod, doc, globals, win} from './globals';
import {
  DOWN_KEY,
  ESC_KEY,
  LEFT_KEY,
  RETURN_KEY,
  RIGHT_KEY,
  SPACE_KEY,
  TAB_KEY,
  UP_KEY,
} from './constants';
import {getUiLayerManager} from './managers/registry';
import {
  getElement,
  getOffset,
  getOuterHeight,
  getOuterWidth,
} from './utils/dom';
import {getScrollParent} from './utils/scroll';
import {focusIsInside} from './utils/focus';
import {getFocusableElements, isFocusable} from './utils/focusable';
import {prefersReducedMotion} from './utils/animation';
import {isCtrlKeyPressed} from './utils/env';
import {isPlainObject} from './utils/misc';
import type {ElementInput, GarnishBaseSettings} from './types';
import type {GarnishEvent} from './events';

/** Duration (ms) of the menu fade-out, matching legacy `FX_DURATION`. */
const FADE_OUT_DURATION = 200;

/**
 * Settings accepted by {@link DisclosureMenu}. Pass a
 * `Partial<DisclosureMenuSettings>` to the constructor; unset keys fall back to
 * {@link DisclosureMenu.defaults}.
 */
export interface DisclosureMenuSettings extends GarnishBaseSettings {
  /**
   * Force the menu below the trigger regardless of clearance. `'below'` pins it
   * below; any other value (or `null`) lets the clearance logic choose
   * above/below. Default `null`.
   */
  position: string | null;
  /** Min gap (px) between the menu and the viewport edge. Default `5`. */
  windowSpacing: number;
  /**
   * Render a search input at the top of the menu that filters items by text.
   * Also enabled when the container carries `data-with-search-input`. Default
   * `false`.
   */
  withSearchInput: boolean;
}

/**
 * A single item configuration accepted by {@link DisclosureMenu.addItem} /
 * {@link DisclosureMenu.createItem}. An already-built `Element` is also accepted
 * and returned as-is.
 */
export interface DisclosureMenuItemConfig {
  /** `'button'` (default) or `'link'`. Inferred as `'link'` when `url` is set. */
  type?: 'button' | 'link';
  /** The link href (implies `type: 'link'`); run through `Craft.getUrl` when present. */
  url?: string;
  /** Explicit element id; a unique `menu-item-*` id is generated when omitted. */
  id?: string;
  /** Mark the item selected (adds the `sel` class). */
  selected?: boolean;
  /** Mark the item destructive (adds `error` + `data-destructive`). */
  destructive?: boolean;
  /** Disable the item (adds the `disabled` class). */
  disabled?: boolean;
  /** A Craft controller action (adds `formsubmit` + `data-action`). */
  action?: string;
  /** An icon: a `data-icon` name string, an `Element`, or a (async) factory. */
  icon?: string | Element | (() => Promise<Element> | Element);
  /** A color class applied alongside the icon. */
  iconColor?: string;
  /** Action params, as a query string or a JSON-serializable object. */
  params?: string | Record<string, unknown>;
  /** A `data-confirm` confirmation message. */
  confirm?: string;
  /** A `data-redirect` target. */
  redirect?: string;
  /** Extra attributes to set on the item element. */
  attributes?: Record<string, string>;
  /** A status indicator class (renders a `status <status>` element). */
  status?: string;
  /** The item label (text). */
  label?: string;
  /** The item label as raw HTML (used when `label` is absent). */
  html?: string;
  /** A secondary description line. */
  description?: string;
  /** Start the item hidden. */
  hidden?: boolean;
  /** Invoked (with the item element) when the item is activated. */
  onActivate?: (el: HTMLElement) => void;
  /** Legacy alias for {@link onActivate}. */
  callback?: (el: HTMLElement) => void;
}

/** An item passed to {@link DisclosureMenu.addItem}: a config object or an element. */
export type DisclosureMenuItem = DisclosureMenuItemConfig | Element;

/** The optional Craft global the legacy item builder leaned on. */
interface CraftLike {
  getUrl?(url: string): string;
  t?(category: string, message: string): string;
  initUiElements?(el: Element): void;
}

/** Resolve the Craft global, if present (CP runtime); `undefined` standalone. */
function getCraft(): CraftLike | undefined {
  return (globalThis as {Craft?: CraftLike}).Craft;
}

/** `Craft.getUrl(url)` when available, else the url unchanged. */
function craftUrl(url: string): string {
  return getCraft()?.getUrl?.(url) ?? url;
}

/** `Craft.t('app', message)` when available, else the message unchanged. */
function craftT(message: string): string {
  return getCraft()?.t?.('app', message) ?? message;
}

/**
 * Maps a trigger / container element back to its `DisclosureMenu` — the native
 * replacement for the legacy `$el.data('disclosureMenu', this)`. Read it with
 * {@link DisclosureMenu.getInstance}.
 */
const menuElements = new WeakMap<Element, DisclosureMenu>();

/** Type-ahead search-text cache per `<li>` (legacy `$o.data('searchText')`). */
const searchTextCache = new WeakMap<HTMLElement, string>();

/** Monotonic id source for generated `menu-item-*` ids (deterministic; was `Math.random`). */
let itemIdCounter = 0;

/**
 * An accessible disclosure dropdown/menu — the jQuery-free TypeScript port of
 * Garnish's `DisclosureMenu`. Pairs a trigger button (`aria-controls` /
 * `aria-expanded`) with a pre-existing menu panel, anchoring it under the
 * trigger with smart above/below + left/center/right placement, full keyboard
 * navigation (arrows / Home-less Tab cycle) + type-ahead search, focus
 * management, a `UiLayerManager` layer + Escape shortcut, optional search input,
 * and item/group builders.
 *
 * Emits `beforeShow`, `show`, `hide`, and `destroy` events (subscribe with
 * {@link Base.on}). Item selection is per-item: each item's `onActivate` /
 * `callback` runs on `activate`, then the menu hides.
 *
 * @example
 * ```ts
 * import {DisclosureMenu} from '@craftcms/garnish';
 *
 * // <button aria-controls="menu1" aria-expanded="false">Actions</button>
 * // <div id="menu1" class="menu menu--disclosure"><ul>…</ul></div>
 * const menu = new DisclosureMenu(document.querySelector('button')!);
 * menu.addItem({label: 'Rename', onActivate: () => rename()});
 * menu.on('show', () => console.log('opened'));
 * ```
 */
export class DisclosureMenu extends Base<DisclosureMenuSettings> {
  /** Every live `DisclosureMenu` (pushed on construction, removed on {@link destroy}). */
  static instances: DisclosureMenu[] = [];

  /** Default {@link DisclosureMenuSettings}, merged with the per-instance overrides. */
  static readonly defaults: DisclosureMenuSettings = {
    position: null,
    windowSpacing: 5,
    withSearchInput: false,
  };

  /**
   * The `DisclosureMenu` registered for a trigger or container element, if any —
   * the native replacement for the legacy `$el.data('disclosureMenu')`.
   */
  static getInstance(
    el: Element | null | undefined
  ): DisclosureMenu | undefined {
    return el ? menuElements.get(el) : undefined;
  }

  // --- Instance state (legacy `$`-prefixed names for consumer parity) ---

  /** The trigger/anchor element. */
  $trigger: HTMLElement | null = null;
  /** The menu panel element (referenced by the trigger's `aria-controls`). */
  $container: HTMLElement | null = null;
  /** The element the menu aligns to (the trigger, or a `data-align-to` descendant). */
  $alignmentElement: HTMLElement | null = null;
  /** The focusable element immediately after the trigger in the DOM, for the Tab cycle. */
  $nextFocusableElement: HTMLElement | null = null;
  /** The optional search input (only when `withSearchInput`). */
  $searchInput: HTMLInputElement | null = null;

  /** The current type-ahead search buffer. */
  searchStr = '';
  /** The pending timeout that clears {@link searchStr}, or `null`. */
  clearSearchStrTimeout: ReturnType<typeof setTimeout> | null = null;
  /** Tracks an info-icon mousedown so it isn't treated as an outside click. */
  infoIconActivated = false;

  // Tracked WAAPI fade-out so it can be cancelled on re-show (legacy `.velocity('stop')`).
  private _anim: Animation | null = null;
  // The trigger's scroll parent listened to while open (removed on hide).
  private _scrollParent: Element | null = null;

  // Transient positioning measurements (set per `setContainerPosition` pass).
  private _vpWidth = 0;
  private _vpScrollLeft = 0;
  private _alignOffsetLeft = 0;
  private _alignWidth = 0;
  private _menuWidth = 0;

  /**
   * @param trigger - The trigger element (or selector / `ElementInput`). Must
   *   carry `aria-controls` pointing at the menu panel (or have it as its next
   *   sibling).
   * @param settings - Optional settings overrides (see {@link DisclosureMenuSettings}).
   */
  constructor(
    trigger: ElementInput,
    settings?: Partial<DisclosureMenuSettings>
  ) {
    super();

    this.setSettings(settings, DisclosureMenu.defaults);

    this.$trigger = (getElement(trigger) as HTMLElement | undefined) ?? null;

    // Double-instantiation guard (legacy `$trigger.data('trigger')`).
    if (this.$trigger && menuElements.has(this.$trigger)) {
      console.warn('Double-instantiating a disclosure menu on an element');
      return;
    }

    this.$trigger?.setAttribute('data-disclosure-trigger', 'true');

    // Resolve the container from `aria-controls` (or the next sibling).
    const containerId = this.$trigger?.getAttribute('aria-controls') ?? null;
    let container: HTMLElement | null = containerId
      ? document.getElementById(containerId)
      : null;
    if (!container && containerId) {
      const next = this.$trigger?.nextElementSibling;
      if (next instanceof HTMLElement && next.id === containerId) {
        container = next;
      }
    }
    if (!container) {
      throw 'No disclosure container found.';
    }
    this.$container = container;

    if (this.$trigger) {
      menuElements.set(this.$trigger, this);
    }
    menuElements.set(this.$container, this);

    // Ensure the trigger advertises a collapsed state for a11y.
    if (!this.$trigger?.getAttribute('aria-expanded')) {
      this.$trigger?.setAttribute('aria-expanded', 'false');
    }

    this.captureAlignmentElement();

    bod.appendChild(this.$container);

    // If the trigger lives in a slideout, (re)initialize its UI elements.
    if (this.$trigger?.closest('.slideout')) {
      getCraft()?.initUiElements?.(this.$container);
    }

    this.addDisclosureMenuEventListeners();

    // Add a search input?
    this.settings!.withSearchInput =
      this.settings!.withSearchInput ||
      this.$container.hasAttribute('data-with-search-input');
    if (this.settings!.withSearchInput) {
      this.addSearchInput();
    }

    DisclosureMenu.instances.push(this);
  }

  /**
   * (Re)capture the alignment element: the `data-align-to` descendant of the
   * trigger, if set, otherwise the trigger itself.
   */
  captureAlignmentElement(): void {
    const alignmentSelector = this.$container?.getAttribute('data-align-to');
    if (alignmentSelector && this.$trigger) {
      this.$alignmentElement =
        this.$trigger.querySelector<HTMLElement>(alignmentSelector) ??
        this.$trigger;
    } else {
      this.$alignmentElement = this.$trigger;
    }
  }

  /** Build and wire the optional search input at the top of the menu. */
  addSearchInput(): void {
    const outer = document.createElement('div');
    outer.className = 'search-container';
    this.$container!.prepend(outer);

    const inner = document.createElement('div');
    inner.className = 'texticon search icon clearable';
    outer.appendChild(inner);

    this.$searchInput = document.createElement('input');
    this.$searchInput.className = 'fullwidth text';
    this.$searchInput.type = 'text';
    this.$searchInput.setAttribute('inputmode', 'search');
    this.$searchInput.autocomplete = 'off';
    this.$searchInput.placeholder = craftT('Search');
    inner.appendChild(this.$searchInput);

    const clearBtn = document.createElement('div');
    clearBtn.className = 'clear-btn hidden';
    clearBtn.title = craftT('Clear');
    clearBtn.setAttribute('aria-label', craftT('Clear'));
    inner.appendChild(clearBtn);

    this.addListener(this.$searchInput, 'input', () => {
      this._applySearchFilter(this.$searchInput!.value, clearBtn);
    });

    this.addListener(this.$searchInput, 'keydown', (event) => {
      const ev = event as unknown as KeyboardEvent;
      switch (ev.keyCode) {
        case ESC_KEY:
          this.$searchInput!.value = '';
          this._applySearchFilter('', clearBtn);
          break;
        case RETURN_KEY:
          // They most likely don't want to submit the form from here.
          ev.preventDefault();
          break;
      }
    });

    this.addListener(clearBtn, 'click', () => {
      this.$searchInput!.value = '';
      this._applySearchFilter('', clearBtn);
      this.$searchInput!.focus();
    });
  }

  /** Apply the search filter to the menu items (factored out of the input handler). */
  private _applySearchFilter(rawValue: string, clearBtn: HTMLElement): void {
    const val = rawValue.toLowerCase().replace(/['"]/g, '');
    const options = Array.from(
      this.$container!.querySelectorAll<HTMLElement>('li')
    );

    if (val) {
      clearBtn.classList.remove('hidden');
      const matches = new Set<HTMLElement>();
      for (const option of options) {
        if ((option.textContent ?? '').toLowerCase().includes(val)) {
          matches.add(option);
        }
      }
      for (const option of options) {
        if (matches.has(option)) {
          option.classList.remove('filtered');
        } else {
          option.classList.add('filtered');
        }
      }

      // Also match the headings: a matching <h3> un-filters its group's items.
      for (const heading of Array.from(
        this.$container!.querySelectorAll<HTMLElement>('h3')
      )) {
        if ((heading.textContent ?? '').toLowerCase().includes(val)) {
          const ul = heading.nextElementSibling;
          if (ul && ul.tagName === 'UL') {
            ul.querySelectorAll('li.filtered').forEach((li) =>
              li.classList.remove('filtered')
            );
          }
        }
      }
    } else {
      clearBtn.classList.add('hidden');
      for (const option of options) {
        option.classList.remove('filtered');
      }
    }

    this.updateVisibility();
    this.setContainerPosition();
  }

  /** Wire the trigger / container / document event listeners (legacy parity). */
  addDisclosureMenuEventListeners(): void {
    this.addListener(this.$trigger, 'mousedown', (event) => {
      const ev = event as unknown as MouseEvent;
      ev.stopPropagation();
      ev.preventDefault();

      // Let the other disclosure menus know about it, at least.
      for (const disclosureMenu of DisclosureMenu.instances) {
        if (disclosureMenu !== this) {
          disclosureMenu.handleMousedown(ev);
        }
      }
    });

    this.addListener(this.$trigger, 'mouseup', (event) => {
      const ev = event as unknown as MouseEvent;
      ev.stopPropagation();
      ev.preventDefault();
    });

    this.addListener(this.$trigger, 'click', (event) => {
      const ev = event as unknown as MouseEvent;
      ev.stopPropagation();
      ev.preventDefault();
      this.handleTriggerClick();
    });

    this.addListener(this.$container, 'keydown', (event) => {
      this.handleKeypress(event as unknown as KeyboardEvent);
    });

    this.addListener(doc, 'mousedown', (event) => {
      this.handleMousedown(event as unknown as MouseEvent);
    });

    // When the menu is expanded, tabbing on the trigger should move focus into it.
    this.addListener(this.$trigger, 'keydown', (event) => {
      const ev = event as unknown as KeyboardEvent;
      if (ev.keyCode === TAB_KEY && !ev.shiftKey && this.isExpanded()) {
        const focusable = getFocusableElements(this.$container!)[0];
        if (focusable) {
          ev.preventDefault();
          focusable.focus();
        }
      }
    });
  }

  /**
   * Focus a menu element. With an `HTMLElement`, focuses it (or its first
   * focusable descendant). With `'prev'`/`'next'`, moves focus to the
   * previous/next focusable element within the container.
   */
  focusElement(component: HTMLElement | 'prev' | 'next'): void {
    if (component instanceof HTMLElement) {
      if (isFocusable(component)) {
        component.focus();
      } else {
        getFocusableElements(component)[0]?.focus();
      }
      return;
    }

    const focusable = getFocusableElements(this.$container!);
    const currentIndex = focusable.indexOf(
      document.activeElement as HTMLElement
    );
    const newIndex = component === 'prev' ? currentIndex - 1 : currentIndex + 1;

    if (newIndex >= 0 && newIndex < focusable.length) {
      focusable[newIndex]!.focus();
    }
  }

  /** Outside-click handler: hide the menu unless the click was inside it / its trigger. */
  handleMousedown(event: MouseEvent): void {
    const target = event.target as Element | null;

    // If the info icon was previously activated, reset and ignore this mousedown.
    if (this.infoIconActivated) {
      this.infoIconActivated = false;
      return;
    }

    if (target && (target as HTMLElement).classList?.contains('info')) {
      this.infoIconActivated = true;
    }

    // Click on this disclosure's trigger → do nothing.
    const trigger = target?.closest('[data-disclosure-trigger]') ?? null;
    if (trigger && trigger === this.$trigger) {
      return;
    }

    // Click inside this disclosure's container → do nothing.
    if (
      this.$container === event.target ||
      (target && this.$container!.contains(target))
    ) {
      return;
    }

    // Click inside a nested disclosure whose trigger lives in this menu → do nothing.
    const nested = target?.closest('.menu--disclosure') ?? null;
    if (nested) {
      const disclosure = menuElements.get(nested);
      if (
        disclosure &&
        disclosure.$trigger &&
        this.$container!.contains(disclosure.$trigger)
      ) {
        return;
      }
    }

    this.hide();
  }

  /** Keyboard handler bound to the container: arrows, Tab cycle, and type-ahead. */
  handleKeypress(ev: KeyboardEvent): void {
    if (isCtrlKeyPressed(ev)) {
      return;
    }

    switch (ev.keyCode) {
      case RIGHT_KEY:
      case DOWN_KEY:
        ev.preventDefault();
        this.focusElement('next');
        return;
      case LEFT_KEY:
      case UP_KEY:
        ev.preventDefault();
        this.focusElement('prev');
        return;
      case TAB_KEY: {
        const focusable = getFocusableElements(this.$container!);
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
        return;
      }
    }

    // Type-ahead search: a printable key (or space, mid-search) focuses the first
    // matching option.
    if (
      (ev.target as Element).nodeName !== 'INPUT' &&
      ev.key &&
      (ev.key.match(/^[^ ]$/) || (this.searchStr.length && ev.key === ' '))
    ) {
      this.searchStr += ev.key.toLowerCase();

      let match: HTMLElement | undefined;
      const options = Array.from(
        this.$container!.querySelectorAll<HTMLElement>('li')
      );
      for (const option of options) {
        if (!searchTextCache.has(option)) {
          // Clone without nested SVGs, then take its trimmed lowercase text.
          const clone = option.cloneNode(true) as HTMLElement;
          clone.querySelectorAll('svg').forEach((svg) => svg.remove());
          searchTextCache.set(
            option,
            (clone.textContent ?? '').toLowerCase().trimStart()
          );
        }
        if (searchTextCache.get(option)!.startsWith(this.searchStr)) {
          match = option;
          break;
        }
      }

      if (match) {
        this.focusElement(match);
      }

      if (this.clearSearchStrTimeout) {
        clearTimeout(this.clearSearchStrTimeout);
      }
      this.clearSearchStrTimeout = setTimeout(() => {
        this.clearSearchStr();
      }, 1000);
    }
  }

  /** Whether the menu is currently expanded (reads the trigger's `aria-expanded`). */
  isExpanded(): boolean {
    return this.$trigger?.getAttribute('aria-expanded') === 'true';
  }

  /** Toggle the menu on a trigger click (re-capturing the alignment element first). */
  handleTriggerClick(): void {
    if (!this.isExpanded()) {
      this.captureAlignmentElement();
      this.show();
    } else {
      this.hide();
    }
  }

  /**
   * Show the menu: move it to the end of `<body>`, position it under the
   * trigger, wire scroll/resize repositioning, reveal it, focus the first item,
   * register the UI layer + Escape shortcut, and emit `beforeShow` then `show`.
   * No-op if already expanded or the trigger is disabled.
   */
  show(): void {
    if (this.isExpanded() || this.$trigger?.classList.contains('disabled')) {
      return;
    }

    this.trigger('beforeShow');

    // Move the menu to the end of the DOM (highest sub-z-index).
    bod.appendChild(this.$container!);

    this.setContainerPosition();
    this.addListener(globals.scrollContainer, 'scroll', 'setContainerPosition');

    const scrollParent = this.$trigger
      ? getScrollParent(this.$trigger)
      : globals.scrollContainer;
    if (scrollParent instanceof Element && scrollParent !== bod) {
      this._scrollParent = scrollParent;
      this.addListener(scrollParent, 'scroll', 'setContainerPosition');
    }
    this.addListener(win, 'resize', 'setContainerPosition');

    // Reveal (instant — legacy only fades on hide).
    this._cancelAnim();
    this.$container!.classList.add('visible');
    this.$container!.style.opacity = '1';
    if (window.getComputedStyle(this.$container!).display === 'none') {
      this.$container!.style.display = 'block';
    }

    this.$trigger?.setAttribute('aria-expanded', 'true');

    // Focus the first focusable element (or the container itself).
    const firstFocusable = getFocusableElements(this.$container!)[0];
    if (firstFocusable) {
      firstFocusable.focus();
    } else {
      this.$container!.setAttribute('tabindex', '-1');
      this.$container!.focus();
    }

    // Find the next focusable element after the trigger; Shift-Tab on it should
    // take focus back into the container.
    if (this.$trigger) {
      const focusableElements = getFocusableElements(bod);
      const triggerIndex = focusableElements.indexOf(this.$trigger);
      if (triggerIndex !== -1 && focusableElements.length > triggerIndex + 1) {
        this.$nextFocusableElement = focusableElements[triggerIndex + 1]!;
        this.addListener(this.$nextFocusableElement, 'keydown', (event) => {
          const ev = event as unknown as KeyboardEvent;
          if (ev.keyCode === TAB_KEY && ev.shiftKey) {
            const items = getFocusableElements(this.$container!);
            const last = items[items.length - 1];
            if (last) {
              ev.preventDefault();
              last.focus();
            }
          }
        });
      }
    }

    this.trigger('show');
    this.clearSearchStr();

    const manager = getUiLayerManager();
    manager?.addLayer(this.$container ?? undefined);
    manager?.registerShortcut(ESC_KEY, () => {
      this.hide();
    });
  }

  /**
   * Hide the menu: restore focus to the trigger if focus was inside, drop the
   * Tab-cycle + scroll/resize listeners, pop the UI layer, clear the search
   * input/buffer, emit `hide`, then fade the panel out. No-op if not expanded.
   */
  hide(): void {
    if (!this.isExpanded()) {
      return;
    }

    this._cancelAnim();
    this.$trigger?.setAttribute('aria-expanded', 'false');

    if (this.focusIsInMenu()) {
      this.$trigger?.focus();
    }

    if (this.$nextFocusableElement) {
      this.removeListener(this.$nextFocusableElement, 'keydown');
      this.$nextFocusableElement = null;
    }

    if (this.$searchInput) {
      this.$searchInput.value = '';
      const clearBtn =
        this.$container!.querySelector<HTMLElement>('.clear-btn');
      if (clearBtn) {
        this._applySearchFilter('', clearBtn);
      }
    }

    this.trigger('hide');
    this.clearSearchStr();

    this.removeListener(globals.scrollContainer, 'scroll');
    if (this._scrollParent) {
      this.removeListener(this._scrollParent, 'scroll');
      this._scrollParent = null;
    }
    this.removeListener(win, 'resize');
    getUiLayerManager()?.removeLayer(this.$container ?? undefined);

    // Fade the panel out, then drop the `visible` class + inline display.
    this._fadeOutContainer();
  }

  /** Whether keyboard focus currently lives inside the menu container. */
  focusIsInMenu(): boolean {
    return !!this.$container && focusIsInside(this.$container);
  }

  /**
   * Position the menu under (or above) the trigger and align it
   * left/center/right within the viewport. A faithful port of the legacy
   * `setContainerPosition` clearance math.
   */
  setContainerPosition(): void {
    const container = this.$container;
    const alignmentElement = this.$alignmentElement;
    if (!container || !alignmentElement) {
      return;
    }

    const settings = this.settings!;

    const vpWidth = window.innerWidth;
    const vpHeight = window.innerHeight;
    const vpScrollLeft = window.scrollX;
    const vpScrollTop = window.scrollY;

    const alignOffset = jQueryOffset(alignmentElement);
    const alignWidth = getOuterWidth(alignmentElement);
    const alignHeight = getOuterHeight(alignmentElement);
    const alignOffsetRight = alignOffset.left + alignWidth;
    const alignOffsetBottom = alignOffset.top + alignHeight;

    // Re-measure from scratch (clear any prior max-width clamp).
    container.style.maxWidth = '';
    container.style.minWidth = '0';
    container.style.minWidth = `${
      alignWidth - (getOuterWidth(container) - getContentWidth(container))
    }px`;

    let menuWidth = getOuterWidth(container);
    const menuHeight = getOuterHeight(container);

    if (menuWidth > vpWidth) {
      container.style.maxWidth = `${vpWidth}px`;
      menuWidth = vpWidth;
    }

    // Is there room for the menu below the trigger?
    const topClearance = alignOffset.top - vpScrollTop;
    const bottomClearance = vpHeight + vpScrollTop - alignOffsetBottom;

    if (
      settings.position === 'below' ||
      bottomClearance >= menuHeight ||
      (topClearance < menuHeight && bottomClearance >= topClearance)
    ) {
      container.style.top = `${alignOffsetBottom}px`;
      container.style.maxHeight = `${bottomClearance - settings.windowSpacing}px`;
    } else {
      container.style.top = `${
        alignOffset.top -
        Math.min(menuHeight, topClearance - settings.windowSpacing)
      }px`;
      container.style.maxHeight = `${topClearance - settings.windowSpacing}px`;
    }

    // Stash transient values for the _align* helpers.
    this._vpWidth = vpWidth;
    this._vpScrollLeft = vpScrollLeft;
    this._alignOffsetLeft = alignOffset.left;
    this._alignWidth = alignWidth;
    this._menuWidth = menuWidth;

    // Figure out how we're aligning it.
    let align = container.getAttribute('data-align');
    if (align !== 'left' && align !== 'center' && align !== 'right') {
      align = 'left';
    }

    if (menuWidth === vpWidth || align === 'center') {
      this._alignCenter();
    } else {
      const rightClearance =
        vpWidth + vpScrollLeft - (alignOffset.left + menuWidth);
      const leftClearance = alignOffsetRight - menuWidth;

      if (leftClearance < 0 && rightClearance < 0) {
        this._alignCenter();
      } else if (
        (align === 'right' && leftClearance >= 0) ||
        rightClearance < 0
      ) {
        this._alignRight();
      } else {
        this._alignLeft();
      }
    }
  }

  /** Clear the type-ahead search buffer + its pending timeout. */
  clearSearchStr(): void {
    this.searchStr = '';
    if (this.clearSearchStrTimeout) {
      clearTimeout(this.clearSearchStrTimeout);
      this.clearSearchStrTimeout = null;
    }
  }

  /** Whether the menu uses padded groups (a direct child `<tag>.padded`). */
  isPadded(tag = 'ul'): boolean {
    return !!this.$container?.querySelector(`:scope > ${tag}.padded`);
  }

  /**
   * Build a menu item element from a config object (or pass an existing element
   * through). Returns the `<li>` wrapper.
   */
  createItem(item: DisclosureMenuItem): HTMLLIElement {
    if (item instanceof Element) {
      // Already a built element (e.g. an <li>); return it as-is.
      return item as HTMLLIElement;
    }

    if (!isPlainObject(item)) {
      throw 'Unsupported item configuration.';
    }

    const config = item as DisclosureMenuItemConfig;

    let type: 'button' | 'link';
    if (config.type) {
      type = config.type;
    } else if (config.url) {
      type = 'link';
    } else {
      type = 'button';
    }

    const li = document.createElement('li');
    const el = document.createElement(type === 'button' ? 'button' : 'a');

    if (type === 'button') {
      el.setAttribute('type', 'button');
    }

    el.id = config.id || `menu-item-${++itemIdCounter}`;
    el.className = 'menu-item';
    if (config.selected) {
      el.classList.add('sel');
    }
    if (config.destructive) {
      el.classList.add('error');
      el.setAttribute('data-destructive', 'true');
    }
    if (config.disabled) {
      el.classList.add('disabled');
    }
    if (config.action) {
      // The CP's form-submit machinery keys off the `formsubmit` class +
      // `data-action`; the legacy `$(el).formsubmit()` jQuery call is omitted
      // here to keep the bundle jQuery-free (doc 16).
      el.classList.add('formsubmit');
    }
    if (type === 'link') {
      (el as HTMLAnchorElement).href = craftUrl(config.url ?? '');
    }
    if (config.icon) {
      if (typeof config.icon === 'string') {
        el.setAttribute('data-icon', config.icon);
        if (config.iconColor) {
          el.classList.add(config.iconColor);
        }
      } else {
        const iconSource = config.icon;
        void (async () => {
          let icon: Element;
          if (iconSource instanceof Element) {
            icon = iconSource;
          } else if (typeof iconSource === 'function') {
            icon = await iconSource();
          } else {
            throw 'Unsupported icon type';
          }
          const span = document.createElement('span');
          span.className = 'icon';
          if (config.iconColor) {
            span.classList.add(config.iconColor);
          }
          span.append(icon);
          el.prepend(span);
        })();
      }
    }
    if (config.action) {
      el.setAttribute('data-action', config.action);
      el.setAttribute('data-form', 'false');
    }
    if (config.params) {
      el.setAttribute(
        'data-params',
        typeof config.params === 'string'
          ? config.params
          : JSON.stringify(config.params)
      );
    }
    if (config.confirm) {
      el.setAttribute('data-confirm', config.confirm);
    }
    if (config.redirect) {
      el.setAttribute('data-redirect', config.redirect);
    }
    if (config.attributes) {
      for (const name in config.attributes) {
        el.setAttribute(name, config.attributes[name]!);
      }
    }
    li.append(el);

    if (config.status) {
      const status = document.createElement('div');
      status.className = `status ${config.status}`;
      el.append(status);
    }

    const label = document.createElement('span');
    label.className = 'menu-item-label';
    if (config.label) {
      label.textContent = config.label;
    } else if (config.html) {
      label.innerHTML = config.html;
    }
    el.append(label);

    if (config.description) {
      const description = document.createElement('div');
      description.className = 'menu-item-description smalltext light';
      description.textContent = config.description;
      el.append(description);
    }

    if (type === 'link') {
      this.addListener(el, 'keydown', (event) => {
        if ((event as unknown as KeyboardEvent).keyCode === SPACE_KEY) {
          (el as HTMLElement).click();
        }
      });
    }

    this.addListener(el, 'activate', () => {
      if (config.onActivate) {
        config.onActivate(el as HTMLElement);
      } else if (config.callback) {
        config.callback(el as HTMLElement);
      }
      setTimeout(() => {
        this.hide();
      }, 1);
    });

    return li;
  }

  /** Append a new empty `<ul>` group to the menu and return it. */
  addList(): HTMLUListElement {
    const ul = document.createElement('ul');
    this.$container!.appendChild(ul);
    return ul;
  }

  /**
   * Add an item to the menu. Builds it via {@link createItem}, appends (or
   * prepends) it to `ul` (defaulting to the last group, creating one if needed),
   * shows/hides it, and returns the item's `<a>`/`<button>` element.
   */
  addItem(
    item: DisclosureMenuItem,
    ul?: HTMLElement | null,
    prepend = false
  ): HTMLElement {
    const li = this.createItem(item);

    if (!ul) {
      ul = this.lastChildUl() ?? this.addGroup();
    }

    if (prepend) {
      ul.prepend(li);
    } else {
      ul.append(li);
    }

    const el = li.querySelector<HTMLElement>('a, button')!;
    const hidden =
      !(item instanceof Element) && (item as DisclosureMenuItemConfig).hidden;

    // Show or hide it (show, in case the UL is already hidden).
    this.toggleItem(el, !hidden);
    this.updateVisibility();

    return el;
  }

  /** Add several items to the menu (optionally into a given group). */
  addItems(items: DisclosureMenuItem[], ul?: HTMLElement | null): void {
    for (const item of items) {
      this.addItem(item, ul);
    }
  }

  /** Add a horizontal rule, either before `before` or at the end of the menu. */
  addHr(before?: Element | null): HTMLHRElement {
    const hr = document.createElement('hr');
    if (this.isPadded('hr')) {
      hr.className = 'padded';
    }

    if (before) {
      before.parentNode?.insertBefore(hr, before);
    } else {
      this.$container!.append(hr);
    }

    return hr;
  }

  /** The first group `<ul>` containing a destructive item, if any. */
  getFirstDestructiveGroup(): HTMLUListElement | undefined {
    for (const child of Array.from(this.$container!.children)) {
      if (child.tagName === 'UL' && child.querySelector('[data-destructive]')) {
        return child as HTMLUListElement;
      }
    }
    return undefined;
  }

  /**
   * Add a group (an optional `<h3>` heading + a `<ul>`), with surrounding
   * `<hr>`s by default, either before `before` or at the end. Returns the `<ul>`.
   */
  addGroup(
    heading: string | null = null,
    addHrs = true,
    before: Element | null = null
  ): HTMLUListElement {
    const padded = this.isPadded();

    if (heading) {
      const h3 = document.createElement('h3');
      h3.classList.add('h6');
      if (padded) {
        h3.classList.add('padded');
      }
      h3.textContent = heading;

      if (before) {
        before.parentNode?.insertBefore(h3, before);
      } else {
        this.$container!.append(h3);
      }
    }

    const ul = document.createElement('ul');
    if (padded) {
      ul.className = 'padded';
    }

    if (before) {
      before.parentNode?.insertBefore(ul, before);
    } else {
      this.$container!.append(ul);
    }

    if (addHrs) {
      const prev = ul.previousElementSibling;
      if (
        prev &&
        prev.nodeName !== 'HR' &&
        !prev.classList.contains('search-container')
      ) {
        this.addHr(ul);
      }
      if (ul.nextElementSibling && ul.nextElementSibling.nodeName !== 'HR') {
        this.addHr(ul.nextElementSibling);
      }
    }

    this.updateVisibility();

    return ul;
  }

  /** Show or hide an item's `<li>`. Defaults to flipping the current state. */
  toggleItem(el: HTMLElement, show?: boolean): void {
    if (typeof show === 'undefined') {
      show = el.parentElement!.classList.contains('hidden');
    }

    if (show) {
      this.showItem(el);
    } else {
      this.hideItem(el);
    }
  }

  /** Reveal an item's `<li>` and re-run visibility/positioning. */
  showItem(el: HTMLElement): void {
    el.parentElement!.classList.remove('hidden');
    this.updateVisibility();
    if (this.isExpanded()) {
      this.setContainerPosition();
    }
  }

  /** Hide an item's `<li>` and re-run visibility/positioning. */
  hideItem(el: HTMLElement): void {
    el.parentElement!.classList.add('hidden');
    this.updateVisibility();
    if (this.isExpanded()) {
      this.setContainerPosition();
    }
  }

  /** Show/hide a group based on whether it has any visible (non-filtered) items. */
  toggleGroup(group: HTMLElement): void {
    if (group.querySelectorAll('li:not(.hidden):not(.filtered)').length) {
      group.classList.remove('hidden');
    } else {
      group.classList.add('hidden');
    }
  }

  /** Remove an item; drops its group too if it becomes empty. */
  removeItem(el: HTMLElement): void {
    const li = el.parentElement!;
    const ul = li.parentElement!;
    li.remove();
    if (ul.querySelectorAll(':scope > li').length === 0) {
      ul.remove();
    }

    this.updateVisibility();
    if (this.isExpanded()) {
      this.setContainerPosition();
    }
  }

  /** @deprecated Use {@link updateVisibility}. */
  updateHrVisibility(): void {
    this.updateVisibility();
  }

  /**
   * Recompute group / `<hr>` / heading visibility: hide empty groups, hide
   * `<hr>`s without visible items on both sides, and hide headings with no
   * following visible items.
   */
  updateVisibility(): void {
    const container = this.$container!;

    for (const group of Array.from(
      container.querySelectorAll<HTMLElement>(
        ':scope > ul, :scope > .menu-group'
      )
    )) {
      this.toggleGroup(group);
    }

    for (const hr of Array.from(
      container.querySelectorAll<HTMLElement>('hr')
    )) {
      const prevVisible = siblingsUntil(hr, 'h3, hr', 'prev').some(
        isVisibleItem
      );
      const nextVisible = siblingsUntil(hr, 'h3, hr', 'next').some(
        isVisibleItem
      );
      if (prevVisible && nextVisible) {
        hr.classList.remove('hidden');
      } else {
        hr.classList.add('hidden');
      }
    }

    for (const h3 of Array.from(
      container.querySelectorAll<HTMLElement>('h3')
    )) {
      const nextVisible = siblingsUntil(h3, 'h3, hr', 'next').some(
        isVisibleItem
      );
      if (nextVisible) {
        h3.classList.remove('hidden');
      } else {
        h3.classList.add('hidden');
      }
    }
  }

  /** Whether the menu has any non-hidden items. */
  hasVisibleItems(): boolean {
    return !!this.$container?.querySelector('li:not(.hidden)');
  }

  /**
   * Destroy the menu: drop the trigger/container element registrations, remove
   * it from {@link DisclosureMenu.instances}, clear the search timeout, then run
   * the base teardown (emits `destroy`, removes all listeners).
   */
  override destroy(): void {
    if (this.$trigger) {
      menuElements.delete(this.$trigger);
    }
    if (this.$container) {
      menuElements.delete(this.$container);
    }

    this.clearSearchStr();
    this._cancelAnim();

    DisclosureMenu.instances = DisclosureMenu.instances.filter(
      (o) => o !== this
    );

    super.destroy();
  }

  // --- Internals ------------------------------------------------------------

  /** The last direct-child `<ul>` of the container, if any. */
  private lastChildUl(): HTMLUListElement | null {
    const uls =
      this.$container!.querySelectorAll<HTMLUListElement>(':scope > ul');
    return uls.length ? uls[uls.length - 1]! : null;
  }

  private _alignLeft(): void {
    this.$container!.style.left = `${Math.max(this._alignOffsetLeft, 0)}px`;
    this.$container!.style.right = 'auto';
  }

  private _alignRight(): void {
    const right = this._vpWidth - (this._alignOffsetLeft + this._alignWidth);
    this.$container!.style.right = `${Math.max(right, 0)}px`;
    this.$container!.style.left = 'auto';
  }

  private _alignCenter(): void {
    const left = Math.round(
      this._alignOffsetLeft + this._alignWidth / 2 - this._menuWidth / 2
    );
    this.$container!.style.left = `${Math.max(left, 0)}px`;
    this.$container!.style.right = 'auto';
  }

  /**
   * Fade the container out (Web Animations API), then strip the `visible` class
   * and inline display. Gated on `prefersReducedMotion` / environments without
   * `element.animate` (happy-dom), which finalize synchronously.
   */
  private _fadeOutContainer(): void {
    const el = this.$container!;
    const finalize = (): void => {
      el.style.opacity = '0';
      el.classList.remove('visible');
      el.style.display = '';
      this._anim = null;
    };

    if (prefersReducedMotion() || typeof el.animate !== 'function') {
      finalize();
      return;
    }

    el.style.opacity = '1';
    const anim = el.animate([{opacity: 1}, {opacity: 0}], {
      duration: FADE_OUT_DURATION,
      fill: 'forwards',
    });
    this._anim = anim;
    anim.onfinish = finalize;
    anim.oncancel = (): void => {
      this._anim = null;
    };
  }

  /** Cancel a pending fade-out (legacy `.velocity('stop')`). */
  private _cancelAnim(): void {
    if (this._anim) {
      this._anim.cancel();
    }
    this._anim = null;
  }
}

/** Document-relative offset rectangle (jQuery `.offset()` parity). */
function jQueryOffset(el: Element): {top: number; left: number} {
  const rect = el.getBoundingClientRect();
  return {top: rect.top + window.scrollY, left: rect.left + window.scrollX};
}

/**
 * Content-box width (jQuery `.width()` parity): the border-box width from
 * `getBoundingClientRect()` minus horizontal padding + border. Using the rect
 * (not `clientWidth`) keeps it mockable in layout-less test environments.
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

/** Whether a menu sibling counts as a visible item (not `hidden`/`filtered`). */
function isVisibleItem(el: Element): boolean {
  return !el.classList.contains('hidden') && !el.classList.contains('filtered');
}

/**
 * Collect siblings of `el` in the given direction up to (but not including) the
 * first sibling matching `stopSelector` — the native replacement for jQuery's
 * `.prevUntil()` / `.nextUntil()`.
 */
function siblingsUntil(
  el: Element,
  stopSelector: string,
  direction: 'prev' | 'next'
): Element[] {
  const out: Element[] = [];
  let node =
    direction === 'prev' ? el.previousElementSibling : el.nextElementSibling;
  while (node) {
    if (node.matches(stopSelector)) {
      break;
    }
    out.push(node);
    node =
      direction === 'prev'
        ? node.previousElementSibling
        : node.nextElementSibling;
  }
  return out;
}

export default DisclosureMenu;
