import {computed, shallowRef, type ComputedRef} from 'vue';
import {craft, type CopiedElementInfo} from './interop';

/**
 * What's on the CP's element clipboard, kept live.
 *
 * `Craft.cp` owns the clipboard — it lives in localStorage and is shared with
 * the legacy stack and with other tabs. Its `onCopyElements()` has no
 * unregister, so the callback is registered once for the page and every caller
 * reads the same ref.
 *
 * The elements are enriched asynchronously: `Craft.cp` renders a chip for each
 * one and hangs its data attributes off `data`, which is where an entry's
 * `entryTypeId` comes from. Until that lands the list stays empty, so a paste
 * target waits rather than offering something the server would refuse.
 */
// `shallowRef`, not `ref`: the entries carry arbitrary chip data, whose
// recursive JSON type TypeScript can't unwrap for deep reactivity. The list is
// replaced wholesale anyway.
const copied = shallowRef<CopiedElementInfo[]>([]);
const copiedElements = computed(() => copied.value);
let listening = false;

export function useCopiedElements(): ComputedRef<CopiedElementInfo[]> {
  if (!listening) {
    listening = true;
    copied.value = craft().cp?.getCopiedElements?.() ?? [];
    craft().cp?.onCopyElements?.((elementInfo) => {
      copied.value = elementInfo ?? [];
    });
  }

  return copiedElements;
}
