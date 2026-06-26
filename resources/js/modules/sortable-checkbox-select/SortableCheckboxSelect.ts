import {Base, DragSort, Y_AXIS} from '@craftcms/garnish';
import {sortableCheckboxSelectData} from './support';
import type {ReorderDirection} from '@craftcms/cp';

// `Craft` and jQuery (`$`) are still globals on the page. SortableCheckboxSelect
// extends the modern `@craftcms/garnish` `Base` but orchestrates still-jQuery
// Craft widgets and DOM, and exposes the jQuery `.data()` back-reference the
// still-legacy `BaseElementIndex` reads — so jQuery (`$`) and the public
// `$container`/`$item` survive at those seams. The modernization is the class
// system, the `.data()` → WeakMap mirror (see `support.ts`), and ESM/bundle
// wiring.
declare const Craft: any;
declare const $: any;

/**
 * Sortable checkbox select — a port of the legacy jQuery
 * `Craft.SortableCheckboxSelect` onto the modern `@craftcms/garnish` `Base`.
 *
 * Setup lives in {@link init}, invoked from the constructor only for the leaf
 * class (`new.target` guard) — the same construction contract as the other ports,
 * so the class slots cleanly under the legacy `new Craft.…` callers and could be
 * compat-wrapped later if anything ever subclassed it (nothing does today, so
 * it's assigned to the global as the plain ES class).
 *
 * Each item has an always-present `<craft-reorder-button>` (prepended before the
 * checkbox) that is BOTH the drag handle and the Move up/down action menu. A
 * button is enabled — draggable + menu active — only while its item is checked
 * AND at least two items are checked overall (so there's a selected set to
 * reorder); otherwise it's disabled. See {@link updateReorderButtons}. Emits a
 * `sortChange` pub/sub event when items are reordered (drag or menu).
 */
export class SortableCheckboxSelect extends Base {
  $container: any = null;
  dragSort: any = null;

  constructor(container?: any) {
    super();
    if (new.target === SortableCheckboxSelect) {
      this.init(container);
    }
  }

  init(container: any): void {
    this.$container = $(container);

    // Object back-reference. Kept as jQuery `.data()` for the still-legacy
    // `BaseElementIndex` reader, and mirrored in a WeakMap for modern consumers
    // (the Card View Designer).
    if (this.$container.data('sortableCheckboxSelect')) {
      this.$container.data('sortableCheckboxSelect', null);
    }
    this.$container.data('sortableCheckboxSelect', this);

    const containerEl: Element | undefined = this.$container[0];
    if (containerEl) {
      sortableCheckboxSelectData.set(containerEl, this);
    }

    const $sortItems = this.$container.children(
      '.checkbox-select-item:not(.all)'
    );

    this.initDrag();

    if ($sortItems.length) {
      $sortItems.each((_key: number, item: HTMLElement) => {
        this.initItem(item);
      });
    }

    // Now that every item has its reorder button, set the initial
    // enabled/disabled + sorter-membership state in one pass.
    this.updateReorderButtons();
  }

  initDrag(): void {
    // The drag handle is each item's <craft-reorder-button>. DragSort resolves
    // an item's handle at addItems time and does NOT honor the button's
    // `disabled` attribute, so dragging is gated purely by sorter membership:
    // updateReorderButtons() adds an item only while its button is enabled and
    // removes it otherwise. The sorter is created once and reused; repeat calls
    // (e.g. the Card View Designer when a checkbox is added) are no-ops. On
    // touch (no mouse pointer events) there's no sorter — the action menu still
    // handles reordering.
    if (this.dragSort || !Craft.hasMousePointerEvents()) {
      return;
    }

    this.dragSort = new DragSort({
      axis: Y_AXIS,
      handle: 'craft-reorder-button',
    });
    this.dragSort.on('sortChange', () => {
      // A drag reorder changes only the order; refresh positions (the checked
      // set — and thus disabled/membership — is unchanged) alongside the
      // existing sortChange pub/sub (consumed by the CVD + legacy
      // BaseElementIndex), not replacing it.
      this.updateReorderPositions();
      this.trigger('sortChange');
    });
  }

  initItem(item: any): Item {
    return new Item(this, item);
  }

  /**
   * Tear down the sorter and release the back-references so the controller can be
   * re-booted (e.g. when the field layout designer's host innerHTML is swapped).
   * The item-level listeners live on the (now detached) checkbox DOM and are
   * cleaned up by GC; this disposes the DragSort (which would otherwise keep its
   * pointer bindings) and clears the WeakMap + jQuery `.data` entries.
   */
  override destroy(): void {
    this.dragSort?.destroy?.();
    this.dragSort = null;

    const containerEl: Element | undefined = this.$container?.[0];
    if (containerEl) {
      sortableCheckboxSelectData.delete(containerEl);
    }
    this.$container?.removeData?.('sortableCheckboxSelect');

    super.destroy();
  }

  /**
   * Recompute every item's reorder state. A button is enabled only when its item
   * is checked and 2+ items are checked overall; otherwise it's disabled.
   * Enabled ⇒ in the sorter (draggable); disabled ⇒ removed from the sorter
   * (DragSort ignores the `disabled` attribute, so membership is what actually
   * gates dragging). Called whenever the checked set changes — a single check
   * can flip a sibling near the 1↔2-checked boundary.
   */
  updateReorderButtons(): void {
    const containerEl: Element | undefined = this.$container?.[0];
    if (!containerEl) {
      return;
    }

    const items = Array.from(
      containerEl.querySelectorAll<HTMLElement>(
        ':scope > .checkbox-select-item:not(.all)'
      )
    );

    const isChecked = (item: HTMLElement): boolean =>
      !!item.querySelector<HTMLInputElement>(':scope > input[type=checkbox]')
        ?.checked;

    const enableReorder = items.filter(isChecked).length >= 2;

    for (const item of items) {
      const btn = item.querySelector(':scope > craft-reorder-button');
      if (!btn) {
        continue;
      }

      if (isChecked(item) && enableReorder) {
        btn.removeAttribute('disabled');
        this.dragSort?.addItems(item);
      } else {
        btn.setAttribute('disabled', '');
        this.dragSort?.removeItems(item);
      }
    }

    this.updateReorderPositions();
  }

  /**
   * Recompute each button's `position` (first / last / middle within the CHECKED
   * subset, mirroring getPrev/NextCheckedItem): the first checked item gets
   * 'first' (disables Move up), the last checked gets 'last' (disables Move
   * down), the rest 'middle'. Unchecked items fall through to 'middle' (their
   * button is disabled anyway).
   *
   * A reorder changes only the order — not which items are checked — so the
   * reorder paths (drag sort-change, moveUp/moveDown) call this rather than the
   * full {@link updateReorderButtons}, leaving the disabled state and DragSort
   * sorter membership untouched (and avoiding redundant addItems/removeItems).
   */
  updateReorderPositions(): void {
    const containerEl: Element | undefined = this.$container?.[0];
    if (!containerEl) {
      return;
    }

    const items = Array.from(
      containerEl.querySelectorAll<HTMLElement>(
        ':scope > .checkbox-select-item:not(.all)'
      )
    );

    const checkedFlags = items.map(
      (item) =>
        !!item.querySelector<HTMLInputElement>(':scope > input[type=checkbox]')
          ?.checked
    );
    const firstCheckedIndex = checkedFlags.indexOf(true);
    const lastCheckedIndex = checkedFlags.lastIndexOf(true);

    items.forEach((item, index) => {
      const btn = item.querySelector(':scope > craft-reorder-button');
      if (!btn) {
        return;
      }

      btn.setAttribute(
        'position',
        index === firstCheckedIndex
          ? 'first'
          : index === lastCheckedIndex
            ? 'last'
            : 'middle'
      );
    });
  }
}

/**
 * A single checkbox row within a {@link SortableCheckboxSelect}. Owns the
 * always-present `<craft-reorder-button>` (prepended before the checkbox) plus
 * the Move up/down handlers; the enabled/disabled + sorter-membership state is
 * driven by the parent's {@link SortableCheckboxSelect.updateReorderButtons}.
 */
export class Item extends Base {
  select: SortableCheckboxSelect;
  $item: any = null;
  $checkbox: any = null;
  reorderBtn: HTMLElement | null = null;

  constructor(select?: SortableCheckboxSelect, item?: any) {
    super();
    // Assigned here (not just in init) so TS sees it as definitely assigned;
    // init re-assigns it for the leaf-construction path.
    this.select = select!;
    if (new.target === Item) {
      this.init(select!, item);
    }
  }

  init(select: SortableCheckboxSelect, item: any): void {
    this.select = select;
    this.$item = $(item);
    this.$checkbox = this.$item.children('input[type=checkbox]');

    // Always-present drag handle + Move up/down menu, before the checkbox.
    this.reorderBtn = document.createElement('craft-reorder-button');
    this.reorderBtn.addEventListener('reorder', (event: Event) => {
      const {direction} = (event as CustomEvent<{direction: ReorderDirection}>)
        .detail;
      if (direction === 'down') {
        this.moveDown();
      } else {
        this.moveUp();
      }
    });
    this.$item.prepend(this.reorderBtn);

    this.addListener(this.$checkbox, 'change', () => {
      this.handleCheckboxChange();
    });

    // Reflect the initial checked/unchecked state for the legacy event contract;
    // the parent sets the disabled/sorter state once all items exist.
    this.$item.trigger(
      this.$checkbox.prop('checked') ? 'checked' : 'unchecked'
    );
  }

  handleCheckboxChange(): void {
    this.$item.trigger(
      this.$checkbox.prop('checked') ? 'checked' : 'unchecked'
    );
    this.select.updateReorderButtons();
  }

  getPrevCheckedItem(): any {
    const $item = this.$item.prevAll(
      '.checkbox-select-item:not(.all):has(input[type=checkbox]:checked):first'
    );
    return $item.length ? $item : null;
  }

  getNextCheckedItem(): any {
    const $item = this.$item.nextAll(
      '.checkbox-select-item:not(.all):has(input[type=checkbox]:checked):first'
    );
    return $item.length ? $item : null;
  }

  moveUp(): void {
    const $prev = this.getPrevCheckedItem();
    if ($prev) {
      this.$item.insertBefore($prev);
      this.$item.trigger('movedUp');
      this.select.updateReorderPositions();
      this.select.trigger('sortChange');
    }
  }

  moveDown(): void {
    const $next = this.getNextCheckedItem();
    if ($next) {
      this.$item.insertAfter($next);
      this.$item.trigger('movedDown');
      this.select.updateReorderPositions();
      this.select.trigger('sortChange');
    }
  }
}
