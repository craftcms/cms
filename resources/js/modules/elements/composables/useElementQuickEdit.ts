import {useDebounceFn} from '@vueuse/core';
import {openSlideout, type SlideoutSaveResult} from '@/common/slideouts';

/**
 * Anything in a row that owns its own click. Double-clicking one of these
 * should do whatever that control does — follow the link, toggle the checkbox,
 * open the menu — not open an editor behind it.
 *
 * Craft 5 checked `a[href], button, [role=button], .move`; the rest are the CP
 * web components that appear in a Vue index row.
 */
export const ELEMENT_QUICK_EDIT_CONTROL_SELECTOR = [
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
 * What a press acts on straight away, rather than waiting out a possible
 * double-click — whether it's inside a chip or wrapped around it, as the title
 * column's link is.
 */
const IMMEDIATE_PRESS_SELECTOR = [
  'a[href]',
  'button',
  '[role="button"]',
  '[role="link"]',
  'craft-button',
].join(', ');

/**
 * How long a chip's first click waits for a second one before doing what a
 * click there normally does. Browsers don't expose the OS double-click
 * interval, so this is the common default.
 */
export const CHIP_DOUBLE_CLICK_DELAY = 300;

/**
 * Double-click a row in an element index to edit it in a slideout.
 *
 * Bound once on the element container and delegated, the way Craft 5's
 * `BaseElementIndexView` does it — so the table and cards views both get it
 * without either having to know about it.
 */
export interface ElementQuickEditDependencies {
  openSlideout?: typeof openSlideout;
  refreshResults: () => void;
}

export function useElementQuickEdit(
  dependencies: ElementQuickEditDependencies
) {
  // The active index's partial reload — the same one a bulk action triggers,
  // minus clearing the selection. Editing one row shouldn't deselect anything.
  const {openSlideout: open = openSlideout, refreshResults} = dependencies;

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

  function openEditor(url: string, opener: HTMLElement | null = null): void {
    void open(url, {opener, onSaved});
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

    return isQuickEditable(element) ? element : null;
  }

  function isQuickEditable(element: HTMLElement): boolean {
    // `data-editable` / `data-trashed` are omitted entirely when false, so
    // presence is the test — same as the legacy `Garnish.hasAttr` check.
    if (
      !element.hasAttribute('data-editable') ||
      element.hasAttribute('data-trashed')
    ) {
      return false;
    }

    // Inside an element picker the rows are a selection UI, not an index.
    return !element.closest('.elementselect');
  }

  /**
   * The chip an event landed on, if it stands for an element that can be
   * edited — the row's own title chip, or one referenced by another column (a
   * related asset, an author).
   *
   * A link or button inside the chip keeps its own behavior, so an event that
   * landed on one of those doesn't count. A link wrapped around the whole chip
   * (the title column's) isn't inside it, so it doesn't either.
   */
  function chipFrom(event: Event): HTMLElement | null {
    const path = event.composedPath();
    const chipIndex = path.findIndex(
      (node) =>
        node instanceof HTMLElement && node.matches('craft-chip[data-cp-url]')
    );

    if (chipIndex === -1) {
      return null;
    }

    const chip = path[chipIndex] as HTMLElement;

    if (!isQuickEditable(chip)) {
      return null;
    }

    // The composed path pierces shadow DOM, so this sees controls the chip
    // renders inside itself as well as slotted ones.
    const overControl = path
      .slice(0, chipIndex)
      .some(
        (node) =>
          node instanceof Element &&
          node.matches(ELEMENT_QUICK_EDIT_CONTROL_SELECTOR)
      );

    return overControl ? null : chip;
  }

  let pendingChipClick: ReturnType<typeof setTimeout> | null = null;
  let replayingChipClick = false;

  function cancelPendingChipClick(): void {
    if (pendingChipClick !== null) {
      clearTimeout(pendingChipClick);
      pendingChipClick = null;
    }
  }

  /**
   * Holds a plain click on an editable chip back until it's clear it isn't the
   * first half of a double-click, so double-clicking the chip doesn't also
   * toggle the row's selection. A press on a link or button, in the chip or
   * around it, isn't held: following it shouldn't wait.
   *
   * Takes the `mouseup` as well as the `click`, since Inertia links visit on
   * `mouseup` when they prefetch on `mousedown` (as `CpLink` does by default).
   * Bind both in the capture phase, on an ancestor of the chips, so this runs
   * before anything the events would otherwise reach.
   *
   * Returns whether the event was held back.
   */
  function deferChipClick(event: MouseEvent): boolean {
    if (
      replayingChipClick ||
      event.button !== 0 ||
      // Keyboard activation, which can't be the start of a double-click.
      event.detail === 0 ||
      // Modified clicks (open in a new tab, extend the selection, …) act
      // straight away.
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey ||
      // A link or button does its own thing at once, with no wait.
      event
        .composedPath()
        .some(
          (node) =>
            node instanceof Element && node.matches(IMMEDIATE_PRESS_SELECTOR)
        ) ||
      !chipFrom(event)
    ) {
      return false;
    }

    event.stopPropagation();

    // Replayed along with the click.
    if (event.type !== 'click') {
      return true;
    }

    event.preventDefault();
    cancelPendingChipClick();

    // A later click in the same burst belongs to the double-click, which
    // `onDblClick` handles.
    if (event.detail > 1) {
      return true;
    }

    const target = event.composedPath()[0];

    if (!(target instanceof Element)) {
      return true;
    }

    const init: MouseEventInit = {
      bubbles: true,
      cancelable: true,
      composed: true,
      detail: 1,
      button: event.button,
      clientX: event.clientX,
      clientY: event.clientY,
      screenX: event.screenX,
      screenY: event.screenY,
    };

    pendingChipClick = setTimeout(() => {
      pendingChipClick = null;

      // The results may have been re-rendered in the meantime.
      if (!target.isConnected) {
        return;
      }

      replayingChipClick = true;

      try {
        target.dispatchEvent(new MouseEvent('mouseup', init));
        target.dispatchEvent(new MouseEvent('click', init));
      } finally {
        replayingChipClick = false;
      }
    }, CHIP_DOUBLE_CLICK_DELAY);

    return true;
  }

  function onDblClick(event: MouseEvent): void {
    const target = event.target;

    if (!(target instanceof Element)) {
      return;
    }

    // A chip opens its own element, which may not be the row's.
    const chip = chipFrom(event);

    if (chip) {
      cancelPendingChipClick();
      event.preventDefault();
      window.getSelection()?.removeAllRanges();

      const chipRow = rowFrom(chip);
      openEditor(
        chip.dataset.cpUrl!,
        chipRow instanceof HTMLElement ? chipRow : chip
      );

      return;
    }

    const row = rowFrom(target);

    if (!row) {
      return;
    }

    // Scoped to the row so a control somewhere else on the page can't suppress
    // a legitimate double-click.
    const control = target.closest(ELEMENT_QUICK_EDIT_CONTROL_SELECTOR);

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

    openEditor(element.dataset.cpUrl!, row instanceof HTMLElement ? row : null);
  }

  return {onDblClick, deferChipClick, openEditor};
}
