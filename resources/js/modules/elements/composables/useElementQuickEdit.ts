import {onScopeDispose} from 'vue';
import {openSlideout} from '@/common/slideouts';

/**
 * How long a plain click on an element link waits to see whether it's actually
 * the first half of a double-click. Matches the platform double-click window
 * closely enough that it isn't perceptible before a page load.
 */
const DOUBLE_CLICK_WINDOW = 250;

/**
 * Double-click an element in an index to edit it in a slideout.
 *
 * Bound once on the element container and delegated, the way Craft 5's
 * `BaseElementIndexView` does it — so it covers both the table and cards views
 * without either having to know about it.
 *
 * The one thing Craft 5 didn't have to solve: there, the dblclick handler bails
 * when the target is inside `a[href]` and lets the link win. In the Vue index
 * the whole title cell *is* a link (`ContentIndexViewModel` wraps the chip in a
 * `CpLink`), so that guard would disable double-click exactly where you'd aim
 * it. Instead the single click is held for {@link DOUBLE_CLICK_WINDOW} and
 * released if no second click arrives.
 */
export function useElementQuickEdit() {
  let pendingNavigation: number | undefined;

  function cancelPendingNavigation(): void {
    if (pendingNavigation !== undefined) {
      window.clearTimeout(pendingNavigation);
      pendingNavigation = undefined;
    }
  }

  onScopeDispose(cancelPendingNavigation);

  /**
   * The editable element a pointer event landed on, or `null` if this event
   * shouldn't open an editor.
   */
  function editableElementFrom(target: EventTarget | null): HTMLElement | null {
    if (!(target instanceof Element)) {
      return null;
    }

    // Fall back to the row's own element so a double-click anywhere in the row
    // works, not just on the chip.
    const element =
      target.closest<HTMLElement>('.element') ??
      target.closest('tr')?.querySelector<HTMLElement>('.element') ??
      null;

    if (!element) {
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

    // Inside an element picker the chips are a selection UI, not an index.
    if (element.closest('.elementselect')) {
      return null;
    }

    return element.dataset.cpUrl ? element : null;
  }

  function onClick(event: MouseEvent): void {
    // Leave modified and non-primary clicks alone: open-in-new-tab and friends
    // should stay instant.
    if (
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey
    ) {
      return;
    }

    const target = event.target;
    const link =
      target instanceof Element
        ? target.closest<HTMLAnchorElement>('a[href]')
        : null;

    if (!link || !editableElementFrom(target)) {
      // Nothing to open on double-click here, so don't make the link wait.
      return;
    }

    event.preventDefault();
    // Bound in the capture phase and stopped here so the anchor's own click
    // handling never runs. An Inertia `<Link>` starts its visit from a listener
    // on the anchor itself — deeper than this one, so in the bubble phase it
    // would already have navigated by the time we saw the event.
    event.stopPropagation();
    cancelPendingNavigation();

    const {href} = link;

    pendingNavigation = window.setTimeout(() => {
      pendingNavigation = undefined;
      window.location.href = href;
    }, DOUBLE_CLICK_WINDOW);
  }

  function onDblClick(event: MouseEvent): void {
    const element = editableElementFrom(event.target);

    if (!element) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    cancelPendingNavigation();

    // Two fast clicks leave a text selection behind.
    window.getSelection()?.removeAllRanges();

    const opener =
      event.target instanceof Element
        ? (event.target.closest<HTMLElement>('a[href]') ?? element)
        : element;

    void openSlideout(element.dataset.cpUrl!, {opener});
  }

  return {onClick, onDblClick};
}
