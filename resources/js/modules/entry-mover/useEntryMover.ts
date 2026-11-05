import {useEventListener} from '@vueuse/core';
import {shallowRef} from 'vue';
import type {ElementIndexOperations} from '@/modules/elements/index/types/model';

/** The entries to move, and what the section picker needs to list targets. */
export interface EntryMoveRequest {
  entryIds: number[];
  siteId: number;
  currentSectionUid: string;
}

/**
 * The entry index's Move to action, which the server serializes as the
 * `entries:move-to-section` client event (see `EntryIndexViewModel`). Opens
 * `EntryMoverModal` for the selected entries, and refreshes the index once
 * they've moved.
 */
export function useEntryMover(index: ElementIndexOperations) {
  const request = shallowRef<EntryMoveRequest | null>(null);

  useEventListener(window, 'entries:move-to-section', (event: Event) => {
    if (!(event instanceof CustomEvent)) return;

    const detail = event.detail ?? {};
    const entryIds = (detail.elementIds ?? [])
      .map(Number)
      .filter(Number.isFinite);

    if (!entryIds.length || !detail.currentSectionUid) return;

    request.value = {
      entryIds,
      siteId: Number(detail.siteId),
      currentSectionUid: String(detail.currentSectionUid),
    };
  });

  function close(): void {
    request.value = null;
  }

  function moved(): void {
    request.value = null;
    index.clearSelection();
    void index.refresh();
  }

  return {request, close, moved};
}
