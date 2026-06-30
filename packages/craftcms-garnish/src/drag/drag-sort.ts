/**
 * DragSort — modern, jQuery-free TypeScript port of Garnish's `DragSort`.
 *
 * Builds on {@link Drag} by letting you sort the dragged element(s) *amongst*
 * the other items: as the cursor moves, the draggee (and an optional placeholder
 * element) is physically re-inserted into the DOM at the spot it would land, so
 * the surrounding items reflow in real time. On drop the new order is committed
 * and a `sortChange` event fires if anything actually moved.
 *
 * jQuery removals (see doc 11 §4):
 *
 * - `$(this.settings.insertion)` / `$(fn($draggee))` → `_toInsertionElement`
 *   (`<template>` parse for markup, else the element directly).
 * - `$draggee.offset()` (magnet math) → `getOffset(this.$draggee[0])`.
 * - `Garnish.hitTest(x, y, $container)` → `hitTest(mouseX, mouseY, container)`.
 * - `$(container).height()` + `.parent()` walk → `getOuterHeight` + `parentElement`.
 * - `$draggee.insertBefore/After(item)` → `item.before/after(...$draggee)`.
 * - `$insertion.insertBefore($draggee.first())` → `$draggee[0].before($insertion)`.
 * - `this.$items.index(item)` → `this.$items.indexOf(item)`; `$el.index()` →
 *   `_siblingIndex(el)`; `this.$draggee.eq(i)` → `this.$draggee[i]`.
 * - `$().add(this.$items)` DOM-order re-sort → `_sortItemsByDomOrder`
 *   (`compareDocumentPosition`).
 * - `$.contains($draggee[0], item)` → `this.$draggee[0].contains(item)`.
 * - `$.data(item, 'midpoint')` → a `Map<Element, Midpoint>` midpoint cache.
 * - `Garnish.requestAnimationFrame` → `utils/animation.ts`; `$.noop` → `() => {}`.
 *
 * DEVIATION (doc 11 §2.1): the legacy `_getItemMidpoint` reposition-and-measure
 * fallback (`$.data('midpointVersion', …)`) is dropped. The modern flow always
 * populates the midpoint cache at drag start, so non-cached elements (only the
 * insertion placeholder) are measured directly via `getBoundingClientRect`.
 */

import {X_AXIS, Y_AXIS} from '../constants';
import {win} from '../globals';
import {requestAnimationFrame} from '../utils/animation';
import {coerceElements, getOffset, getOuterHeight, hitTest} from '../utils/dom';
import {isPlainObject} from '../utils/misc';
import {Drag, type DragSettings} from './drag';
import type {DragItemsInput} from './base-drag';
import type {ElementInput} from '../types';

/** A precomputed, scroll-adjusted (page-coords) midpoint + box for one item. */
interface Midpoint {
  x: number;
  y: number;
  width: number;
  height: number;
  top: number;
  bottom: number;
}

/** The insertion placeholder hook: a builder fn, an element, markup, or `null`. */
export type DragSortInsertion =
  | ((draggee: HTMLElement[]) => HTMLElement | string)
  | HTMLElement
  | string
  | null;

/** Settings accepted by {@link DragSort} (extends {@link DragSettings}). */
export interface DragSortSettings extends DragSettings {
  /**
   * The list container (element / selector / list). At drag start it is walked
   * up to the nearest ancestor that actually has a height; the drag only sorts
   * while the cursor is over that resolved {@link DragSort.$heightedContainer}.
   */
  container: ElementInput | null;
  /**
   * The placeholder shown at the landing spot. A function `(draggee) => element
   * | markup`, an element, or an HTML string. `null` → no placeholder (the
   * draggee itself is the feedback).
   */
  insertion: DragSortInsertion;
  /**
   * If the target item isn't the first draggee, move it to the front of the
   * draggee block in the DOM at drag start (and leave it there on drop).
   */
  moveTargetItemToFront: boolean;
  /**
   * Divides the helper's pull toward the cursor. `1` → track the cursor exactly
   * (as in {@link Drag}); `>1` → rubber-band toward the draggee's home.
   */
  magnetStrength: number;

  /** Fired (RAF-deferred) whenever the insertion point moves. */
  onInsertionPointChange: () => void;
  /** Fired (RAF-deferred) on drop when the order actually changed. */
  onSortChange: () => void;
  /** Gate: may the draggee be inserted *before* this item? */
  canInsertBefore: (item: HTMLElement) => boolean;
  /** Gate: may the draggee be inserted *after* this item? */
  canInsertAfter: (item: HTMLElement) => boolean;
}

const noop = (): void => {};

/** Viewport buffer (px) for the large-list visible-items filter. */
const VIEWPORT_BUFFER = 300;
/** Above this many items, restrict the closest-item scan to visible items. */
const LARGE_LIST_THRESHOLD = 200;

/**
 * The drag-to-sort class. Extends {@link Drag} with live insertion feedback:
 * the draggee is re-inserted into the DOM at the closest landing spot as the
 * cursor moves, and a `sortChange` event fires on drop if the order changed.
 *
 * @typeParam S - The settings shape; defaults to {@link DragSortSettings}.
 */
export class DragSort<
  S extends DragSortSettings = DragSortSettings,
> extends Drag<S> {
  /** Default {@link DragSortSettings}. */
  static override readonly defaults: DragSortSettings = {
    ...Drag.defaults,
    container: null,
    insertion: null,
    moveTargetItemToFront: false,
    magnetStrength: 1,
    onInsertionPointChange: noop,
    onSortChange: noop,
    canInsertBefore: () => true,
    canInsertAfter: () => true,
  };

  // --- Public state (legacy `$`-prefixed names; now native) -----------------

  /** Nearest ancestor of `settings.container` that has a height, or `null`. */
  $heightedContainer: HTMLElement | null = null;
  /** The insertion placeholder element, or `null` (legacy: jQuery). */
  $insertion: HTMLElement | null = null;
  /** Whether the insertion placeholder is currently in the DOM. */
  insertionVisible = false;
  /** Draggee indexes (into `$items`) captured at drag start. */
  oldDraggeeIndexes: number[] | null = null;
  /** Draggee indexes (into `$items`) captured on drop. */
  newDraggeeIndexes: number[] | null = null;
  /** The item the draggee is currently closest to, or `null`. */
  closestItem: HTMLElement | null = null;

  // --- Private --------------------------------------------------------------

  /** Per-drag midpoint cache (replaces `$.data(item, 'midpoint')`). */
  private _allMidpoints: Map<Element, Midpoint> | null = null;
  /** Bumped on every `_clearMidpoints` (parity/debug; cache is the real store). */
  private _midpointVersion = 0;

  /**
   * @param items - Elements draggable right away, or — via the legacy
   *   param-shift — a `Partial<S>` settings object when no second arg is given.
   * @param settings - Optional settings overrides (see {@link DragSortSettings}).
   */
  constructor(
    items?: DragItemsInput | Partial<S> | null,
    settings?: Partial<S>
  ) {
    let resolvedItems: DragItemsInput | null =
      (items as DragItemsInput) ?? null;
    if (settings === undefined && isPlainObject(items)) {
      settings = items as Partial<S>;
      resolvedItems = null;
    }

    super(resolvedItems, {
      ...(DragSort.defaults as Partial<S>),
      ...(settings ?? {}),
    });
  }

  // --- Insertion / gating ---------------------------------------------------

  /** Build the insertion placeholder element from `settings.insertion`. */
  createInsertion(): HTMLElement | null {
    const {insertion} = this.settings!;
    if (!insertion) {
      return null;
    }
    const value =
      typeof insertion === 'function' ? insertion(this.$draggee) : insertion;
    return this._toInsertionElement(value);
  }

  /** Whether the draggee may be inserted before `item` (delegates to settings). */
  canInsertBefore(item: HTMLElement): boolean {
    return this.settings!.canInsertBefore(item);
  }

  /** Whether the draggee may be inserted after `item` (delegates to settings). */
  canInsertAfter(item: HTMLElement): boolean {
    return this.settings!.canInsertAfter(item);
  }

  // --- Helper geometry overrides (magnetStrength) ---------------------------

  /** The helper's target X — applies `magnetStrength` rubber-banding when set. */
  override getHelperTargetX(real = false): number {
    if (this.settings!.magnetStrength !== 1 && this.$draggee[0]) {
      const offsetX = getOffset(this.$draggee[0]).left;
      return (
        offsetX +
        (this.mouseX! - this.mouseOffsetX! - offsetX) /
          this.settings!.magnetStrength
      );
    }
    return super.getHelperTargetX(real);
  }

  /** The helper's target Y — applies `magnetStrength` rubber-banding when set. */
  override getHelperTargetY(real = false): number {
    if (this.settings!.magnetStrength !== 1 && this.$draggee[0]) {
      const offsetY = getOffset(this.$draggee[0]).top;
      return (
        offsetY +
        (this.mouseY! - this.mouseOffsetY! - offsetY) /
          this.settings!.magnetStrength
      );
    }
    return super.getHelperTargetY(real);
  }

  // --- Lifecycle hooks ------------------------------------------------------

  /** Capture order, create the insertion, precalc midpoints, then emit dragStart. */
  override onDragStart(): void {
    this.oldDraggeeIndexes = this._getDraggeeIndexes();

    // Move the target to the front of the draggee block if asked, and it isn't.
    if (
      this.settings!.moveTargetItemToFront &&
      this.$draggee.length > 1 &&
      this._getItemIndex(this.$draggee[0]!) >
        this._getItemIndex(this.$draggee[1]!)
    ) {
      this.$draggee[1]!.before(this.$draggee[0]!);
    }

    // Create + place the insertion placeholder.
    this.$insertion = this.createInsertion();
    this._placeInsertionWithDraggee();

    this.closestItem = null;
    this._clearMidpoints();

    // Resolve the nearest container ancestor that actually has a height.
    if (this.settings!.container) {
      let container =
        (coerceElements(this.settings!.container)[0] as HTMLElement | null) ??
        null;
      while (container && !getOuterHeight(container)) {
        container = container.parentElement;
      }
      this.$heightedContainer = container;
    }

    // PERFORMANCE: pre-calculate all midpoints in a single read pass to avoid
    // layout thrashing during the drag.
    this._precalculateMidpoints();

    super.onDragStart();
  }

  /** Detect a new closest item and update the insertion, then emit drag. */
  override onDrag(): void {
    if (
      this.$heightedContainer &&
      !hitTest(this.mouseX!, this.mouseY!, this.$heightedContainer)
    ) {
      // The cursor left the container — clear any insertion feedback.
      if (this.closestItem) {
        this.closestItem = null;
        this._removeInsertion();
      }
    } else {
      // Is there a new closest item?
      const prev = this.closestItem;
      this.closestItem = this._getClosestItem();
      if (prev !== this.closestItem && this.closestItem !== null) {
        this._updateInsertion();
      }
    }

    super.onDrag();
  }

  /** Commit the new order, return helpers, emit dragStop + sortChange. */
  override onDragStop(): void {
    this._removeInsertion();

    // Keep the target where it was, unless we moved it to the front.
    if (
      !this.settings!.moveTargetItemToFront &&
      this.targetItemPositionInDraggee !== 0 &&
      this.$targetItem
    ) {
      const ref = this.$draggee[this.targetItemPositionInDraggee!];
      if (ref) {
        ref.after(this.$targetItem);
      }
    }

    // Return the helpers to the draggees (DragSort auto-returns; legacy parity).
    this.returnHelpersToDraggees();

    super.onDragStop();

    // Has the order actually changed?
    this.$items = this._sortItemsByDomOrder(this.$items);
    this.newDraggeeIndexes = this._getDraggeeIndexes();

    if (
      this.newDraggeeIndexes.join(',') !==
      (this.oldDraggeeIndexes ?? []).join(',')
    ) {
      this.onSortChange();
    }
  }

  // --- Event hooks ----------------------------------------------------------

  /** RAF-deferred: emit `insertionPointChange` + run the settings callback. */
  onInsertionPointChange(): void {
    requestAnimationFrame(() => {
      this.trigger('insertionPointChange');
      this.settings!.onInsertionPointChange();
    });
  }

  /** RAF-deferred: emit `sortChange` + run the settings callback. */
  onSortChange(): void {
    requestAnimationFrame(() => {
      this.trigger('sortChange');
      this.settings!.onSortChange();
    });
  }

  // --- Private --------------------------------------------------------------

  /** Single read pass populating the midpoint cache for every tracked item. */
  private _precalculateMidpoints(): void {
    this._allMidpoints = new Map();

    const scrollX = win.scrollX;
    const scrollY = win.scrollY;

    for (const item of this.$items) {
      this._allMidpoints.set(
        item,
        this._measureMidpoint(item, scrollX, scrollY)
      );
    }
  }

  /** Items currently within (or near) the viewport — used for large lists. */
  private _getVisibleItems(): HTMLElement[] {
    const viewportTop = win.scrollY;
    const viewportBottom = viewportTop + win.innerHeight;
    const visible: HTMLElement[] = [];

    for (const item of this.$items) {
      // Skip items inside the draggee.
      if (this.$draggee[0] && this.$draggee[0].contains(item)) {
        continue;
      }
      const mp = this._allMidpoints?.get(item);
      if (!mp) {
        continue;
      }
      if (
        mp.bottom >= viewportTop - VIEWPORT_BUFFER &&
        mp.top <= viewportBottom + VIEWPORT_BUFFER
      ) {
        visible.push(item as HTMLElement);
      }
    }

    return visible;
  }

  /** Index of an item within `$items` (replaces `this.$items.index(item)`). */
  private _getItemIndex(item: Element): number {
    return this.$items.indexOf(item);
  }

  /** Indexes (into `$items`) of every draggee, in draggee order. */
  private _getDraggeeIndexes(): number[] {
    return this.$draggee.map((item) => this._getItemIndex(item));
  }

  /** The closest insertable item to the draggee's virtual midpoint, or `null`. */
  private _getClosestItem(): HTMLElement | null {
    let closest: HTMLElement | null = null;
    let closestDist = 0;
    const axis = this.settings!.axis;
    const vx = this.draggeeVirtualMidpointX!;
    const vy = this.draggeeVirtualMidpointY!;

    const test = (item: HTMLElement): void => {
      const mp = this._getItemMidpoint(item);
      const dx = Math.abs(mp.x - vx);
      const dy = Math.abs(mp.y - vy);
      const dist =
        axis === X_AXIS
          ? dx
          : axis === Y_AXIS
            ? dy
            : Math.sqrt(dx ** 2 + dy ** 2);
      if (closest === null || dist < closestDist) {
        closest = item;
        closestDist = dist;
      }
    };

    // PERFORMANCE: for large lists, only scan items near the viewport.
    const visibleItems =
      this._allMidpoints && this.$items.length > LARGE_LIST_THRESHOLD
        ? this._getVisibleItems()
        : null;

    // Seed with the draggee itself (or the visible insertion when removeDraggee).
    if (!this.settings!.removeDraggee && this.$draggee[0]) {
      test(this.$draggee[0]);
    } else if (this.insertionVisible && this.$insertion) {
      test(this.$insertion);
    }

    // Baseline distances off the seed.
    let lastX: number | null = null;
    let lastY: number | null = null;
    let startX: number | null = null;
    let startY: number | null = null;
    if (closest) {
      const mp = this._getItemMidpoint(closest);
      if (axis !== Y_AXIS) {
        startX = lastX = Math.abs(mp.x - vx);
      }
      if (axis !== X_AXIS) {
        startY = lastY = Math.abs(mp.y - vy);
      }
    }

    const walk = (
      next: (item: Element) => Element | null,
      from: Element | null
    ): void => {
      let other = from as HTMLElement | null;
      while (other) {
        const mp = this._getItemMidpoint(other);
        const dx = axis !== Y_AXIS ? Math.abs(mp.x - vx) : 0;
        const dy = axis !== X_AXIS ? Math.abs(mp.y - vy) : 0;
        // Skip testing once we're receding on every relevant axis (but keep
        // walking) — the cheap monotonic-distance early-out from legacy.
        const receding =
          (axis === Y_AXIS || (lastX !== null && dx > lastX)) &&
          (axis === X_AXIS || (lastY !== null && dy > lastY));
        if (!receding) {
          if (axis !== Y_AXIS) {
            lastX = dx;
          }
          if (axis !== X_AXIS) {
            lastY = dy;
          }
          if (this.canInsertBefore(other) || this.canInsertAfter(other)) {
            test(other);
          }
        }
        other = next(other) as HTMLElement | null;
      }
    };

    if (visibleItems) {
      for (const item of visibleItems) {
        if (this.canInsertBefore(item) || this.canInsertAfter(item)) {
          test(item);
        }
      }
    } else {
      // Items before the draggee, walking backward.
      walk(
        (item) => this.getPrevItem(item),
        this.$draggee[0] ? this.getPrevItem(this.$draggee[0]) : null
      );
      // Reset for the forward pass.
      lastX = startX;
      lastY = startY;
      // Items after the draggee, walking forward.
      const last = this.$draggee[this.$draggee.length - 1];
      walk(
        (item) => this.getNextItem(item),
        last ? this.getNextItem(last) : null
      );
    }

    // Ignore a "closest" that is the draggee/insertion itself.
    if (
      closest !== this.$draggee[0] &&
      (!this.insertionVisible || closest !== this.$insertion)
    ) {
      return closest;
    }
    return null;
  }

  /** Invalidate the midpoint cache (bumps the version for parity/debug). */
  private _clearMidpoints(): void {
    this._midpointVersion++;
    this._allMidpoints = null;
  }

  /**
   * The cached midpoint for an item, or a fresh measurement for elements not in
   * the cache (only the insertion placeholder; see doc 11 §2.1 deviation).
   */
  private _getItemMidpoint(item: Element): Midpoint {
    const cached = this._allMidpoints?.get(item);
    if (cached) {
      return cached;
    }
    return this._measureMidpoint(item, win.scrollX, win.scrollY);
  }

  /** Measure an element's page-coords midpoint + box from its bounding rect. */
  private _measureMidpoint(
    item: Element,
    scrollX: number,
    scrollY: number
  ): Midpoint {
    const rect = item.getBoundingClientRect();
    return {
      x: rect.left + scrollX + rect.width / 2,
      y: rect.top + scrollY + rect.height / 2,
      width: rect.width,
      height: rect.height,
      top: rect.top + scrollY,
      bottom: rect.bottom + scrollY,
    };
  }

  /** Move the draggee to the closest item, refresh affected midpoints, notify. */
  private _updateInsertion(): void {
    if (this.closestItem) {
      this._moveDraggeeToItem(this.closestItem);
    }

    // PERFORMANCE: recompute only the moved item + its neighbors, not the list.
    if (this._allMidpoints && this.closestItem) {
      const toUpdate: Element[] = [this.closestItem];
      const prev = this.getPrevItem(this.closestItem);
      const next = this.getNextItem(this.closestItem);
      if (prev) {
        toUpdate.push(prev);
      }
      if (next) {
        toUpdate.push(next);
      }

      const scrollX = win.scrollX;
      const scrollY = win.scrollY;
      for (const item of toUpdate) {
        this._allMidpoints.set(
          item,
          this._measureMidpoint(item, scrollX, scrollY)
        );
      }
    } else {
      this._clearMidpoints();
    }

    this.onInsertionPointChange();
  }

  /** Re-insert the whole draggee block before/after `item`, place the insertion. */
  private _moveDraggeeToItem(item: HTMLElement): void {
    const first = this.$draggee[0];
    if (!first) {
      return;
    }

    const sameParent = first.parentNode === item.parentNode;
    const goingDown =
      this.canInsertAfter(item) &&
      sameParent &&
      this._siblingIndex(first) < this._siblingIndex(item);

    if (goingDown) {
      item.after(...this.$draggee);
    } else {
      item.before(...this.$draggee);
    }

    this._placeInsertionWithDraggee();
    this.$items = this._sortItemsByDomOrder(this.$items);
  }

  /** Put the insertion placeholder immediately before the first draggee. */
  private _placeInsertionWithDraggee(): void {
    if (this.$insertion && this.$draggee[0]) {
      this.$draggee[0].before(this.$insertion);
      this.insertionVisible = true;
    }
  }

  /** Remove the insertion placeholder from the DOM, if visible. */
  private _removeInsertion(): void {
    if (this.insertionVisible && this.$insertion) {
      this.$insertion.remove();
      this.insertionVisible = false;
    }
  }

  /** Sort a list of elements into document order (replaces jQuery `$().add()`). */
  private _sortItemsByDomOrder(items: Element[]): Element[] {
    return [...items].sort((a, b) =>
      a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1
    );
  }

  /** An element's index among its element siblings (replaces jQuery `.index()`). */
  private _siblingIndex(el: Element): number {
    const parent = el.parentNode;
    if (!parent) {
      return -1;
    }
    return Array.prototype.indexOf.call(parent.children, el);
  }

  /** Coerce an insertion value (markup string or element) into an element. */
  private _toInsertionElement(value: HTMLElement | string): HTMLElement | null {
    if (typeof value === 'string') {
      const template = document.createElement('template');
      template.innerHTML = value.trim();
      return template.content.firstElementChild as HTMLElement | null;
    }
    return value ?? null;
  }
}

export default DragSort;
