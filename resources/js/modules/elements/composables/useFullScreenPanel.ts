import {useEventListener} from '@vueuse/core';
import {onBeforeUnmount, onMounted, toValue, type MaybeRefOrGetter} from 'vue';
import {uiLayerManager} from '@/common/slideouts/panel-stack';

/**
 * Keeps the page's panel over the rest of the control panel, as Live Preview
 * is, while the page is open: the panel's styles stretch it over the screen,
 * and this leaves everything around it inert, so the sidebar and header bar
 * it covers can't be reached, and closes the page with Escape, unless
 * something open over it, such as a menu, takes the key. Only the page
 * screen's own chrome goes inert: the live regions, skip links, dialogs and
 * slideouts outside it stay usable.
 */
export function useFullScreenPanel(
  content: MaybeRefOrGetter<HTMLElement | null | undefined>,
  close: () => void
): void {
  const madeInert: HTMLElement[] = [];

  onMounted(() => {
    const panel = toValue(content)?.closest<HTMLElement>('.cp-body__panel');
    const screen = panel?.closest<HTMLElement>('.page-screen');

    for (let node = panel; node && node !== screen; node = node.parentElement) {
      for (const sibling of node.parentElement?.children ?? []) {
        if (
          sibling !== node &&
          sibling instanceof HTMLElement &&
          !sibling.inert
        ) {
          sibling.inert = true;
          madeInert.push(sibling);
        }
      }
    }
  });

  onBeforeUnmount(() => {
    madeInert.forEach((element) => (element.inert = false));
  });

  // Not a shortcut on the base layer: those fire even when an open menu or
  // popover has already handled the key, which it marks by preventing it.
  useEventListener(document, 'keydown', (event: KeyboardEvent) => {
    if (
      event.key === 'Escape' &&
      !event.defaultPrevented &&
      !event.isComposing &&
      uiLayerManager().layer === 0
    ) {
      close();
    }
  });
}
