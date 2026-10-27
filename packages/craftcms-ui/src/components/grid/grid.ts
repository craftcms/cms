import {css, html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {FOCUSABLE_SELECTOR} from '@src/utilities/focus-trap';
import '../grid-row/grid-row.js';
import '../grid-cell/grid-cell.js';

/** Popups a cell's control can open, whose own controls aren't the cell's. */
const NESTED_POPUP = '[slot="content"], [popover], dialog, craft-dialog';

/** Controls that need the arrow keys, Home and End for themselves. */
const TEXT_ENTRY =
  'input:not([type="button"], [type="checkbox"], [type="radio"], [type="reset"], [type="submit"]), select, textarea, [contenteditable]:not([contenteditable="false"])';

/** How many rows Page Down and Page Up move. */
const PAGE_ROWS = 5;

/** The attributes that change which elements can take focus. */
const WATCHED_ATTRIBUTES = [
  'tabindex',
  'disabled',
  'hidden',
  'inert',
  'aria-hidden',
];

interface Position {
  row: number;
  column: number;
}

/**
 * @summary Rows of links, buttons and menus that the arrow keys move
 * between, as one Tab stop.
 *
 * Lay it out with `craft-grid-row`s holding `craft-grid-cell`s. Each cell
 * holds at most one interactive element, which takes focus; a cell with none
 * takes focus itself. Only one of them is in the Tab order at a time: the
 * first, until another takes focus.
 *
 * Follows the WAI-ARIA APG Layout Grid examples: Right and Left move along a
 * row, Down and Up between rows, Home and End to the ends of the row,
 * Control+Home and Control+End to the first and last cells, and Page Down and
 * Page Up five rows at a time.
 *
 * Name it with `label`, or with `aria-labelledby` pointing at a visible
 * heading.
 *
 * @slot - The `craft-grid-row`s making up the grid.
 */
export default class CraftGrid extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }
  `;

  /** The grid's accessible name, when no visible heading names it through `aria-labelledby`. */
  @property({reflect: true}) label: string | null = null;

  /** The element holding the Tab stop. */
  #active: HTMLElement | null = null;

  /** Where the Tab stop was last, for when its element is removed. */
  #position: Position = {row: 0, column: 0};

  /** Each managed element's own `tabindex`, given back when it stops being managed. */
  #originalTabindex = new Map<HTMLElement, string | null>();

  #observer = new MutationObserver(() => this.#sync());

  override connectedCallback() {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'grid');
    }

    this.addEventListener('keydown', this.#onKeydown);
    this.addEventListener('focusin', this.#onFocusin);
    this.#observer.observe(this, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: WATCHED_ATTRIBUTES,
    });
    this.#sync();
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.removeEventListener('keydown', this.#onKeydown);
    this.removeEventListener('focusin', this.#onFocusin);
    this.#observer.disconnect();

    for (const element of [...this.#originalTabindex.keys()]) {
      this.#release(element);
    }

    this.#active = null;
  }

  protected override updated(changed: PropertyValues<this>) {
    if (changed.has('label')) {
      if (this.label) {
        this.setAttribute('aria-label', this.label);
      } else {
        this.removeAttribute('aria-label');
      }
    }
  }

  protected override firstUpdated() {
    this.#sync();
  }

  override render() {
    return html`<slot @slotchange=${() => this.#sync()}></slot>`;
  }

  /**
   * Puts the Tab stop back on the first cell, for when the grid's rows are
   * replaced by different ones — new search results, say — rather than
   * changed in place.
   */
  resetTabStop(): void {
    this.#active = null;
    this.#position = {row: 0, column: 0};
    this.#sync();
  }

  /** The element in each cell that takes focus, row by row, leaving out empty rows. */
  #targets(): HTMLElement[][] {
    return this.#rows()
      .map((row) => this.#cells(row).map((cell) => this.#targetIn(cell)))
      .filter((row) => row.length > 0);
  }

  #rows(): HTMLElement[] {
    return [...this.querySelectorAll<HTMLElement>('craft-grid-row')].filter(
      (row) => row.closest('craft-grid') === this && this.#isShown(row)
    );
  }

  #cells(row: HTMLElement): HTMLElement[] {
    return [...row.querySelectorAll<HTMLElement>('craft-grid-cell')].filter(
      (cell) => cell.closest('craft-grid-row') === row && this.#isShown(cell)
    );
  }

  /** The cell's interactive element, or the cell itself when it has none. */
  #targetIn(cell: HTMLElement): HTMLElement {
    const control = [
      ...cell.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR),
    ].find((element) => this.#isControl(element, cell));

    return control ?? cell;
  }

  #isControl(element: HTMLElement, cell: HTMLElement): boolean {
    if (
      element.hasAttribute('disabled') &&
      !element.hasAttribute('focusable-when-disabled')
    ) {
      return false;
    }

    if (!this.#isShown(element) || element.closest('[inert]')) {
      return false;
    }

    if (element.closest('craft-grid-cell') !== cell) {
      return false;
    }

    const popup = element.parentElement?.closest(NESTED_POPUP);

    return !(popup && cell.contains(popup));
  }

  #isShown(element: HTMLElement): boolean {
    return !element.closest('[hidden], [aria-hidden="true"]');
  }

  /** Puts the Tab stop on one element and takes every other target out of the Tab order. */
  #sync(): void {
    const targets = this.#targets();
    const all = targets.flat();

    if (!this.#active || !all.includes(this.#active)) {
      const row = targets[Math.min(this.#position.row, targets.length - 1)];
      this.#active =
        row?.[Math.min(this.#position.column, row.length - 1)] ??
        all[0] ??
        null;
    }

    for (const element of [...this.#originalTabindex.keys()]) {
      if (!all.includes(element)) {
        this.#release(element);
      }
    }

    for (const element of all) {
      this.#manage(element, element === this.#active ? '0' : '-1');
    }

    // This just matched the Tab order to the grid as it is, so the changes it
    // made itself don't need syncing again.
    this.#observer.takeRecords();
  }

  #manage(element: HTMLElement, tabindex: string): void {
    if (!this.#originalTabindex.has(element)) {
      this.#originalTabindex.set(element, element.getAttribute('tabindex'));
    }

    if (element.getAttribute('tabindex') !== tabindex) {
      element.setAttribute('tabindex', tabindex);
    }
  }

  #release(element: HTMLElement): void {
    const original = this.#originalTabindex.get(element);
    this.#originalTabindex.delete(element);

    if (original === null || original === undefined) {
      element.removeAttribute('tabindex');
    } else {
      element.setAttribute('tabindex', original);
    }
  }

  #positionOf(
    targets: HTMLElement[][],
    element: EventTarget | null
  ): Position | null {
    for (const [row, cells] of targets.entries()) {
      const column = cells.indexOf(element as HTMLElement);

      if (column !== -1) {
        return {row, column};
      }
    }

    return null;
  }

  #onFocusin = (event: FocusEvent): void => {
    const targets = this.#targets();
    const position = this.#positionOf(targets, event.target);

    if (!position) {
      return;
    }

    const element = targets[position.row]![position.column]!;
    this.#position = position;

    // Arrow keys move the Tab stop before moving focus, so there's nothing to sync.
    if (element === this.#active) {
      return;
    }

    this.#active = element;
    this.#sync();
  };

  #onKeydown = (event: KeyboardEvent): void => {
    if (event.defaultPrevented || event.altKey || event.shiftKey) {
      return;
    }

    const target = event.target as HTMLElement | null;

    if (!target || target.matches(TEXT_ENTRY)) {
      return;
    }

    const targets = this.#targets();
    const position = this.#positionOf(targets, target);

    if (!position) {
      return;
    }

    const next = this.#nextPosition(event, targets, position);

    if (!next) {
      return;
    }

    event.preventDefault();
    const element = targets[next.row]![next.column]!;
    this.#active = element;
    this.#position = next;
    this.#sync();
    element.focus();
  };

  #nextPosition(
    event: KeyboardEvent,
    targets: HTMLElement[][],
    {row, column}: Position
  ): Position | null {
    const lastRow = targets.length - 1;
    const lastColumn = (index: number) => targets[index]!.length - 1;
    const inRow = (index: number) => ({
      row: index,
      column: Math.min(column, lastColumn(index)),
    });
    // Computed, so `dir="auto"` and CSS `direction` count too
    const rtl = getComputedStyle(this).direction === 'rtl';
    /** A move along the row, toward its end for a positive `step`, stopping at both ends. */
    const along = (step: number): Position => ({
      row,
      column: Math.max(0, Math.min(column + step, lastColumn(row))),
    });
    const modified = event.ctrlKey || event.metaKey;

    if (modified) {
      switch (event.key) {
        case 'Home':
          return {row: 0, column: 0};
        case 'End':
          return {row: lastRow, column: lastColumn(lastRow)};
        default:
          return null;
      }
    }

    switch (event.key) {
      case 'ArrowRight':
        return along(rtl ? -1 : 1);
      case 'ArrowLeft':
        return along(rtl ? 1 : -1);
      case 'ArrowDown':
        return inRow(Math.min(row + 1, lastRow));
      case 'ArrowUp':
        return inRow(Math.max(row - 1, 0));
      case 'PageDown':
        return inRow(Math.min(row + PAGE_ROWS, lastRow));
      case 'PageUp':
        return inRow(Math.max(row - PAGE_ROWS, 0));
      case 'Home':
        return {row, column: 0};
      case 'End':
        return {row, column: lastColumn(row)};
      default:
        return null;
    }
  }
}

if (!customElements.get('craft-grid')) {
  customElements.define('craft-grid', CraftGrid);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-grid': CraftGrid;
  }
}
