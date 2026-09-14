import {computed, shallowRef} from 'vue';
import type {Table} from '@tanstack/vue-table';

interface ElementIndexHandle {
  /** The listing's tanstack table (selection, state, …). */
  table: Table<any>;
  /** The index's post-action refresh: clears selection + partial reload. */
  onActionPerformed: () => void;
  /** Re-pull just the results, leaving selection and scroll alone. */
  refreshResults: () => void;
}

// The active element index. There's a single one per CP page, so a module-scoped
// slot is enough to hand its table + refresh action — created deep inside the
// shared pipeline (useElementIndexPage) — to components that layer behavior on
// top of the listing, without prop-drilling or reaching through a component ref.
// The asset page, for one, uses it to wire up drag-and-drop file moves.
const active = shallowRef<ElementIndexHandle | null>(null);

export function useElementIndexTable() {
  /**
   * Publish the active element index. The pipeline calls this on setup with its
   * handle and clears it (passing `null`) on teardown; the guard there avoids a
   * just-mounted page's index being wiped by the previous page unmounting.
   */
  function register(handle: ElementIndexHandle | null) {
    active.value = handle;
  }

  const table = computed(() => active.value?.table ?? null);

  /** Trigger the active index's post-action refresh, if one is registered. */
  function onActionPerformed() {
    active.value?.onActionPerformed();
  }

  /** Re-pull the active index's results, if one is registered. */
  function refreshResults() {
    active.value?.refreshResults();
  }

  return {table, onActionPerformed, refreshResults, register};
}
