/**
 * BaseDrag — modern, jQuery-free TypeScript port of Garnish's `BaseDrag`.
 *
 * Does all the grunt work for manipulating elements via click-and-drag, while
 * leaving the actual element manipulation up to a subclass (`DragMove`, and
 * later `Drag`/`DragSort`). Every jQuery call from the legacy `BaseDrag.js` is
 * replaced with native DOM (see doc 07 §2):
 *
 * - `mousedown`/`mousemove`/`mouseup` + the legacy touch shim → the Pointer
 *   Events API (`pointerdown`/`pointermove`/`pointerup`/`pointercancel`), which
 *   unifies mouse + touch + pen.
 * - `$.data(item, 'drag')` → a module-level `WeakMap<Element, BaseDrag>`.
 * - `$()` collections → native `Element[]`; `$.scrollParent()` →
 *   `getScrollParent` (`utils/scroll.ts`); `$.offset()` → `getOffset`.
 * - `Garnish.requestAnimationFrame` etc. → `utils/animation.ts`.
 *
 * Consumers honor the `$`-prefixed public names (`$items`, `$targetItem`) for
 * parity, even though they now hold native elements rather than jQuery
 * collections.
 */

import {Base} from '../base';
import {X_AXIS, Y_AXIS} from '../constants';
import {doc, globals, win} from '../globals';
import {requestAnimationFrame, cancelAnimationFrame} from '../utils/animation';
import {coerceElements, getOffset} from '../utils/dom';
import {isPrimaryClick} from '../utils/env';
import {getDist, isPlainObject} from '../utils/misc';
import {getScrollParent, isWindowScrollContainer} from '../utils/scroll';
import type {GarnishEvent} from './../events';
import type {ElementInput, GarnishBaseSettings} from '../types';

/** Anything `addItems`/`removeItems` accepts: a selector, element, or list. */
export type DragItemsInput = ElementInput;

/** A drag handle: element(s), a selector resolved within each item, a resolver fn, or `null`. */
export type DragHandle =
  | ElementInput
  | ((item: Element) => ElementInput)
  | null;

/** Settings accepted by {@link BaseDrag}. */
export interface BaseDragSettings extends GarnishBaseSettings {
  /** Min px the pointer must travel before a drag starts. `null` → the static {@link BaseDrag.minMouseDist} (1). */
  minMouseDist: number | null;
  /**
   * The drag handle. Element(s), a selector resolved within each item, or a
   * function `(item) => handleElement`. `null` → the item itself is the handle.
   */
  handle: DragHandle;
  /** Restrict movement to one axis: {@link X_AXIS}, {@link Y_AXIS}, or `null` (both). */
  axis: typeof X_AXIS | typeof Y_AXIS | null;
  /** Pointer-down on a descendant matching this selector is ignored (unless it IS the handle). */
  ignoreHandleSelector: string | null;

  onBeforeDragStart: () => void;
  onDragStart: () => void;
  onDrag: () => void;
  onDragStop: () => void;
}

/**
 * Maps a draggable item element back to its owning `BaseDrag` instance — the
 * native replacement for the legacy `$.data(item, 'drag')`.
 */
const dragRegistry = new WeakMap<Element, BaseDrag>();

/** Read the owning dragger of an item (test/introspection helper). */
export function getItemDragger(item: Element): BaseDrag | undefined {
  return dragRegistry.get(item);
}

const noop = (): void => {};

/**
 * The base drag class. Tracks a set of draggable items, watches for a
 * pointer-down on each item's handle, starts a drag once the pointer has moved
 * past {@link BaseDragSettings.minMouseDist}, and drives an auto-scroll loop
 * when the pointer nears a scroll edge. Subclasses override
 * {@link onDragStart}/{@link onDrag}/{@link onDragStop} (and optionally
 * {@link startDragging}/{@link drag}/{@link stopDragging} via `super`-dispatch)
 * to manipulate the dragged element(s).
 *
 * @typeParam S - The settings shape; defaults to {@link BaseDragSettings}.
 *
 * @example
 * ```ts
 * const dragger = new BaseDrag('.draggable', {
 *   axis: 'x',
 *   onDrag: () => console.log('dragging'),
 * });
 * ```
 */
export class BaseDrag<
  S extends BaseDragSettings = BaseDragSettings,
> extends Base<S> {
  /** Fallback for `settings.minMouseDist === null`. */
  static readonly minMouseDist = 1;
  /** px edge band that triggers auto-scroll. */
  static readonly windowScrollTargetSize = 25;

  /** Default {@link BaseDragSettings}. */
  static readonly defaults: BaseDragSettings = {
    minMouseDist: null,
    handle: null,
    axis: null,
    ignoreHandleSelector: 'input, textarea, button, select, .btn',
    onBeforeDragStart: noop,
    onDragStart: noop,
    onDrag: noop,
    onDragStop: noop,
  };

  // --- Items + current target (legacy `$`-prefixed names) -------------------

  /** The draggable items (native elements; legacy held a jQuery collection). */
  $items: Element[] = [];
  /** The element currently being dragged, or `null`. */
  $targetItem: HTMLElement | null = null;

  // --- State ----------------------------------------------------------------

  /** Whether a drag is in progress. */
  dragging = false;

  // --- Pointer coordinates (page coords) ------------------------------------

  mousedownX: number | null = null;
  mousedownY: number | null = null;
  /** Unconstrained pointer X (before axis lock). */
  realMouseX: number | null = null;
  realMouseY: number | null = null;
  /** Axis-constrained pointer X; what subclasses use. */
  mouseX: number | null = null;
  mouseY: number | null = null;
  /** `mouseX - mousedownX`. */
  mouseDistX: number | null = null;
  mouseDistY: number | null = null;
  /** Pointer-down page X minus the target's left offset (grab offset). */
  mouseOffsetX: number | null = null;
  mouseOffsetY: number | null = null;

  // --- Scroll-loop state ----------------------------------------------------

  scrollProperty: 'scrollTop' | 'scrollLeft' | null = null;
  scrollAxis: 'X' | 'Y' | null = null;
  scrollDist: number | null = null;
  /** RAF handle for the auto-scroll loop. */
  scrollFrame: number | null = null;

  /** The resolved scroll container (replaces the legacy `this._.$scrollContainer`). */
  private _scrollContainer: Element | Window = win;
  /** The pointer id that started the current interaction (multi-touch safety). */
  private _pointerId: number | null = null;

  /**
   * @param items - Elements that should be draggable right away (selector,
   *   element, or list), or — via the legacy param-shift — a `Partial<S>`
   *   settings object when no second argument is given.
   * @param settings - Optional settings overrides (see {@link BaseDragSettings}).
   */
  constructor(
    items?: DragItemsInput | Partial<S> | null,
    settings?: Partial<S>
  ) {
    super();

    // Param-shift: `new BaseDrag(settings)` when the first arg is a plain object.
    let resolvedItems: DragItemsInput = null;
    if (settings === undefined && isPlainObject(items)) {
      settings = items as Partial<S>;
    } else {
      resolvedItems = (items as DragItemsInput) ?? null;
    }

    this.setSettings(settings, BaseDrag.defaults as Partial<S>);

    this.$items = [];

    if (resolvedItems) {
      this.addItems(resolvedItems);
    }
  }

  // --- Drag lifecycle -------------------------------------------------------

  /** Returns whether dragging is allowed right now. Subclasses override to gate. */
  allowDragging(): boolean {
    return true;
  }

  /** Begin a drag: fire the before/start hooks, resolve the scroll container. */
  startDragging(): void {
    this.onBeforeDragStart();
    this.dragging = true;
    this.setScrollContainer();
    this.onDragStart();

    // Mute activate events while dragging (legacy parity).
    globals.activateEventsMuted = true;
  }

  /** Resolve {@link _scrollContainer} for the current target via the axis-aware finder. */
  setScrollContainer(): void {
    const axis = (this.settings!.axis as 'x' | 'y' | null) ?? null;
    this._scrollContainer = this.$targetItem
      ? getScrollParent(this.$targetItem, axis)
      : win;
  }

  /** Whether the resolved scroll container is the window. */
  isScrollingWindow(): boolean {
    return isWindowScrollContainer(this._scrollContainer);
  }

  /**
   * Advance the drag a frame. When `didMouseMove`, decide whether the pointer is
   * within the auto-scroll edge band and (start|update|cancel) the scroll loop
   * accordingly, then fire {@link onDrag}.
   */
  drag(didMouseMove: boolean): void {
    if (didMouseMove) {
      const decision = this._computeEdgeScroll();

      if (decision) {
        // Are we starting to scroll now?
        if (!this.scrollProperty) {
          if (this.scrollFrame) {
            cancelAnimationFrame(this.scrollFrame);
            this.scrollFrame = null;
          }
          this.scrollFrame = requestAnimationFrame(() => this._scrollWindow());
        }

        this.scrollProperty = decision.property;
        this.scrollAxis = decision.axis;
        this.scrollDist = decision.dist;
      } else {
        this._cancelWindowScroll();
      }
    }

    this.onDrag();
  }

  /** End the drag: fire {@link onDragStop}, cancel scrolling, unmute activate events. */
  stopDragging(): void {
    this.dragging = false;
    this.onDragStop();

    this._cancelWindowScroll();

    requestAnimationFrame(() => {
      globals.activateEventsMuted = false;
    });
  }

  // --- Item management ------------------------------------------------------

  /**
   * Make the given element(s) draggable: register them with this dragger and
   * bind a `pointerdown` listener on each item's handle.
   */
  addItems(items: DragItemsInput): void {
    const elements = coerceElements(items).filter(
      (el): el is Element => el instanceof Element
    );

    for (const item of elements) {
      // Make sure this element doesn't belong to another dragger.
      const owner = dragRegistry.get(item);
      if (owner) {
        if (owner === this) {
          continue;
        }
        console.warn('Element was added to more than one dragger');
        owner.removeItems(item);
      }

      dragRegistry.set(item, this);

      // Bind pointerdown on the handle. The registry passes the native event
      // through (core impl note #2), so it is a real PointerEvent at runtime.
      this.addListener(this._getItemHandle(item), 'pointerdown', (ev) => {
        this._handlePointerDown(ev as unknown as PointerEvent, item);
      });

      this.$items.push(item);
    }
  }

  /** Stop tracking the given element(s) and unbind their handle listeners. */
  removeItems(items: DragItemsInput): void {
    const elements = coerceElements(items).filter(
      (el): el is Element => el instanceof Element
    );

    for (const item of elements) {
      const index = this.$items.indexOf(item);
      if (index !== -1) {
        this._deinitItem(item);
        this.$items.splice(index, 1);
      }
    }
  }

  /** The item before `item` in {@link $items}, or `null`. */
  getPrevItem(item: Element): Element | null {
    const index = this.$items.indexOf(item);
    if (index === -1 || index === 0) {
      return null;
    }
    return this.$items[index - 1] ?? null;
  }

  /** The item after `item` in {@link $items}, or `null`. */
  getNextItem(item: Element): Element | null {
    const index = this.$items.indexOf(item);
    if (index === -1 || index === this.$items.length - 1) {
      return null;
    }
    return this.$items[index + 1] ?? null;
  }

  /** Stop tracking every item. */
  removeAllItems(): void {
    for (const item of this.$items) {
      this._deinitItem(item);
    }
    this.$items = [];
  }

  /** Tear the dragger down: drop all items, then run the base teardown. */
  override destroy(): void {
    this.removeAllItems();
    super.destroy();
  }

  // --- Overridable hooks (event emitters) -----------------------------------

  /** Sync hook: emits `beforeDragStart` + invokes `settings.onBeforeDragStart`. */
  onBeforeDragStart(): void {
    this.trigger('beforeDragStart');
    this.settings!.onBeforeDragStart();
  }

  /** RAF-deferred hook: emits `dragStart` + invokes `settings.onDragStart`. */
  onDragStart(): void {
    requestAnimationFrame(() => {
      this.trigger('dragStart');
      this.settings!.onDragStart();
    });
  }

  /** RAF-deferred hook: emits `drag` + invokes `settings.onDrag`. */
  onDrag(): void {
    requestAnimationFrame(() => {
      this.trigger('drag');
      this.settings!.onDrag();
    });
  }

  /** RAF-deferred hook: emits `dragStop` + invokes `settings.onDragStop`. */
  onDragStop(): void {
    requestAnimationFrame(() => {
      this.trigger('dragStop');
      this.settings!.onDragStop();
    });
  }

  // --- Private --------------------------------------------------------------

  /** Resolve the handle element(s) for an item from `settings.handle`. */
  private _getItemHandle(item: Element): Element[] {
    const {handle} = this.settings!;
    if (!handle) {
      return [item];
    }

    if (typeof handle === 'function') {
      return coerceElements(handle(item)).filter(
        (el): el is Element => el instanceof Element
      );
    }

    if (typeof handle === 'string') {
      // Legacy split commas and forced `:first` per selector to dodge
      // craftcms/cms#12896. `querySelector` is inherently first-match, so the
      // `:first` hack is dropped.
      return handle
        .split(',')
        .map((s) => item.querySelector(s.trim()))
        .filter((el): el is Element => el !== null);
    }

    // Element / list of elements passed directly (Modal passes the container).
    return coerceElements(handle).filter(
      (el): el is Element => el instanceof Element
    );
  }

  /** Pointer-down on a handle: capture the target + initial coords, start listening. */
  private _handlePointerDown(ev: PointerEvent, item: Element): void {
    // Ignore right/ctrl-clicks.
    if (!isPrimaryClick(ev)) {
      return;
    }

    // Ignore if we already have a target.
    if (this.$targetItem) {
      return;
    }

    // Make sure the target isn't a button (unless the button IS the handle).
    const evTarget = ev.target as Element | null;
    if (item !== ev.target && this.settings!.ignoreHandleSelector) {
      if (evTarget?.closest(this.settings!.ignoreHandleSelector)) {
        return;
      }
    }

    ev.preventDefault();

    // Make sure that dragging is allowed right now.
    if (!this.allowDragging()) {
      return;
    }

    // Capture the target + pointer.
    this.$targetItem = item as HTMLElement;
    this._pointerId = ev.pointerId;

    // Capture the current pointer position.
    this.mousedownX = this.mouseX = ev.pageX;
    this.mousedownY = this.mouseY = ev.pageY;

    // Capture the difference between the pointer position and the target's offset.
    const offset = getOffset(this.$targetItem);
    this.mouseOffsetX = ev.pageX - offset.left;
    this.mouseOffsetY = ev.pageY - offset.top;

    // Listen for pointermove/up/cancel on the document.
    this.addListener(doc, 'pointermove', '_handlePointerMove');
    this.addListener(doc, 'pointerup', '_handlePointerUp');
    this.addListener(doc, 'pointercancel', '_handlePointerUp');
  }

  /** Pointer-move: update coords, gate the drag start, then drag. */
  private _handlePointerMove(ev: PointerEvent): void {
    // Ignore events from a different pointer (multi-touch safety).
    if (this._pointerId !== null && ev.pointerId !== this._pointerId) {
      return;
    }

    ev.preventDefault();

    this.realMouseX = ev.pageX;
    this.realMouseY = ev.pageY;

    if (this.settings!.axis !== Y_AXIS) {
      this.mouseX = ev.pageX;
    }
    if (this.settings!.axis !== X_AXIS) {
      this.mouseY = ev.pageY;
    }

    this.mouseDistX = this.mouseX! - this.mousedownX!;
    this.mouseDistY = this.mouseY! - this.mousedownY!;

    if (!this.dragging) {
      // Has the pointer moved far enough to initiate dragging yet?
      const dist = getDist(
        this.mousedownX!,
        this.mousedownY!,
        this.realMouseX,
        this.realMouseY
      );

      if (dist >= (this.settings!.minMouseDist ?? BaseDrag.minMouseDist)) {
        this.startDragging();
      }
    }

    if (this.dragging) {
      this.drag(true);
    }
  }

  /** Pointer-up / -cancel: unbind doc listeners, stop dragging, clear the target. */
  private _handlePointerUp(ev: PointerEvent): void {
    if (this._pointerId !== null && ev.pointerId !== this._pointerId) {
      return;
    }

    this.removeAllListeners(doc);

    if (this.dragging) {
      this.stopDragging();
    }

    this.$targetItem = null;
    this._pointerId = null;
  }

  /**
   * Decide whether the pointer is inside the auto-scroll edge band and, if so,
   * which scroll property/axis to drive and by how far. Pure-ish helper factored
   * out of the legacy `drag()` body so it is unit-testable without a live loop.
   */
  private _computeEdgeScroll(): {
    property: 'scrollTop' | 'scrollLeft';
    axis: 'X' | 'Y';
    dist: number;
  } | null {
    const edge = BaseDrag.windowScrollTargetSize;
    const container = this._scrollContainer;
    const onWindow = this.isScrollingWindow();

    // Vertical (unless axis-locked to X).
    if (this.settings!.axis !== X_AXIS && this.mouseY != null) {
      let min: number;
      let max: number;
      if (onWindow) {
        min = win.scrollY;
        max = min + win.innerHeight;
      } else {
        const el = container as Element;
        min = getOffset(el).top;
        // Behavior fix (doc 07 §4.2): clientHeight, not the legacy outerHeight.
        max = min + el.clientHeight;
      }
      min += edge;
      max -= edge;

      if (this.mouseY < min) {
        return {
          property: 'scrollTop',
          axis: 'Y',
          dist: Math.round((this.mouseY - min) / 2),
        };
      }
      if (this.mouseY > max) {
        return {
          property: 'scrollTop',
          axis: 'Y',
          dist: Math.round((this.mouseY - max) / 2),
        };
      }
    }

    // Horizontal (unless axis-locked to Y), only if vertical didn't claim it.
    if (this.settings!.axis !== Y_AXIS && this.mouseX != null) {
      let min: number;
      let max: number;
      if (onWindow) {
        min = win.scrollX;
        max = min + win.innerWidth;
      } else {
        const el = container as Element;
        min = getOffset(el).left;
        max = min + el.clientWidth;
      }
      min += edge;
      max -= edge;

      if (this.mouseX < min) {
        return {
          property: 'scrollLeft',
          axis: 'X',
          dist: Math.round((this.mouseX - min) / 2),
        };
      }
      if (this.mouseX > max) {
        return {
          property: 'scrollLeft',
          axis: 'X',
          dist: Math.round((this.mouseX - max) / 2),
        };
      }
    }

    return null;
  }

  /** Auto-scroll one frame, correct the cursor when scrolling the window, re-schedule. */
  private _scrollWindow(): void {
    if (!this.scrollProperty || !this.scrollAxis || this.scrollDist == null) {
      return;
    }

    const onWindow = this.isScrollingWindow();
    const prop = this.scrollProperty;

    const scrollPos = onWindow
      ? prop === 'scrollTop'
        ? win.scrollY
        : win.scrollX
      : (this._scrollContainer as Element)[prop];

    let targetPos = scrollPos + this.scrollDist;
    if (targetPos < 0) {
      targetPos = 0;
    } else {
      let scrollMax: number;
      if (onWindow) {
        scrollMax =
          this.scrollAxis === 'Y'
            ? doc.documentElement.scrollHeight - win.innerHeight
            : doc.documentElement.scrollWidth - win.innerWidth;
      } else {
        const el = this._scrollContainer as Element;
        // Behavior fix (doc 07 §4.2): clientHeight/Width, not legacy outerHeight.
        scrollMax =
          this.scrollAxis === 'Y'
            ? el.scrollHeight - el.clientHeight
            : el.scrollWidth - el.clientWidth;
      }
      if (targetPos > scrollMax) {
        targetPos = scrollMax;
      }
    }

    // Apply the scroll.
    if (onWindow) {
      if (prop === 'scrollTop') {
        win.scrollTo({top: targetPos});
      } else {
        win.scrollTo({left: targetPos});
      }
    } else {
      (this._scrollContainer as Element)[prop] = targetPos;
    }

    // Window-only correction: keep the dragged helper tracking the cursor.
    if (onWindow) {
      const newScrollPos = prop === 'scrollTop' ? win.scrollY : win.scrollX;
      if (this.scrollAxis === 'Y') {
        if (this.mouseY != null) this.mouseY -= scrollPos - newScrollPos;
        this.realMouseY = this.mouseY;
      } else {
        if (this.mouseX != null) this.mouseX -= scrollPos - newScrollPos;
        this.realMouseX = this.mouseX;
      }
    }

    this.scrollFrame = requestAnimationFrame(() => this._scrollWindow());
    this.drag(true);
  }

  /** Cancel the auto-scroll loop and clear its state. */
  private _cancelWindowScroll(): void {
    if (this.scrollFrame) {
      cancelAnimationFrame(this.scrollFrame);
      this.scrollFrame = null;
    }
    this.scrollProperty = null;
    this.scrollAxis = null;
    this.scrollDist = null;
  }

  /** Unbind an item's handle listeners and drop it from the registry. */
  private _deinitItem(item: Element): void {
    this.removeAllListeners(this._getItemHandle(item));
    dragRegistry.delete(item);
  }
}

export default BaseDrag;
