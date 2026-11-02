import {
  Base,
  type GarnishBaseSettings,
  muteResizeEvents,
} from '@craftcms/garnish';
import {startSide} from '@/common/craft/direction';
import {resolveElement, type ElementArg} from '@/common/utils/dom';
import {jq} from '@/common/utils/jquery';

/** The content width, without padding, like jQuery's `width()`. */
function contentWidth(el: HTMLElement): number {
  const style = getComputedStyle(el);
  return (
    el.clientWidth -
    parseFloat(style.paddingLeft || '0') -
    parseFloat(style.paddingRight || '0')
  );
}

/** The content height, without padding, like jQuery's `height()`. */
function contentHeight(el: HTMLElement): number {
  const style = getComputedStyle(el);
  return (
    el.clientHeight -
    parseFloat(style.paddingTop || '0') -
    parseFloat(style.paddingBottom || '0')
  );
}

/** Sets the content height, accounting for `box-sizing`, like jQuery's `height(value)`. */
function setContentHeight(el: HTMLElement, height: number | 'auto'): void {
  if (height === 'auto') {
    el.style.height = 'auto';
    return;
  }

  const style = getComputedStyle(el);
  const extra =
    style.boxSizing === 'border-box'
      ? parseFloat(style.paddingTop || '0') +
        parseFloat(style.paddingBottom || '0') +
        parseFloat(style.borderTopWidth || '0') +
        parseFloat(style.borderBottomWidth || '0')
      : 0;
  el.style.height = `${height + extra}px`;
}

function show(el: HTMLElement): void {
  if (el.style.display === 'none') {
    el.style.display = '';
  }
  if (getComputedStyle(el).display === 'none') {
    el.style.display = 'block';
  }
}

function numberData(el: HTMLElement, name: string): number | null {
  const value = parseInt(el.dataset[name] ?? '', 10);
  return Number.isNaN(value) ? null : value;
}

function toElements(items: unknown): HTMLElement[] {
  if (!items) {
    return [];
  }
  if (items instanceof HTMLElement) {
    return [items];
  }
  // Arrays, NodeLists and jQuery collections
  return Array.from(items as ArrayLike<HTMLElement>);
}

const grids = new WeakMap<HTMLElement, Grid>();

interface GridSettings extends GarnishBaseSettings {
  itemSelector: string;
  cols: number | null;
  maxCols: number | null;
  minColWidth: number;
  gutter: number;
  fillMode: string;
  colClass: string;
  snapToGrid: number | null;
  onRefreshCols: () => void;
}

interface GridLayout {
  positions: number[];
  colspans: number[];
  colHeights: number[];
  emptySpace: number;
}

/**
 * Grid — a port of `Craft.Grid` onto `@craftcms/garnish` `Base`. A masonry /
 * bin-packing layout manager: it computes a responsive column count, and for
 * variable-width (`data-colspan`) / variable-height items it runs a
 * combinatorial optimizer ({@link GridLayoutGenerator}) to find the layout with
 * the most-used columns, least total height, and least empty space, then
 * absolutely-positions the items. (This is why it can't be a CSS `display:grid`
 * — native masonry isn't shipped cross-browser.)
 *
 * It works on plain elements. `$container` and `$items` still return jQuery
 * collections when jQuery is loaded, for the legacy dashboard, and `addItems()`
 * accepts them too. Booted via `new Craft.Grid(...)` and the `.grid` jQuery
 * plugin, so exposed on `window.Craft`.
 */
export class Grid extends Base<GridSettings> {
  static defaults: GridSettings = {
    itemSelector: '.item',
    cols: null,
    maxCols: null,
    minColWidth: 320,
    gutter: 14,
    fillMode: 'top',
    colClass: 'col',
    snapToGrid: null,
    onRefreshCols: () => {},
  };

  container: HTMLElement | null = null;
  items: HTMLElement[] = [];

  totalCols: number | null = null;
  colGutterDrop = 0;
  colPctWidth = 0;

  layouts: GridLayout[] = [];
  layout: GridLayout | null = null;

  possibleItemColspans: number[][] = [];
  possibleItemPositionsByColspan: Record<number, number[]>[] = [];
  itemHeightsByColspan: Record<number, number>[] = [];

  #refreshingCols = false;
  #refreshColsAfterRefresh = false;
  #forceRefreshColsAfterRefresh = false;

  constructor(container?: ElementArg, settings?: Partial<GridSettings>) {
    super();
    if (new.target === Grid) {
      this.init(container ?? null, settings);
    }
  }

  init(container: ElementArg, settings?: Partial<GridSettings>): void {
    const el = resolveElement(container);
    if (!el) {
      return;
    }
    // SAFETY: Grids are laid out on HTML elements.
    this.container = el as HTMLElement;

    // Is this already a grid?
    const existing = grids.get(this.container);
    if (existing) {
      console.warn('Double-instantiating a grid on an element');
      existing.destroy();
    }

    grids.set(this.container, this);
    jq()?.(this.container).data('grid', this);

    this.setSettings(settings, Grid.defaults);

    this.items = [];
    this.addItems(
      Array.from(this.container.children).filter((child) =>
        child.matches(this.settings!.itemSelector)
      )
    );

    this.addListener(this.container, 'resize', () => {
      this.refreshCols();
    });
  }

  /** The container as a jQuery collection, for legacy callers. */
  get $container(): any {
    return this.container ? jq()?.(this.container) : null;
  }

  /** The items as a jQuery collection, for legacy callers. */
  get $items(): any {
    return jq()?.(this.items) ?? null;
  }

  addItems(items: unknown): void {
    this.setItems([...this.items, ...toElements(items)]);
    this.refreshCols(true);
  }

  removeItems(items: unknown): void {
    const removed = new Set(toElements(items));
    this.setItems(this.items.filter((item) => !removed.has(item)));
    this.refreshCols(true);
  }

  resetItemOrder(): void {
    this.setItems(this.items);
    this.refreshCols(true);
  }

  /** Replaces the items, deduplicated and in document order. */
  setItems(items: unknown = this.items): void {
    this.items = Array.from(new Set(toElements(items))).sort((a, b) =>
      a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1
    );
  }

  refreshCols(force = false): void {
    if (this.#refreshingCols) {
      this.#refreshColsAfterRefresh = true;
      if (force) {
        this.#forceRefreshColsAfterRefresh = true;
      }
      return;
    }

    this.#refreshingCols = true;

    if (!this.items.length) {
      this.completeRefreshCols();
      return;
    }

    const settings = this.settings!;
    const container = this.container!;

    // Check to see if the grid is actually visible
    const oldHeight = container.style.height;
    container.style.height = '1';
    const scrollHeight = container.scrollHeight;
    container.style.height = oldHeight;

    if (scrollHeight === 0) {
      this.completeRefreshCols();
      return;
    }

    let totalCols: number;
    if (settings.cols) {
      totalCols = settings.cols;
    } else {
      totalCols = Math.floor(contentWidth(container) / settings.minColWidth);

      // If we're adding a new column, require an extra 20 pixels in case a scrollbar shows up
      if (this.totalCols !== null && totalCols > this.totalCols) {
        totalCols = Math.floor(
          (contentWidth(container) - 20) / settings.minColWidth
        );
      }

      if (settings.maxCols && totalCols > settings.maxCols) {
        totalCols = settings.maxCols;
      }
    }

    if (totalCols === 0) {
      totalCols = 1;
    }

    // Same number of columns as before?
    if (
      force !== true &&
      this.totalCols === totalCols &&
      !settings.snapToGrid
    ) {
      this.completeRefreshCols();
      return;
    }

    this.totalCols = totalCols;
    this.colGutterDrop = (settings.gutter * (totalCols - 1)) / totalCols;

    // Temporarily stop listening to container resizes
    muteResizeEvents(() => {
      if (settings.fillMode === 'grid') {
        this.#layoutGridFill(totalCols);
      } else {
        this.#layoutTopFill(totalCols);
      }

      this.completeRefreshCols();
    });

    this.onRefreshCols();
  }

  /** `fillMode: 'grid'` — equalize each row's items to the tallest item. */
  #layoutGridFill(totalCols: number): void {
    const settings = this.settings!;
    let itemIndex = 0;

    while (itemIndex < this.items.length) {
      // Append the next X items and figure out which one is the tallest
      let tallestItemHeight = -1;

      for (
        let i = itemIndex;
        i < itemIndex + totalCols && i < this.items.length;
        i++
      ) {
        const item = this.items[i]!;
        setContentHeight(item, 'auto');
        const itemHeight = contentHeight(item);
        if (itemHeight > tallestItemHeight) {
          tallestItemHeight = itemHeight;
        }
      }

      if (settings.snapToGrid) {
        const remainder = tallestItemHeight % settings.snapToGrid;
        if (remainder) {
          tallestItemHeight += settings.snapToGrid - remainder;
        }
      }

      // Now set their heights to the tallest one
      for (
        let i = itemIndex;
        i < itemIndex + totalCols && i < this.items.length;
        i++
      ) {
        setContentHeight(this.items[i]!, tallestItemHeight);
      }

      itemIndex += totalCols;
    }
  }

  /** `fillMode: 'top'` (default) — the masonry optimizer + absolute positioning. */
  #layoutTopFill(totalCols: number): void {
    const settings = this.settings!;

    // If there's only one column, sneak out early
    if (totalCols === 1) {
      this.container!.style.height = 'auto';
      for (const item of this.items) {
        show(item);
        Object.assign(item.style, {
          position: 'relative',
          width: 'auto',
          top: '0',
        });
        item.style.setProperty(startSide(), '0');
      }
      return;
    }

    for (const item of this.items) {
      item.style.position = 'absolute';
    }
    this.colPctWidth = 100 / totalCols;

    this.layouts = [];
    this.possibleItemColspans = [];
    this.possibleItemPositionsByColspan = [];
    this.itemHeightsByColspan = [];

    // Figure out all of the possible colspans for each item, plus all the
    // possible positions for each item at each of its colspans.
    for (let item = 0; item < this.items.length; item++) {
      this.possibleItemColspans[item] = [];
      this.possibleItemPositionsByColspan[item] = {};
      this.itemHeightsByColspan[item] = {};

      const $item = this.items[item]!;
      show($item);
      const positionRight = $item.dataset.position === 'right';
      const positionLeft = $item.dataset.position === 'left';

      let minColspan =
        numberData($item, 'colspan') ?? numberData($item, 'minColspan') ?? 1;
      let maxColspan =
        numberData($item, 'colspan') ??
        numberData($item, 'maxColspan') ??
        totalCols;

      if (minColspan > totalCols) {
        minColspan = totalCols;
      }
      if (maxColspan > totalCols) {
        maxColspan = totalCols;
      }

      for (let colspan = minColspan; colspan <= maxColspan; colspan++) {
        // Get the height for this colspan
        $item.style.width = this.getItemWidthCss(colspan);
        this.itemHeightsByColspan[item]![colspan] = $item.offsetHeight;

        this.possibleItemColspans[item]!.push(colspan);
        this.possibleItemPositionsByColspan[item]![colspan] = [];

        let minPosition: number;
        let maxPosition: number;
        if (positionLeft) {
          minPosition = 0;
          maxPosition = 0;
        } else if (positionRight) {
          minPosition = totalCols - colspan;
          maxPosition = minPosition;
        } else {
          minPosition = 0;
          maxPosition = totalCols - colspan;
        }

        for (let position = minPosition; position <= maxPosition; position++) {
          this.possibleItemPositionsByColspan[item]![colspan]!.push(position);
        }
      }
    }

    // Find all the possible layouts
    const colHeights: number[] = [];
    for (let i = 0; i < totalCols; i++) {
      colHeights.push(0);
    }

    this.createLayouts(0, [], [], colHeights, 0);

    // Now find the layout that looks the best.

    // First find the layouts with the highest number of used columns
    const layoutTotalCols: number[] = [];
    for (let i = 0; i < this.layouts.length; i++) {
      let count = 0;
      for (let j = 0; j < totalCols; j++) {
        if (this.layouts[i]!.colHeights[j]) {
          count++;
        }
      }
      layoutTotalCols[i] = count;
    }

    const highestTotalCols = Math.max.apply(null, layoutTotalCols);

    // Filter out the ones that aren't using as many columns as they could be
    for (let i = this.layouts.length - 1; i >= 0; i--) {
      if (layoutTotalCols[i] !== highestTotalCols) {
        this.layouts.splice(i, 1);
      }
    }

    // Find the layout(s) with the least overall height
    const layoutHeights: number[] = [];
    for (let i = 0; i < this.layouts.length; i++) {
      layoutHeights.push(Math.max.apply(null, this.layouts[i]!.colHeights));
    }

    const shortestHeight = Math.min.apply(null, layoutHeights);
    const shortestLayouts: GridLayout[] = [];
    const emptySpaces: number[] = [];

    for (let i = 0; i < layoutHeights.length; i++) {
      if (layoutHeights[i] === shortestHeight) {
        shortestLayouts.push(this.layouts[i]!);

        // Now get its total empty space, including any trailing empty space
        let emptySpace = this.layouts[i]!.emptySpace;
        for (let j = 0; j < totalCols; j++) {
          emptySpace += shortestHeight - this.layouts[i]!.colHeights[j]!;
        }
        emptySpaces.push(emptySpace);
      }
    }

    // And the layout with the least empty space is...
    this.layout =
      shortestLayouts[emptySpaces.indexOf(Math.min.apply(null, emptySpaces))]!;

    // Set the item widths and left positions
    for (let i = 0; i < this.items.length; i++) {
      const item = this.items[i]!;
      item.style.width = this.getItemWidthCss(this.layout.colspans[i]!);
      item.style.setProperty(
        startSide(),
        this.getItemLeftPosCss(this.layout.positions[i]!)
      );
    }

    // If every item is at position 0, then let them lay out au naturel
    if (this.isSimpleLayout()) {
      this.container!.style.height = 'auto';
      for (const item of this.items) {
        Object.assign(item.style, {
          position: 'relative',
          top: '0',
          marginBottom: `${settings.gutter}px`,
        });
      }
    } else {
      for (const item of this.items) {
        item.style.position = 'absolute';
      }

      // Now position the items
      this.positionItems();

      // Update the positions as the items' heights change
      this.addListener(this.items, 'resize', 'onItemResize');
    }
  }

  completeRefreshCols(): void {
    this.#refreshingCols = false;

    if (this.#refreshColsAfterRefresh) {
      const force = this.#forceRefreshColsAfterRefresh;
      this.#refreshColsAfterRefresh = false;
      this.#forceRefreshColsAfterRefresh = false;

      requestAnimationFrame(() => {
        this.refreshCols(force);
      });
    }
  }

  getItemWidth(colspan: number): number {
    return this.colPctWidth * colspan;
  }

  getItemWidthCss(colspan: number): string {
    return `calc(${this.getItemWidth(colspan)}% - ${this.colGutterDrop}px)`;
  }

  getItemWidthInPx(colspan: number): number {
    return (
      (this.getItemWidth(colspan) / 100) * contentWidth(this.container!) -
      this.colGutterDrop
    );
  }

  getItemLeftPosCss(position: number): string {
    return `calc((${this.getItemWidth(1)}% + ${
      this.settings!.gutter - this.colGutterDrop
    }px) * ${position})`;
  }

  getItemLeftPosInPx(position: number): number {
    return (
      ((this.getItemWidth(1) / 100) * contentWidth(this.container!) +
        (this.settings!.gutter - this.colGutterDrop)) *
      position
    );
  }

  createLayouts(
    item: number,
    prevPositions: number[],
    prevColspans: number[],
    prevColHeights: number[],
    prevEmptySpace: number
  ): void {
    new GridLayoutGenerator(this).createLayouts(
      item,
      prevPositions,
      prevColspans,
      prevColHeights,
      prevEmptySpace
    );
  }

  isSimpleLayout(): boolean {
    if (!this.layout) {
      return true;
    }
    for (let i = 0; i < this.layout.positions.length; i++) {
      if (this.layout.positions[i] !== 0) {
        return false;
      }
    }
    return true;
  }

  positionItems(): void {
    const totalCols = this.totalCols!;
    const layout = this.layout!;
    const settings = this.settings!;

    const colHeights: number[] = [];
    for (let i = 0; i < totalCols; i++) {
      colHeights.push(0);
    }

    for (let i = 0; i < this.items.length; i++) {
      const endingCol = layout.positions[i]! + layout.colspans[i]! - 1;
      const affectedColHeights: number[] = [];

      for (let col = layout.positions[i]!; col <= endingCol; col++) {
        affectedColHeights.push(colHeights[col]!);
      }

      let top = Math.max.apply(null, affectedColHeights);
      if (top > 0) {
        top += settings.gutter;
      }

      this.items[i]!.style.top = `${top}px`;

      // Now add the new heights to those columns
      for (let col = layout.positions[i]!; col <= endingCol; col++) {
        colHeights[col] =
          top + this.itemHeightsByColspan[i]![layout.colspans[i]!]!;
      }
    }

    // Set the container height
    setContentHeight(this.container!, Math.max.apply(null, colHeights));
  }

  onItemResize(ev: any): void {
    // Prevent this from bubbling up to the container, which has its own resize listener
    ev.stopPropagation();

    const item = this.#itemIndex(ev.currentTarget);

    if (item !== -1) {
      // Update the height and reposition the items
      const newHeight = this.items[item]!.offsetHeight;

      if (
        newHeight !==
        this.itemHeightsByColspan[item]![this.layout!.colspans[item]!]
      ) {
        this.itemHeightsByColspan[item]![this.layout!.colspans[item]!] =
          newHeight;
        this.positionItems();
      }
    }
  }

  #itemIndex(el: HTMLElement): number {
    return this.items.indexOf(el);
  }

  onRefreshCols(): void {
    this.trigger('refreshCols');
    this.settings!.onRefreshCols();
  }

  override destroy(): void {
    if (this.container) {
      grids.delete(this.container);
      jq()?.(this.container).removeData('grid');
    }
    super.destroy();
  }
}

/**
 * The recursive layout optimizer for `fillMode: 'top'`. Enumerates every
 * colspan/position combination, greedily choosing the shortest position for
 * each colspan, and collects the resulting full-grid layouts on the owning
 * {@link Grid}.
 */
class GridLayoutGenerator {
  #grid: Grid;

  constructor(grid: Grid) {
    this.#grid = grid;
  }

  createLayouts(
    item: number,
    prevPositions: number[],
    prevColspans: number[],
    prevColHeights: number[],
    prevEmptySpace: number
  ): void {
    const grid = this.#grid;

    // Loop through all possible colspans
    for (let c = 0; c < grid.possibleItemColspans[item]!.length; c++) {
      const colspan = grid.possibleItemColspans[item]![c]!;

      // Loop through all the possible positions for this colspan, and find the
      // one that is closest to the top.
      const tallestColHeightsByPosition: number[] = [];
      const positions = grid.possibleItemPositionsByColspan[item]![colspan]!;

      for (let p = 0; p < positions.length; p++) {
        const position = positions[p]!;
        const colHeightsForPosition: number[] = [];
        const endingCol = position + colspan - 1;

        for (let col = position; col <= endingCol; col++) {
          colHeightsForPosition.push(prevColHeights[col]!);
        }

        tallestColHeightsByPosition[p] = Math.max.apply(
          null,
          colHeightsForPosition
        );
      }

      // And the shortest position for this colspan is...
      const p = tallestColHeightsByPosition.indexOf(
        Math.min.apply(null, tallestColHeightsByPosition)
      );
      const position = positions[p]!;

      // Now log the colspan/position placement
      const nextPositions = prevPositions.slice(0);
      const nextColspans = prevColspans.slice(0);
      const nextColHeights = prevColHeights.slice(0);
      let nextEmptySpace = prevEmptySpace;

      nextPositions.push(position);
      nextColspans.push(colspan);

      // Add the new heights to those columns
      const tallestColHeight = tallestColHeightsByPosition[p]!;
      const endingCol = position + colspan - 1;

      for (let col = position; col <= endingCol; col++) {
        nextEmptySpace += tallestColHeight - nextColHeights[col]!;
        nextColHeights[col] =
          tallestColHeight + grid.itemHeightsByColspan[item]![colspan]!;
      }

      // If this is the last item, create the layout
      if (item === grid.items.length - 1) {
        grid.layouts.push({
          positions: nextPositions,
          colspans: nextColspans,
          colHeights: nextColHeights,
          emptySpace: nextEmptySpace,
        });
      } else {
        // Dive deeper
        grid.createLayouts(
          item + 1,
          nextPositions,
          nextColspans,
          nextColHeights,
          nextEmptySpace
        );
      }
    }
  }
}

// Legacy static exposure (`Craft.Grid.LayoutGenerator`).
Object.assign(Grid, {LayoutGenerator: GridLayoutGenerator});
