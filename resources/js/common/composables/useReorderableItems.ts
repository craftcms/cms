import {
  nextTick,
  onMounted,
  onUnmounted,
  ref,
  shallowRef,
  triggerRef,
  watch,
} from 'vue';
import {
  type Axis,
  type DragState,
  type DropState,
  useDragAndDrop,
} from './useDragAndDrop';

export type {DragState, DropState};

export interface UseReorderableItemsOptions {
  getItemIds: () => Array<string | number>;
  onReorder: (startIndex: number, finishIndex: number) => void;
  enabled?: () => boolean;
  axis?: Axis;
}

export interface UseReorderableItemsReturn {
  setItemRef: (el: HTMLElement | null, itemId: string | number) => void;
  setHandleRef: (el: HTMLElement | null, itemId: string | number) => void;
  getDragState: (id: string | number) => DragState;
  getDropState: (id: string | number) => DropState;
  refreshRegistrations: () => void;
}

export function useReorderableItems(
  options: UseReorderableItemsOptions
): UseReorderableItemsReturn {
  const itemRefs = ref<Map<string | number, HTMLElement>>(new Map());
  const handleRefs = shallowRef<Map<string | number, HTMLElement>>(new Map());
  const cleanupFns = ref<Map<string | number, () => void>>(new Map());
  let monitorCleanup: (() => void) | null = null;

  const {registerItem, getDragState, getDropState, setupMonitor} =
    useDragAndDrop({
      onReorder: options.onReorder,
      axis: options.axis ?? 'vertical',
    });

  function setItemRef(el: HTMLElement | null, itemId: string | number) {
    if (el) {
      itemRefs.value.set(itemId, el);
    } else {
      itemRefs.value.delete(itemId);
    }
  }

  function setHandleRef(el: HTMLElement | null, itemId: string | number) {
    if (el) {
      handleRefs.value.set(itemId, el);
    } else {
      handleRefs.value.delete(itemId);
    }
    triggerRef(handleRefs);
  }

  function isEnabled(): boolean {
    return options.enabled?.() ?? true;
  }

  function refreshRegistrations() {
    // Clean up existing registrations
    cleanupFns.value.forEach((fn) => fn());
    cleanupFns.value.clear();

    if (!isEnabled()) {
      return;
    }

    // Register each item
    const itemIds = options.getItemIds();
    itemIds.forEach((itemId, index) => {
      const itemEl = itemRefs.value.get(itemId);
      const handleEl = handleRefs.value.get(itemId);

      if (itemEl) {
        const cleanup = registerItem(itemEl, handleEl ?? null, itemId, index);
        cleanupFns.value.set(itemId, cleanup);
      }
    });
  }

  // Re-register when item IDs change
  watch(
    () => options.getItemIds(),
    () => {
      nextTick(refreshRegistrations);
    },
    {deep: true}
  );

  // Re-register when handle refs change (handles may be set after initial mount)
  watch(
    () => handleRefs.value.size,
    () => {
      nextTick(refreshRegistrations);
    }
  );

  onMounted(() => {
    // Setup the monitor
    monitorCleanup = setupMonitor();
    nextTick(refreshRegistrations);
  });

  onUnmounted(() => {
    cleanupFns.value.forEach((fn) => fn());
    monitorCleanup?.();
  });

  return {
    setItemRef,
    setHandleRef,
    getDragState,
    getDropState,
    refreshRegistrations,
  };
}
