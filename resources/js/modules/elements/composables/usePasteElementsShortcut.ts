import {useEventListener} from '@vueuse/core';
import {toValue, type MaybeRefOrGetter} from 'vue';
import {pastesInto} from '@/common/craft/system-clipboard';
import {isCopiedElementsText} from '../clipboard';

/**
 * Pastes the copied elements when Ctrl/Cmd+V is pressed within `target`,
 * outside anything text is typed into and fields nested in it, while the
 * system clipboard still holds the titles they were copied as. What's pasted
 * is the copied elements, never what's on the clipboard.
 */
export function usePasteElementsShortcut(
  target: MaybeRefOrGetter<HTMLElement | null | undefined>,
  options: {canPaste: () => boolean; paste: () => unknown}
): void {
  useEventListener(target, 'paste', (event: ClipboardEvent) => {
    if (
      event.defaultPrevented ||
      !options.canPaste() ||
      !pastesInto(event, toValue(target) ?? null) ||
      !isCopiedElementsText(event.clipboardData?.getData('text/plain') ?? '')
    ) {
      return;
    }

    event.preventDefault();
    void options.paste();
  });
}
