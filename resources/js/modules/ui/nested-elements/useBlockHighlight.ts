import {onBeforeUnmount, ref} from 'vue';
import {NEW_BLOCK_HIGHLIGHT_MS} from '@/modules/matrix/new-block';

/**
 * Blocks that have just appeared, for as long as their highlight runs.
 *
 * Held as state rather than put on the element the way the other stacks do
 * it: Vue owns these blocks' classes and would patch a stray one away on the
 * next render.
 */
export function useBlockHighlight() {
  const justAdded = ref(new Set<string>());
  const highlights = new Set<ReturnType<typeof setTimeout>>();

  function highlightBlock(uid: string): void {
    justAdded.value = new Set(justAdded.value).add(uid);

    const timer = setTimeout(() => {
      highlights.delete(timer);
      const next = new Set(justAdded.value);
      next.delete(uid);
      justAdded.value = next;
    }, NEW_BLOCK_HIGHLIGHT_MS);

    highlights.add(timer);
  }

  function isHighlighted(uid: string): boolean {
    return justAdded.value.has(uid);
  }

  onBeforeUnmount(() => {
    for (const timer of highlights) {
      clearTimeout(timer);
    }
    highlights.clear();
  });

  return {highlightBlock, isHighlighted};
}
