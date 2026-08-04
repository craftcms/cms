/**
 * Select — modern, jQuery-free TypeScript port of Garnish's `Select`.
 *
 * A selection interface over a set of sibling items: click to select, shift-click
 * to extend a range, and ctrl/⌘-click to toggle individual items (the ctrl/shift
 * roles swap under `checkboxMode`). Selected items get the `selectedClass` (`sel`
 * by default); arrow keys move the selection two-dimensionally by measuring item
 * geometry, so it works for both vertical lists and wrapping grids.
 *
 * jQuery removals (mirroring the `Drag`/`DragSort` ports):
 *
 * - `$()` collections → native `HTMLElement[]` (`$items`, `$selectedItems`).
 * - `$.data(item, 'select')` cross-selector guard → a module-scoped
 *   {@link itemOwners} `WeakMap`; `$.data(item, 'select-handle')` /
 *   `$handle.data('select-item')` → per-instance {@link itemHandles} /
 *   {@link itemCheckboxes} `WeakMap`s + closures that capture the item.
 * - `$item.offset()` / `.outerWidth()` / `.outerHeight()` (the 2D nav geometry)
 *   → `getOffset` / `getOuterWidth` / `getOuterHeight`.
 * - `$item.is(':focusable')` / `.find(':focusable:first')` → `isFocusable` /
 *   `getFocusableElements(item)[0]`.
 * - `Garnish.ltr/rtl` → `globals.ltr/rtl`; `Garnish.requestAnimationFrame` →
 *   `utils/animation`; `$.noop` → `() => {}`.
 *
 * Unlike the legacy `Base.extend({init})` trampoline, this is a native
 * `class extends Base` with a real constructor (see {@link Base}).
 */

import {Base} from './base';
import {globals} from './globals';
import {
  X_AXIS,
  Y_AXIS,
  DOWN_KEY,
  LEFT_KEY,
  RIGHT_KEY,
  RETURN_KEY,
  SPACE_KEY,
  UP_KEY,
  A_KEY,
} from './constants';
import {
  coerceElements,
  getOffset,
  getOuterHeight,
  getOuterWidth,
} from './utils/dom';
import {isFocusable, getFocusableElements} from './utils/focusable';
import {isCtrlKeyPressed, isPrimaryClick} from './utils/env';
import {requestAnimationFrame, cancelAnimationFrame} from './utils/animation';
import {isPlainObject} from './utils/misc';
import type {GarnishEvent} from './events';
import type {ElementInput, GarnishBaseSettings} from './types';

// `keyCode` is deprecated but still populated on native KeyboardEvents in every
// browser Craft supports, and the rest of the garnish core (HUD, DisclosureMenu,
// EscManager, …) reads it against the numeric key constants — so this port does
// too, for consistency.

/** The handle for selecting an item: a selector, a function, an element, or `null` (the item itself). */
export type SelectHandle =
  | string
  | ((item: HTMLElement) => HTMLElement | HTMLElement[] | null)
  | HTMLElement
  | HTMLElement[]
  | null;

/** The click/keydown filter: a selector string or a predicate over the event target. */
export type SelectFilter =
  | string
  | ((target: EventTarget | null) => boolean)
  | null;

/** Settings accepted by {@link Select} (extends {@link GarnishBaseSettings}). */
export interface SelectSettings extends GarnishBaseSettings {
  /** Class added to selected items. */
  selectedClass: string;
  /** Class marking a checkbox affordance inside an item (gets `aria-checked`). */
  checkboxClass: string;
  /** Allow more than one item selected at a time. */
  multi: boolean;
  /** Allow the selection to be emptied (container click / last-item deselect). */
  allowEmpty: boolean;
  /** Constrain arrow-key navigation to a single vertical column. */
  vertical: boolean;
  /** Constrain arrow-key navigation to a single horizontal row. */
  horizontal: boolean;
  /** What within each item receives the pointer listeners; `null` → the item. */
  handle: SelectHandle;
  /** Gate: only start selecting when the pointer/key target passes this. */
  filter: SelectFilter;
  /** Whether a checkbox affordance (not ctrl/shift) drives selection. */
  checkboxMode: boolean;
  /** Give the focused item a roving `tabindex` (keyboard-navigable list). */
  makeFocusable: boolean;
  /** Defer single-click (de)selection briefly so a double-click can pre-empt it. */
  waitForDoubleClicks: boolean;
  /** Called (RAF-deferred) whenever the selection changes. */
  onSelectionChange: () => void;
}

const noop = (): void => {};

/**
 * Item → owning {@link Select}. Module-scoped (not per-instance) so adding an
 * item already claimed by another selector can warn and hand it over, matching
 * the legacy `$.data(item, 'select')` guard.
 */
const itemOwners = new WeakMap<Element, Select>();

/** Element the container is bound to → its {@link Select} (legacy `$container.data('select')`). */
const containerOwners = new WeakMap<Element, Select>();

/** Geometry lookups for {@link Select.getClosestItem}, keyed by axis. */
const closestItemAxisProps = {
  [X_AXIS]: {
    midpointOffset: 'top',
    midpointSize: getOuterHeight,
    rowOffset: 'left',
  },
  [Y_AXIS]: {
    midpointOffset: 'left',
    midpointSize: getOuterWidth,
    rowOffset: 'top',
  },
} as const;

/** Direction lookups for {@link Select.getClosestItem}, keyed by `'<'` / `'>'`. */
const closestItemDirectionProps = {
  '<': {
    step: -1,
    isNextRow: (a: number, b: number): boolean => a < b,
    isWrongDirection: (a: number, b: number): boolean => a > b,
  },
  '>': {
    step: 1,
    isNextRow: (a: number, b: number): boolean => a > b,
    isWrongDirection: (a: number, b: number): boolean => a < b,
  },
} as const;

/**
 * Selection interface over a set of sibling items — the modern, jQuery-free
 * port of `Garnish.Select`. See the file header for the porting notes.
 *
 * @typeParam S - The settings shape; defaults to {@link SelectSettings}.
 *
 * @fires selectionChange - (RAF-deferred) whenever the selected set changes.
 * @fires focusItem - `{item}` when keyboard/pointer focus moves to an item.
 */
export class Select<S extends SelectSettings = SelectSettings> extends Base<S> {
  /** Default {@link SelectSettings}. */
  static readonly defaults: SelectSettings = {
    selectedClass: 'sel',
    checkboxClass: 'checkbox',
    multi: false,
    allowEmpty: true,
    vertical: false,
    horizontal: false,
    handle: null,
    filter: null,
    checkboxMode: false,
    makeFocusable: false,
    waitForDoubleClicks: false,
    onSelectionChange: noop,
  };

  /** The container element (its click deselects, when `allowEmpty`). */
  $container: HTMLElement | null = null;

  /** All tracked items, in the order they were added. */
  $items: HTMLElement[] = [];

  /** The currently-selected items (a subset of {@link $items}). */
  $selectedItems: HTMLElement[] = [];

  /** The item that last received focus. */
  $focusedItem: HTMLElement | null = null;

  /** The anchor item/index of the current selection range (shift-select origin). */
  $first: HTMLElement | null = null;
  first: number | null = null;

  /** The far end item/index of the current selection range. */
  $last: HTMLElement | null = null;
  last: number | null = null;

  /** The single roving-`tabindex` item, when `makeFocusable`. */
  $focusable: HTMLElement | null = null;

  /** The handle whose `mousedown` began a potential click (resolved in `mouseup`). */
  private mousedownTarget: HTMLElement | null = null;
  /** Pending single-click handler timer (see `waitForDoubleClicks`). */
  private mouseUpTimeout: ReturnType<typeof setTimeout> | null = null;
  /** RAF handle coalescing `selectionChange` emissions. */
  private callbackFrame: number | null = null;
  /** Set by an item's own `click` so the container's deselect click can be ignored. */
  private ignoreClick = false;

  /** Handles bound for each item (the item itself when no `handle` setting). */
  private readonly itemHandles = new WeakMap<HTMLElement, HTMLElement[]>();
  /** The `.checkbox` affordance within each item, if any. */
  private readonly itemCheckboxes = new WeakMap<
    HTMLElement,
    HTMLElement | null
  >();

  /**
   * @param container - The container element (or, via param-shift, the settings
   *   or items when later args are omitted).
   * @param items - Items to track right away (selector / element / list).
   * @param settings - Settings overrides.
   */
  constructor(
    container?: ElementInput | Partial<S>,
    items?: ElementInput | Partial<S>,
    settings?: Partial<S>
  ) {
    super();

    // Param mapping (legacy parity):
    //   (settings)            — first arg is a plain object
    //   (container, settings) — second arg is a plain object
    //   (container, items, settings)
    let resolvedContainer: ElementInput = container as ElementInput;
    let resolvedItems: ElementInput = items as ElementInput;
    if (
      items === undefined &&
      settings === undefined &&
      isPlainObject(container)
    ) {
      settings = container as Partial<S>;
      resolvedContainer = null;
      resolvedItems = null;
    } else if (settings === undefined && isPlainObject(items)) {
      settings = items as Partial<S>;
      resolvedItems = null;
    }

    this.$container =
      (coerceElements(resolvedContainer)[0] as HTMLElement) ?? null;

    // Is this already a select?
    if (this.$container && containerOwners.has(this.$container)) {
      console.warn('Double-instantiating a select on an element');
      containerOwners.get(this.$container)!.destroy();
    }
    if (this.$container) {
      containerOwners.set(this.$container, this);
    }

    this.setSettings(settings, Select.defaults as Partial<S>);

    this.$items = [];
    this.$selectedItems = [];

    this.addItems(resolvedItems);

    if (
      this.$container &&
      this.settings!.allowEmpty &&
      !this.settings!.checkboxMode
    ) {
      this.addListener(this.$container, 'click', () => {
        if (this.ignoreClick) {
          this.ignoreClick = false;
        } else {
          // Deselect all items on container click.
          this.deselectAll(true);
        }
      });
    }
  }

  // --- Queries ----------------------------------------------------------------

  /** The index of an item within {@link $items}, or `-1`. */
  getItemIndex(item: HTMLElement): number {
    return this.$items.indexOf(item);
  }

  /** Whether an item is currently selected. */
  isSelected(item: HTMLElement | null): boolean {
    if (!item) {
      return false;
    }
    return this.$selectedItems.indexOf(item) !== -1;
  }

  /** The selected items, as a fresh array (legacy returned a fresh jQuery set). */
  getSelectedItems(): HTMLElement[] {
    return [...this.$selectedItems];
  }

  /** `totalSelected` getter (legacy parity). */
  get totalSelected(): number {
    return this.getTotalSelected();
  }

  /** How many items are selected. */
  getTotalSelected(): number {
    return this.$selectedItems.length;
  }

  // --- Selection --------------------------------------------------------------

  /** Select a single item, collapsing the range to it. */
  selectItem(
    item: HTMLElement,
    focus?: boolean,
    preventScroll?: boolean
  ): void {
    if (!this.settings!.multi) {
      this.deselectAll();
    }

    this.$first = this.$last = item;
    this.first = this.last = this.getItemIndex(item);

    if (focus) {
      this.focusItem(item, preventScroll);
    }

    this._selectItems([item]);
  }

  /** Select every item (multi only). */
  selectAll(): void {
    if (!this.settings!.multi || !this.$items.length) {
      return;
    }

    this.first = 0;
    this.last = this.$items.length - 1;
    this.$first = this.$items[this.first]!;
    this.$last = this.$items[this.last]!;

    this._selectItems([...this.$items]);
  }

  /** Extend the selection from the anchor to `item` (multi only). */
  selectRange(item: HTMLElement, preventScroll?: boolean): void {
    if (!this.settings!.multi) {
      this.selectItem(item, true, true);
      return;
    }

    this.deselectAll();

    this.$last = item;
    this.last = this.getItemIndex(item);

    this.focusItem(item, preventScroll);

    let sliceFrom: number;
    let sliceTo: number;
    if (this.first! < this.last) {
      sliceFrom = this.first!;
      sliceTo = this.last + 1;
    } else {
      sliceFrom = this.last;
      sliceTo = this.first! + 1;
    }

    this._selectItems(this.$items.slice(sliceFrom, sliceTo));
  }

  /** Deselect a single item, clearing the range anchor/end if it was one. */
  deselectItem(item: HTMLElement): void {
    const index = this.getItemIndex(item);
    if (this.first === index) {
      this.$first = null;
      this.first = null;
    }
    if (this.last === index) {
      this.$last = null;
      this.last = null;
    }

    this._deselectItems([item]);
  }

  /** Deselect everything; pass `clearFirst` to also drop the range anchor/end. */
  deselectAll(clearFirst?: boolean): void {
    if (clearFirst) {
      this.$first = this.first = this.$last = this.last = null;
    }

    this._deselectItems([...this.$items]);
  }

  /** Deselect everything else and select just `item`. */
  deselectOthers(item: HTMLElement): void {
    this.deselectAll();
    this.selectItem(item, true, true);
  }

  /** Toggle a single item's selection (respecting `_canDeselect`). */
  toggleItem(item: HTMLElement, preventScroll?: boolean): void {
    if (!this.isSelected(item)) {
      this.selectItem(item, true, preventScroll);
    } else if (this._canDeselect([item])) {
      this.deselectItem(item);
    }
  }

  // --- Navigation getters -----------------------------------------------------

  getFirstItem(): HTMLElement | undefined {
    return this.$items[0];
  }

  getLastItem(): HTMLElement | undefined {
    return this.$items[this.$items.length - 1];
  }

  isPreviousItem(index: number): boolean {
    return index > 0;
  }

  isNextItem(index: number): boolean {
    return index < this.$items.length - 1;
  }

  getPreviousItem(index: number): HTMLElement | undefined {
    return this.isPreviousItem(index) ? this.$items[index - 1] : undefined;
  }

  getNextItem(index: number): HTMLElement | undefined {
    return this.isNextItem(index) ? this.$items[index + 1] : undefined;
  }

  getItemToTheLeft(index: number): HTMLElement | undefined {
    const next = globals.ltr
      ? this.getPreviousItem(index)
      : this.getNextItem(index);
    const has = globals.ltr
      ? this.isPreviousItem(index)
      : this.isNextItem(index);
    if (!has) {
      return undefined;
    }
    if (this.settings!.horizontal) {
      return next;
    }
    if (!this.settings!.vertical) {
      return this.getClosestItem(index, X_AXIS, '<');
    }
    return undefined;
  }

  getItemToTheRight(index: number): HTMLElement | undefined {
    const next = globals.ltr
      ? this.getNextItem(index)
      : this.getPreviousItem(index);
    const has = globals.ltr
      ? this.isNextItem(index)
      : this.isPreviousItem(index);
    if (!has) {
      return undefined;
    }
    if (this.settings!.horizontal) {
      return next;
    }
    if (!this.settings!.vertical) {
      return this.getClosestItem(index, X_AXIS, '>');
    }
    return undefined;
  }

  getItemAbove(index: number): HTMLElement | undefined {
    if (!this.isPreviousItem(index)) {
      return undefined;
    }
    if (this.settings!.vertical) {
      return this.getPreviousItem(index);
    }
    if (!this.settings!.horizontal) {
      return this.getClosestItem(index, Y_AXIS, '<');
    }
    return undefined;
  }

  getItemBelow(index: number): HTMLElement | undefined {
    if (!this.isNextItem(index)) {
      return undefined;
    }
    if (this.settings!.vertical) {
      return this.getNextItem(index);
    }
    if (!this.settings!.horizontal) {
      return this.getClosestItem(index, Y_AXIS, '>');
    }
    return undefined;
  }

  /**
   * The nearest item to `index` on the next row/column in a direction — the 2D
   * geometry that lets arrow keys traverse a wrapping grid. Ported from the
   * legacy midpoint-scan; reads live layout via `getOffset`/`getOuter*`.
   */
  getClosestItem(
    index: number,
    axis: typeof X_AXIS | typeof Y_AXIS,
    dir: '<' | '>'
  ): HTMLElement | undefined {
    const axisProps = closestItemAxisProps[axis];
    const dirProps = closestItemDirectionProps[dir];

    const thisItem = this.$items[index]!;
    const thisOffset = getOffset(thisItem);
    const thisMidpoint =
      thisOffset[axisProps.midpointOffset] +
      Math.round(axisProps.midpointSize(thisItem) / 2);

    let otherRowPos: number | null = null;
    let smallestMidpointDiff: number | null = null;
    let closestItem: HTMLElement | undefined;

    // Go the other way if this is the X axis on an RTL page.
    const step =
      globals.rtl && axis === X_AXIS ? dirProps.step * -1 : dirProps.step;

    for (
      let i = index + step;
      typeof this.$items[i] !== 'undefined';
      i += step
    ) {
      const otherItem = this.$items[i]!;
      const otherOffset = getOffset(otherItem);

      // Are we on the next row yet?
      if (
        dirProps.isNextRow(
          otherOffset[axisProps.rowOffset],
          thisOffset[axisProps.rowOffset]
        )
      ) {
        // Is this the first time we've seen this row?
        if (otherRowPos === null) {
          otherRowPos = otherOffset[axisProps.rowOffset];
        } else if (otherOffset[axisProps.rowOffset] !== otherRowPos) {
          // Have we gone too far?
          break;
        }

        const otherMidpoint =
          otherOffset[axisProps.midpointOffset] +
          Math.round(axisProps.midpointSize(otherItem) / 2);
        const midpointDiff = Math.abs(thisMidpoint - otherMidpoint);

        // Are we getting warmer?
        if (
          smallestMidpointDiff === null ||
          midpointDiff < smallestMidpointDiff
        ) {
          smallestMidpointDiff = midpointDiff;
          closestItem = otherItem;
        } else {
          // Getting colder?
          break;
        }
      } else if (
        dirProps.isWrongDirection(
          otherOffset[axisProps.rowOffset],
          thisOffset[axisProps.rowOffset]
        )
      ) {
        // Getting colder?
        break;
      }
    }

    return closestItem;
  }

  getFurthestItemToTheLeft(index: number): HTMLElement | undefined {
    return this.getFurthestItem(index, 'getItemToTheLeft');
  }

  getFurthestItemToTheRight(index: number): HTMLElement | undefined {
    return this.getFurthestItem(index, 'getItemToTheRight');
  }

  getFurthestItemAbove(index: number): HTMLElement | undefined {
    return this.getFurthestItem(index, 'getItemAbove');
  }

  getFurthestItemBelow(index: number): HTMLElement | undefined {
    return this.getFurthestItem(index, 'getItemBelow');
  }

  private getFurthestItem(
    index: number,
    getter:
      | 'getItemToTheLeft'
      | 'getItemToTheRight'
      | 'getItemAbove'
      | 'getItemBelow'
  ): HTMLElement | undefined {
    let item: HTMLElement | undefined;
    let testItem: HTMLElement | undefined;

    while ((testItem = this[getter](index))) {
      item = testItem;
      index = this.getItemIndex(item);
    }

    return item;
  }

  // --- Item management --------------------------------------------------------

  /** Track item(s) for selection and bind their pointer/keyboard listeners. */
  addItems(items: ElementInput): void {
    const elements = coerceElements(items).filter(
      (el): el is HTMLElement => el instanceof HTMLElement
    );

    for (const item of elements) {
      // Make sure this element doesn't belong to another selector (and isn't
      // already ours — re-adding would duplicate it in `$items`).
      const owner = itemOwners.get(item);
      if (owner === this) {
        continue;
      }
      if (owner) {
        console.warn('Element was added to more than one selector');
        owner.removeItems(item);
      }

      itemOwners.set(item, this);

      const handles = this._resolveHandles(item);
      this.itemHandles.set(item, handles);

      const checkbox = this.settings!.checkboxClass
        ? item.querySelector<HTMLElement>(`.${this.settings!.checkboxClass}`)
        : null;
      this.itemCheckboxes.set(item, checkbox);

      for (const handle of handles) {
        this.addListener(handle, 'mousedown', (ev: GarnishEvent) => {
          this.onMouseDown(ev as unknown as MouseEvent, item, handle);
        });
        this.addListener(handle, 'mouseup', (ev: GarnishEvent) => {
          this.onMouseUp(ev as unknown as MouseEvent, item, handle);
        });
        this.addListener(handle, 'click', () => {
          this.ignoreClick = true;
        });
      }

      if (checkbox) {
        this.addListener(checkbox, 'keydown', (ev: GarnishEvent) => {
          const kev = ev as unknown as KeyboardEvent;
          if (
            (kev.keyCode === RETURN_KEY || kev.keyCode === SPACE_KEY) &&
            !kev.shiftKey &&
            !isCtrlKeyPressed(kev)
          ) {
            kev.preventDefault();
            this.onCheckboxActivate(kev, item);
          }
        });
      }

      this.addListener(item, 'keydown', (ev: GarnishEvent) => {
        this.onKeyDown(ev as unknown as KeyboardEvent, item);
      });

      this.$items.push(item);
    }

    this.updateIndexes();
  }

  /** Stop tracking item(s), unbinding listeners and dropping them from the selection. */
  removeItems(items: ElementInput): void {
    const elements = coerceElements(items).filter(
      (el): el is HTMLElement => el instanceof HTMLElement
    );

    let itemsChanged = false;
    let selectionChanged = false;

    for (const item of elements) {
      const index = this.$items.indexOf(item);
      if (index !== -1) {
        this._deinitItem(item);
        this.$items.splice(index, 1);
        itemsChanged = true;

        const selectedIndex = this.$selectedItems.indexOf(item);
        if (selectedIndex !== -1) {
          this.$selectedItems.splice(selectedIndex, 1);
          selectionChanged = true;
        }
      }
    }

    if (itemsChanged) {
      this.updateIndexes();

      if (selectionChanged) {
        for (const item of elements) {
          item.classList.remove(this.settings!.selectedClass);
        }
        this.onSelectionChange();
      }
    }
  }

  /** Stop tracking every item. */
  removeAllItems(): void {
    for (const item of this.$items) {
      this._deinitItem(item);
    }

    this.$items = [];
    this.$selectedItems = [];
    this.updateIndexes();
  }

  /** Recompute the first/last indexes and the roving-focus item. */
  updateIndexes(): void {
    if (this.first !== null && this.$first) {
      this.first = this.getItemIndex(this.$first);
      this.setFocusableItem(this.$first);
    } else if (this.$items.length) {
      this.setFocusableItem(this.$items[0]!);
    }

    if (this.$focusedItem) {
      this.focusItem(this.$focusedItem, true);
    }

    if (this.last !== null && this.$last) {
      this.last = this.getItemIndex(this.$last);
    }
  }

  /**
   * Re-sort {@link $items} / {@link $selectedItems} into current DOM order — call
   * after the items have been reordered in the DOM (legacy `resetItemOrder`).
   */
  resetItemOrder(): void {
    this.$items = this._sortByDomOrder(this.$items);
    this.$selectedItems = this._sortByDomOrder(this.$selectedItems);
    this.updateIndexes();
  }

  /**
   * Give a single item the roving `tabindex="0"` (when `makeFocusable`), so the
   * list is a single tab stop rather than one stop per item.
   */
  setFocusableItem(item: HTMLElement): void {
    if (this.settings!.makeFocusable) {
      if (this.$focusable) {
        this.$focusable.removeAttribute('tabindex');
      }
      item.setAttribute('tabindex', '0');
      this.$focusable = item;
    }
  }

  /** Move DOM focus onto an item (or its first focusable descendant). */
  focusItem(item: HTMLElement, preventScroll?: boolean): void {
    let focusableElement: HTMLElement | null;
    if (this.settings!.makeFocusable) {
      this.setFocusableItem(item);
      focusableElement = item;
    } else if (isFocusable(item)) {
      focusableElement = item;
    } else {
      focusableElement = getFocusableElements(item)[0] ?? null;
    }

    if (focusableElement) {
      focusableElement.focus({preventScroll: !!preventScroll});
    }

    this.$focusedItem = item;
    this.trigger('focusItem', {item});
  }

  // --- Events -----------------------------------------------------------------

  /** Pointer-down on a handle: begin a shift-range, checkbox-toggle, or pending click. */
  onMouseDown(ev: MouseEvent, item: HTMLElement, handle: HTMLElement): void {
    this.mousedownTarget = null;

    // Ignore right/ctrl-clicks.
    if (!isPrimaryClick(ev) && !isCtrlKeyPressed(ev)) {
      return;
    }

    // Enforce the filter.
    if (this.settings!.filter && !this._passesFilter(ev.target)) {
      return;
    }

    if (this.first !== null && ev.shiftKey) {
      // Shift key is consistent for both selection modes.
      this.selectRange(item, true);
    } else if (
      this._actAsCheckbox(ev) &&
      (!this.settings!.waitForDoubleClicks || !this.isSelected(item))
    ) {
      // Checkbox-style deselection is handled from onMouseUp().
      this.toggleItem(item, true);
    } else {
      // Prepare for click handling in onMouseUp().
      this.mousedownTarget = handle;
    }
  }

  /** Pointer-up on a handle: resolve a plain click into (de)selection. */
  onMouseUp(ev: MouseEvent, item: HTMLElement, handle: HTMLElement): void {
    // Ignore right clicks.
    if (!isPrimaryClick(ev) && !isCtrlKeyPressed(ev)) {
      return;
    }

    // Enforce the filter (legacy applied jQuery `.is()` semantics here).
    if (this.settings!.filter && !this._passesFilterIs(ev.target)) {
      return;
    }

    // Was this a click?
    if (!ev.shiftKey && handle === this.mousedownTarget) {
      if (this.isSelected(item)) {
        const handler = (): void => {
          if (this._actAsCheckbox(ev)) {
            this.deselectItem(item);
          } else {
            this.deselectOthers(item);
          }
        };

        if (this.settings!.waitForDoubleClicks) {
          // Wait a moment to see if this is a double click before deciding.
          this.clearMouseUpTimeout();
          this.mouseUpTimeout = setTimeout(handler, 300);
        } else {
          handler();
        }
      } else if (!this._actAsCheckbox(ev)) {
        // Checkbox-style selection is handled from onMouseDown().
        this.deselectAll();
        this.selectItem(item, true, true);
      }
    }
  }

  /** Return/Space on a checkbox affordance: toggle that item's selection. */
  onCheckboxActivate(ev: KeyboardEvent, item: HTMLElement): void {
    ev.stopImmediatePropagation();

    if (!this.isSelected(item)) {
      this.selectItem(item);
    } else {
      this.deselectItem(item);
    }
  }

  /** Keyboard navigation/selection over the items (arrows, space, ctrl+A). */
  onKeyDown(ev: KeyboardEvent, item: HTMLElement): void {
    // Ignore if the focus isn't on this item, its handle, or its checkbox.
    const itemIsTarget = ev.target === item;
    const handleIsTarget = (this.itemHandles.get(item) ?? []).includes(
      ev.target as HTMLElement
    );
    const checkboxIsTarget =
      !!this.settings!.checkboxClass &&
      ev.target instanceof Element &&
      ev.target.classList.contains(this.settings!.checkboxClass);

    if (!itemIsTarget && !handleIsTarget && !checkboxIsTarget) {
      return;
    }

    const ctrlKey = isCtrlKeyPressed(ev);
    const shiftKey = ev.shiftKey;

    let anchor: number;
    if (!this.settings!.checkboxMode || !this.$focusable) {
      anchor = (ev.shiftKey ? this.last : this.first) ?? 0;
    } else {
      anchor = this.$items.indexOf(this.$focusable);
      if (anchor === -1) {
        anchor = 0;
      }
    }

    let nextItem: HTMLElement | undefined;

    switch (ev.keyCode) {
      case LEFT_KEY: {
        ev.preventDefault();
        if (this.first === null) {
          nextItem = globals.ltr ? this.getLastItem() : this.getFirstItem();
        } else if (ctrlKey) {
          nextItem = this.getFurthestItemToTheLeft(anchor);
        } else {
          nextItem = this.getItemToTheLeft(anchor);
        }
        break;
      }

      case RIGHT_KEY: {
        ev.preventDefault();
        if (this.first === null) {
          nextItem = globals.ltr ? this.getFirstItem() : this.getLastItem();
        } else if (ctrlKey) {
          nextItem = this.getFurthestItemToTheRight(anchor);
        } else {
          nextItem = this.getItemToTheRight(anchor);
        }
        break;
      }

      case UP_KEY: {
        ev.preventDefault();
        if (this.first === null) {
          if (this.$focusable) {
            nextItem =
              (this.$focusable.previousElementSibling as HTMLElement | null) ??
              undefined;
          }
          if (!this.$focusable || !nextItem) {
            nextItem = this.getLastItem();
          }
        } else {
          nextItem = ctrlKey
            ? this.getFurthestItemAbove(anchor)
            : this.getItemAbove(anchor);
          if (!nextItem) {
            nextItem = this.getFirstItem();
          }
        }
        break;
      }

      case DOWN_KEY: {
        ev.preventDefault();
        if (this.first === null) {
          if (this.$focusable) {
            nextItem =
              (this.$focusable.nextElementSibling as HTMLElement | null) ??
              undefined;
          }
          if (!this.$focusable || !nextItem) {
            nextItem = this.getFirstItem();
          }
        } else {
          nextItem = ctrlKey
            ? this.getFurthestItemBelow(anchor)
            : this.getItemBelow(anchor);
          if (!nextItem) {
            nextItem = this.getLastItem();
          }
        }
        break;
      }

      case SPACE_KEY: {
        if (!ctrlKey && !shiftKey) {
          ev.preventDefault();
          if (this.isSelected(this.$focusable)) {
            if (this.$focusable && this._canDeselect([this.$focusable])) {
              this.deselectItem(this.$focusable);
            }
          } else if (this.$focusable) {
            this.selectItem(this.$focusable, true, false);
          }
        }
        break;
      }

      case A_KEY: {
        if (ctrlKey) {
          ev.preventDefault();
          this.selectAll();
        }
        break;
      }
    }

    // Is there an item queued up for focus/selection?
    if (nextItem) {
      if (!this.settings!.checkboxMode) {
        if (this.first !== null && ev.shiftKey) {
          this.selectRange(nextItem, false);
        } else {
          this.deselectAll();
          this.selectItem(nextItem, true, false);
        }
      } else {
        // Just set the new item to be focusable.
        this.setFocusableItem(nextItem);
        if (this.settings!.makeFocusable) {
          nextItem.focus();
        }
        this.$focusedItem = nextItem;
        this.trigger('focusItem', {item: nextItem});
      }
    }
  }

  /** Emit `selectionChange` on the next frame, coalescing bursts. */
  onSelectionChange(): void {
    if (this.callbackFrame) {
      cancelAnimationFrame(this.callbackFrame);
      this.callbackFrame = null;
    }

    this.callbackFrame = requestAnimationFrame(() => {
      this.callbackFrame = null;
      this.trigger('selectionChange');
      this.settings!.onSelectionChange();
    });
  }

  /** Cancel a pending single-click timer (see `waitForDoubleClicks`). */
  clearMouseUpTimeout(): void {
    if (this.mouseUpTimeout) {
      clearTimeout(this.mouseUpTimeout);
      this.mouseUpTimeout = null;
    }
  }

  /** Tear down: release the container/items and run the base teardown. */
  override destroy(): void {
    if (this.$container) {
      containerOwners.delete(this.$container);
    }
    this.removeAllItems();
    super.destroy();
  }

  // --- Private ----------------------------------------------------------------

  /** Whether this event should act as a checkbox toggle (ctrl inverts `checkboxMode`). */
  private _actAsCheckbox(ev: MouseEvent): boolean {
    if (isCtrlKeyPressed(ev)) {
      return !this.settings!.checkboxMode;
    }
    return this.settings!.checkboxMode;
  }

  /** Whether the given items may be deselected without violating `allowEmpty`. */
  private _canDeselect(items: HTMLElement[]): boolean {
    return this.settings!.allowEmpty || this.totalSelected > items.length;
  }

  private _selectItems(items: HTMLElement[]): void {
    for (const item of items) {
      item.classList.add(this.settings!.selectedClass);

      if (this.settings!.checkboxClass) {
        for (const checkbox of item.querySelectorAll(
          `.${this.settings!.checkboxClass}`
        )) {
          checkbox.setAttribute('aria-checked', 'true');
        }
      }

      if (this.$selectedItems.indexOf(item) === -1) {
        this.$selectedItems.push(item);
      }
    }

    this.onSelectionChange();
  }

  private _deselectItems(items: HTMLElement[]): void {
    for (const item of items) {
      item.classList.remove(this.settings!.selectedClass);

      if (this.settings!.checkboxClass) {
        for (const checkbox of item.querySelectorAll(
          `.${this.settings!.checkboxClass}`
        )) {
          checkbox.setAttribute('aria-checked', 'false');
        }
      }

      const index = this.$selectedItems.indexOf(item);
      if (index !== -1) {
        this.$selectedItems.splice(index, 1);
      }
    }

    this.onSelectionChange();
  }

  private _deinitItem(item: HTMLElement): void {
    const handles = this.itemHandles.get(item) ?? [];
    for (const handle of handles) {
      this.removeAllListeners(handle);
    }

    const checkbox = this.itemCheckboxes.get(item);
    if (checkbox) {
      this.removeAllListeners(checkbox);
    }

    this.removeAllListeners(item);

    this.itemHandles.delete(item);
    this.itemCheckboxes.delete(item);
    if (itemOwners.get(item) === this) {
      itemOwners.delete(item);
    }

    if (this.$focusedItem === item) {
      this.$focusedItem = null;
    }
    if (this.$focusable === item) {
      this.$focusable = null;
    }
  }

  /** Resolve the handle element(s) for an item from the `handle` setting. */
  private _resolveHandles(item: HTMLElement): HTMLElement[] {
    const handle = this.settings!.handle;
    if (!handle) {
      return [item];
    }
    if (typeof handle === 'string') {
      return Array.from(item.querySelectorAll<HTMLElement>(handle));
    }
    if (typeof handle === 'function') {
      const resolved = handle(item);
      return this._toElements(resolved);
    }
    return this._toElements(handle);
  }

  private _toElements(
    value: HTMLElement | HTMLElement[] | null
  ): HTMLElement[] {
    if (!value) {
      return [];
    }
    return Array.isArray(value) ? value.filter(Boolean) : [value];
  }

  /** Filter check for `mousedown`/`keydown` — the direct `filter(target)` form. */
  private _passesFilter(target: EventTarget | null): boolean {
    const filter = this.settings!.filter;
    if (!filter) {
      return true;
    }
    if (typeof filter === 'function') {
      return filter(target);
    }
    return target instanceof Element && target.matches(filter);
  }

  /**
   * Filter check for `mouseup`, replicating legacy `$(target).is(filter)`:
   * jQuery `.is(fn)` invokes `fn(index, element)` with `this === element`, a
   * different call shape than {@link _passesFilter}. Preserved for exact parity.
   */
  private _passesFilterIs(target: EventTarget | null): boolean {
    const filter = this.settings!.filter;
    if (!filter) {
      return true;
    }
    if (typeof filter === 'function') {
      return !!(filter as (this: unknown, ...a: unknown[]) => unknown).call(
        target,
        0,
        target
      );
    }
    return target instanceof Element && target.matches(filter);
  }

  /** Sort a subset of items into current DOM order (for {@link resetItemOrder}). */
  private _sortByDomOrder(items: HTMLElement[]): HTMLElement[] {
    return [...items].sort((a, b) => {
      const pos = a.compareDocumentPosition(b);
      if (pos & Node.DOCUMENT_POSITION_FOLLOWING) {
        return -1;
      }
      if (pos & Node.DOCUMENT_POSITION_PRECEDING) {
        return 1;
      }
      return 0;
    });
  }
}
