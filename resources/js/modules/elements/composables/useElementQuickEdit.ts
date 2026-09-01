import {useDebounceFn} from '@vueuse/core';
import {openSlideout, type SlideoutSaveResult} from '@/common/slideouts';
import {useElementIndexTable} from '@/modules/elements/composables/useElementIndexTable';

/**
 * Anything in a row that owns its own click. Double-clicking one of these
 * should do whatever that control does — follow the link, toggle the checkbox,
 * open the menu — not open an editor behind it.
 *
 * Craft 5 checked `a[href], button, [role=button], .move`; the rest are the CP
 * web components that appear in a Vue index row.
 */
const INTERACTIVE_SELECTOR = [
  'a[href]',
  'button',
  'input',
  'select',
  'textarea',
  'label',
  '[role="button"]',
  '[role="link"]',
  '[role="menuitem"]',
  '[contenteditable="true"]',
  '.move',
  'craft-button',
  'craft-checkbox',
  'craft-action-menu',
  'craft-reorder-button',
].join(', ');

/**
 * Double-click a row in an element index to edit it in a slideout.
 *
 * Bound once on the element container and delegated, the way Craft 5's
 * `BaseElementIndexView` does it — so the table and cards views both get it
 * without either having to know about it.
 */
export interface ElementQuickEditDependencies {
  openSlideout: typeof openSlideout;
  refreshResults: () => void;
}

function defaultDependencies(): ElementQuickEditDependencies {
  return {
    openSlideout,
    refreshResults: useElementIndexTable().refreshResults,
  };
}

export function useElementQuickEdit(
  dependencies: ElementQuickEditDependencies = defaultDependencies()
) {
  // The active index's partial reload — the same one a bulk action triggers,
  // minus clearing the selection. Editing one row shouldn't deselect anything.
  const {openSlideout: open, refreshResults} = dependencies;

  /**
   * Drafts autosave as the user types, and each one is a chance for the row to
   * drift out of date. Trailing-edge only: mid-word rows aren't worth a
   * request, and the last one always lands.
   */
  const refreshSoon = useDebounceFn(refreshResults, 600);

  function onSaved(result: SlideoutSaveResult): void {
    if (result.draft) {
      void refreshSoon();

      return;
    }

    refreshResults();
  }

  /**
   * The row (table) or card (cards view) an event landed in.
   *
   * Cards put the `element` class on the `<li>` and the element's `data-`
   * metadata on the `<craft-card>` inside it, so the class is the only thing
   * common to both shapes.
   */
  function rowFrom(target: Element): Element | null {
    return target.closest('tr') ?? target.closest('.element');
  }

  /**
   * The node carrying the element's metadata.
   *
   * In a table that's the title chip; in cards it's the `<craft-card>`. Taking
   * the first match in document order picks the row's own element rather than
   * one referenced by a later column (an author chip, say) — the same thing
   * Craft 5's `.find('.element:first')` did.
   */
  function elementIn(row: Element): HTMLElement | null {
    const element = row.matches('[data-cp-url]')
      ? row
      : row.querySelector<HTMLElement>('[data-cp-url]');

    if (!(element instanceof HTMLElement)) {
      return null;
    }

    // `data-editable` / `data-trashed` are omitted entirely when false, so
    // presence is the test — same as the legacy `Garnish.hasAttr` check.
    if (
      !element.hasAttribute('data-editable') ||
      element.hasAttribute('data-trashed')
    ) {
      return null;
    }

    // Inside an element picker the rows are a selection UI, not an index.
    return element.closest('.elementselect') ? null : element;
  }

  function onDblClick(event: MouseEvent): void {
    const target = event.target;

    if (!(target instanceof Element)) {
      return;
    }

    const row = rowFrom(target);

    if (!row) {
      return;
    }

    // Scoped to the row so a control somewhere else on the page can't suppress
    // a legitimate double-click.
    const control = target.closest(INTERACTIVE_SELECTOR);

    if (control && row.contains(control)) {
      return;
    }

    const element = elementIn(row);

    if (!element) {
      return;
    }

    event.preventDefault();

    // Two fast clicks leave a text selection behind.
    window.getSelection()?.removeAllRanges();

    void open(element.dataset.cpUrl!, {
      opener: row instanceof HTMLElement ? row : null,
      onSaved,
    });
  }

  return {onDblClick};
}
