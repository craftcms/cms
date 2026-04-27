import {nextTick, onMounted, onUnmounted, ref, watch} from 'vue';
import {
  type DragState,
  type DropState,
  useTableDragAndDrop,
} from './useTableDragAndDrop';

export type {DragState, DropState};

export interface UseReorderableRowsOptions {
  getRowIds: () => Array<string | number>;
  onReorder: (startIndex: number, finishIndex: number) => void;
  enabled: () => boolean;
}

export function useReorderableRows(options: UseReorderableRowsOptions) {
  const rowRefs = ref<Map<string, HTMLTableRowElement>>(new Map());
  const handleRefs = ref<Map<string, HTMLElement>>(new Map());
  const cleanupFns = ref<Map<string, () => void>>(new Map());

  const {registerRow, getDragState, getDropState} = useTableDragAndDrop({
    onReorder: options.onReorder,
    getRowId: (row) => row.id,
  });

  function setRowRef(el: HTMLTableRowElement | null, rowId: string) {
    if (el) {
      rowRefs.value.set(rowId, el);
    } else {
      rowRefs.value.delete(rowId);
    }
  }

  function setHandleRef(el: HTMLElement | null, rowId: string) {
    if (el) {
      handleRefs.value.set(rowId, el);
    } else {
      handleRefs.value.delete(rowId);
    }
  }

  function refreshRegistrations() {
    if (!options.enabled()) {
      return;
    }

    // Clean up existing registrations
    cleanupFns.value.forEach((fn) => fn());
    cleanupFns.value.clear();

    // Register each row
    const rowIds = options.getRowIds();
    rowIds.forEach((rowId, index) => {
      const id = String(rowId);
      const rowEl = rowRefs.value.get(id);
      const handleEl = handleRefs.value.get(id);

      if (rowEl) {
        const cleanup = registerRow(rowEl, handleEl ?? null, id, index);
        cleanupFns.value.set(id, cleanup);
      }
    });
  }

  // Re-register when row IDs change
  watch(
    () => options.getRowIds(),
    () => {
      nextTick(refreshRegistrations);
    },
    {deep: true}
  );

  onMounted(() => {
    nextTick(refreshRegistrations);
  });

  onUnmounted(() => {
    cleanupFns.value.forEach((fn) => fn());
  });

  return {
    setRowRef,
    setHandleRef,
    getDragState,
    getDropState,
    refreshRegistrations,
  };
}
