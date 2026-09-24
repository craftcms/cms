import {Base, DragSort, Y_AXIS} from '@craftcms/garnish';
import {sortableCheckboxSelectData} from './support';
import type {ReorderDirection} from '@craftcms/ui';

// `Craft` and `$` (jQuery) remain page globals. This class extends the modern
// `@craftcms/garnish` `Base` but orchestrates jQuery Craft widgets/DOM and exposes
// the jQuery `.data()` back-reference the still-legacy `BaseElementIndex` reads, so
// jQuery and the public `$container`/`$item` survive at those seams.
declare const Craft: any;
declare const $: any;

/**
 * Sortable checkbox select — a port of `Craft.SortableCheckboxSelect` onto
 * `@craftcms/garnish` `Base`. Setup lives in {@link init}, invoked from the
 * constructor only for the leaf class (`new.target` guard) — the same contract as
 * the other ports.
 *
 * Each item has an always-present `<craft-reorder-button>` (before the checkbox)
 * that is both the drag handle and the Move up/down menu. It's enabled only while
 * its item is checked AND 2+ items are checked overall (so there's a set to
 * reorder); see {@link updateReorderButtons}. Emits a `sortChange` event on
 * reorder (drag or menu).
 */
export class SortableCheckboxSelect extends Base {
  $container: any = null;
  dragSort: any = null;
  itemObserver: MutationObserver | null = null;

  constructor(container?: any) {
    super();
    if (new.target === SortableCheckboxSelect) {
      this.init(container);
    }
  }

  init(container: any): void {
    this.$container = $(container);

    // Object back-reference: jQuery `.data()` for the legacy `BaseElementIndex`
    // reader, mirrored in a WeakMap for modern consumers (the Card View Designer).
    if (this.$container.data('sortableCheckboxSelect')) {
      this.$container.data('sortableCheckboxSelect', null);
    }
    this.$container.data('sortableCheckboxSelect', this);

    const containerEl: Element | undefined = this.$container[0];
    if (containerEl) {
      sortableCheckboxSelectData.set(containerEl, this);
    }

    const $sortItems = this.$container.children(
      '.cp-checkbox-select__item:not(.all)'
    );

    this.initDrag();

    if ($sortItems.length) {
      $sortItems.each((_key: number, item: HTMLElement) => {
        this.initItem(item);
      });
    }

    // Every item now has its button; set initial enabled/membership state.
    this.updateReorderButtons();

    // Keep the sorter in sync with items added/removed after boot.
    this.observeItems();
  }

  initDrag(): void {
    // DragSort doesn't honor the button's `disabled` attribute, so dragging is
    // gated by sorter membership instead: updateReorderButtons() adds/removes items
    // as their buttons enable/disable. Created once and reused (repeat calls are
    // no-ops). On touch there's no sorter — the action menu handles reordering.
    if (this.dragSort || !Craft.hasMousePointerEvents()) {
      return;
    }

    this.dragSort = new DragSort({
      axis: Y_AXIS,
      handle: 'craft-reorder-button',
    });
    this.dragSort.on('sortChange', () => {
      // A drag changes only the order (not the checked set), so refresh positions
      // and re-emit sortChange (consumed by the CVD + legacy BaseElementIndex).
      this.updateReorderPositions();
      this.trigger('sortChange');
    });
  }

  initItem(item: any): Item {
    return new Item(this, item);
  }

  /**
   * Watch the container for `.cp-checkbox-select__item`s added or removed after
   * boot — e.g. the Card View Designer appends a checkbox when a generated field
   * is named — and keep the sorter in sync: a new item gets {@link initItem} (its
   * `<craft-reorder-button>` drag handle), a removed one is dropped from the
   * sorter. Existing items are wired synchronously in {@link init}, so the
   * observer only ever sees later mutations. It watches **direct children only**
   * (`childList`, no `subtree`), so prepending the reorder button inside an item
   * can't re-trigger it.
   */
  observeItems(): void {
    const containerEl: Element | undefined = this.$container?.[0];
    if (!containerEl) {
      return;
    }

    this.itemObserver = new MutationObserver((mutations) => {
      let changed = false;

      for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
          if (
            node instanceof HTMLElement &&
            node.matches('.cp-checkbox-select__item:not(.all)') &&
            !node.querySelector(':scope > craft-reorder-button')
          ) {
            this.initItem(node);
            changed = true;
          }
        }

        for (const node of mutation.removedNodes) {
          if (
            node instanceof HTMLElement &&
            node.matches('.cp-checkbox-select__item:not(.all)')
          ) {
            this.dragSort?.removeItems(node);
            changed = true;
          }
        }
      }

      // Refresh button enabled/disabled state, sorter membership, and positions
      // for the new item set.
      if (changed) {
        this.updateReorderButtons();
      }
    });

    this.itemObserver.observe(containerEl, {childList: true});
  }

  /**
   * Tear down the sorter and release the back-references so the controller can be
   * re-booted (e.g. when the FLD host innerHTML is swapped). Item listeners on the
   * detached DOM are GC'd; this disposes the DragSort (which would otherwise keep
   * its pointer bindings) and clears the WeakMap + jQuery `.data` entries.
   */
  override destroy(): void {
    this.itemObserver?.disconnect();
    this.itemObserver = null;

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
   * Whether an item is checked. The checkbox is either a direct-child native
   * `<input type=checkbox>` (legacy markup) or an input slotted inside a
   * `<craft-checkbox>`. Always read the native input — never the
   * `craft-checkbox` host: the host's `checked` is Lion state that updates in
   * Lion's own `change` listener, which can run *after* ours (making every
   * read one toggle stale), and it isn't adopted yet when this runs at boot.
   * The input's checkedness is already current when `change` fires.
   */
  isItemChecked(item: HTMLElement): boolean {
    return !!item.querySelector<HTMLInputElement>(
      ':scope > input[type=checkbox], :scope > craft-checkbox input[type=checkbox]'
    )?.checked;
  }

  /**
   * Recompute every item's reorder state: a button is enabled only when its item is
   * checked and 2+ items are checked overall. Enabled ⇒ in the sorter (draggable);
   * disabled ⇒ removed (membership is what gates dragging). Called whenever the
   * checked set changes, since one check can flip a sibling at the 1↔2 boundary.
   */
  updateReorderButtons(): void {
    const containerEl: Element | undefined = this.$container?.[0];
    if (!containerEl) {
      return;
    }

    const items = Array.from(
      containerEl.querySelectorAll<HTMLElement>(
        ':scope > .cp-checkbox-select__item:not(.all)'
      )
    );

    const isChecked = (item: HTMLElement): boolean => this.isItemChecked(item);

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
   * Recompute each button's `position` within the CHECKED subset: first checked ⇒
   * 'first' (disables Move up), last checked ⇒ 'last' (disables Move down), rest ⇒
   * 'middle' (unchecked items fall through to 'middle', but are disabled anyway).
   *
   * Reorders don't change which items are checked, so the reorder paths call this
   * instead of the full {@link updateReorderButtons}, leaving disabled state and
   * sorter membership untouched.
   */
  updateReorderPositions(): void {
    const containerEl: Element | undefined = this.$container?.[0];
    if (!containerEl) {
      return;
    }

    const items = Array.from(
      containerEl.querySelectorAll<HTMLElement>(
        ':scope > .cp-checkbox-select__item:not(.all)'
      )
    );

    const checkedFlags = items.map((item) => this.isItemChecked(item));
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
 * always-present `<craft-reorder-button>` and the Move up/down handlers; the
 * enabled/membership state is driven by the parent's
 * {@link SortableCheckboxSelect.updateReorderButtons}.
 */
export class Item extends Base {
  select: SortableCheckboxSelect;
  $item: any = null;
  $checkbox: any = null;
  reorderBtn: HTMLElement | null = null;

  constructor(select?: SortableCheckboxSelect, item?: any) {
    super();
    // Assigned here (not just in init) so TS sees it as definitely assigned.
    this.select = select!;
    if (new.target === Item) {
      this.init(select!, item);
    }
  }

  init(select: SortableCheckboxSelect, item: any): void {
    this.select = select;
    this.$item = $(item);
    // The checkbox is either a direct-child native input (legacy markup) or a
    // slotted input inside a `<craft-checkbox>`, so match by descendant rather
    // than direct child — otherwise the change listener never binds and
    // checking items never enables reordering.
    this.$checkbox = this.$item.find('input[type=checkbox]').first();

    // Always-present drag handle + Move up/down menu, before the checkbox.
    this.reorderBtn = document.createElement('craft-reorder-button');
    this.reorderBtn.addEventListener('craft-reorder', (event: Event) => {
      if (!(event instanceof CustomEvent)) {
        return;
      }
      // SAFETY: The reorder control emits a detail object with this registered direction contract.
      const {direction} = event.detail as {direction: ReorderDirection};
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

    // Emit the initial checked/unchecked state for the legacy event contract;
    // the parent sets disabled/sorter state once all items exist.
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
      '.cp-checkbox-select__item:not(.all):has(input[type=checkbox]:checked):first'
    );
    return $item.length ? $item : null;
  }

  getNextCheckedItem(): any {
    const $item = this.$item.nextAll(
      '.cp-checkbox-select__item:not(.all):has(input[type=checkbox]:checked):first'
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
