import {computed, shallowRef, type ComputedRef} from 'vue';
import {
  getCopiedElements,
  onCopiedElementsChange,
  type CopiedElementInfo,
} from '@/modules/elements/clipboard';

/**
 * What's on the CP's element clipboard, kept live.
 *
 * The elements are filled in asynchronously, and an entry's `entryTypeId`
 * comes with them, so until then the list stays empty and a paste target
 * waits rather than offering something the server would refuse.
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
    copied.value = getCopiedElements();
    onCopiedElementsChange((elements) => {
      copied.value = elements;
    });
  }

  return copiedElements;
}
