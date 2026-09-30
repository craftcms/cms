import {
  nextTick,
  onMounted,
  onUnmounted,
  shallowRef,
  triggerRef,
  watch,
} from 'vue';
import {
  type DragState,
  type DropState,
  useDragAndDrop,
} from '@/common/composables/useDragAndDrop.js';
export type {DragState, DropState};

export interface UseReorderableRowsOptions {
  getRowIds: () => Array<string | number>;
  onReorder: (startIndex: number, finishIndex: number) => void;
  enabled: () => boolean;
}

export function useReorderableRows(options: UseReorderableRowsOptions) {
  const rowRefs = shallowRef<Map<string, HTMLTableRowElement>>(new Map());
  const handleRefs = shallowRef<Map<string, HTMLElement>>(new Map());
  const cleanupFns = new Map<string, () => void>();
  let monitorCleanup: (() => void) | null = null;
  let mounted = false;
  let refreshScheduled = false;

  const {registerItem, getDragState, getDropState, setupMonitor} =
    useDragAndDrop({
      onReorder: options.onReorder,
      axis: 'vertical',
    });

  function setRowRef(el: HTMLTableRowElement | null, rowId: string) {
    if (rowRefs.value.get(rowId) === el) return;

    if (el) {
      rowRefs.value.set(rowId, el);
    } else {
      rowRefs.value.delete(rowId);
    }

    triggerRef(rowRefs);
    scheduleRefreshRegistrations();
  }

  function setHandleRef(el: HTMLElement | null, rowId: string) {
    if (handleRefs.value.get(rowId) === el) return;

    if (el) {
      handleRefs.value.set(rowId, el);
    } else {
      handleRefs.value.delete(rowId);
    }

    triggerRef(handleRefs);
    scheduleRefreshRegistrations();
  }

  function scheduleRefreshRegistrations() {
    if (!mounted || refreshScheduled) return;

    refreshScheduled = true;
    void nextTick(() => {
      refreshScheduled = false;

      if (mounted) {
        refreshRegistrations();
      }
    });
  }

  function refreshRegistrations() {
    cleanupFns.forEach((fn) => fn());
    cleanupFns.clear();

    if (!options.enabled()) {
      return;
    }

    const rowIds = options.getRowIds();
    rowIds.forEach((rowId, index) => {
      const id = String(rowId);
      const rowEl = rowRefs.value.get(id);
      const handleEl = handleRefs.value.get(id);

      if (rowEl) {
        const cleanup = registerItem(rowEl, handleEl ?? null, id, index);
        cleanupFns.set(id, cleanup);
      }
    });
  }

  // Re-register when row IDs change
  watch(
    () => options.getRowIds(),
    () => {
      scheduleRefreshRegistrations();
    },
    {deep: true}
  );

  watch(
    () => options.enabled(),
    () => scheduleRefreshRegistrations()
  );

  onMounted(() => {
    mounted = true;
    monitorCleanup = setupMonitor();
    scheduleRefreshRegistrations();
  });

  onUnmounted(() => {
    mounted = false;
    cleanupFns.forEach((fn) => fn());
    cleanupFns.clear();
    monitorCleanup?.();
  });

  return {
    setRowRef,
    setHandleRef,
    getDragState,
    getDropState,
    refreshRegistrations,
  };
}
