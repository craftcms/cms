import {
  type ComponentPublicInstance,
  nextTick,
  onMounted,
  onUnmounted,
  shallowRef,
  triggerRef,
  watch,
} from 'vue';
import {
  type Axis,
  type DragData,
  type DragState,
  type DropState,
  type ForeignDropTarget,
  useDragAndDrop,
} from './useDragAndDrop.js';

export type {DragData, DragState, DropState, ForeignDropTarget};

type ReorderableElement = Element | ComponentPublicInstance | null;

export interface UseReorderableItemsOptions {
  getItemIds: () => Array<string | number>;
  onReorder: (startIndex: number, finishIndex: number) => void;
  enabled?: () => boolean;
  axis?: Axis;
  /** Extra data to attach to a row's drag payload, for other lists to read. */
  dragData?: (id: string | number, index: number) => DragData;
  /** Whether a row dragged out of another list may be dropped onto a row here. */
  canDropForeign?: (data: DragData) => boolean;
  /** A foreign row was dropped on this list, at the given position. */
  onForeignDrop?: (data: DragData, target: ForeignDropTarget) => void;
}

export interface UseReorderableItemsReturn {
  setItemRef: (el: ReorderableElement, itemId: string | number) => void;
  setHandleRef: (el: ReorderableElement, itemId: string | number) => void;
  getDragState: (id: string | number) => DragState;
  getDropState: (id: string | number) => DropState;
  refreshRegistrations: () => void;
  getRowPosition: (index: number) => 'first' | 'middle' | 'last';
}

export function useReorderableItems(
  options: UseReorderableItemsOptions
): UseReorderableItemsReturn {
  const itemRefs = shallowRef<Map<string | number, HTMLElement>>(new Map());
  const handleRefs = shallowRef<Map<string | number, HTMLElement>>(new Map());
  const cleanupFns = new Map<string | number, () => void>();
  let monitorCleanup: (() => void) | null = null;
  let mounted = false;
  let unmounted = false;
  let refreshScheduled = false;

  const {registerItem, getDragState, getDropState, setupMonitor} =
    useDragAndDrop({
      onReorder: options.onReorder,
      axis: options.axis ?? 'vertical',
      dragData: (id, index) => options.dragData?.(id, index) ?? {},
      canDropForeign: (data) => options.canDropForeign?.(data) ?? false,
      onForeignDrop: (data, target) => options.onForeignDrop?.(data, target),
    });

  function resolveElement(el: ReorderableElement): HTMLElement | null {
    if (el instanceof HTMLElement) {
      return el;
    }

    if (el && !(el instanceof Element) && el.$el instanceof HTMLElement) {
      return el.$el;
    }

    return null;
  }

  function scheduleRefreshRegistrations() {
    if (!mounted || unmounted || refreshScheduled) {
      return;
    }

    refreshScheduled = true;

    nextTick(() => {
      refreshScheduled = false;

      if (!unmounted) {
        refreshRegistrations();
      }
    });
  }

  function setItemRef(el: ReorderableElement, itemId: string | number) {
    const element = resolveElement(el);
    const current = itemRefs.value.get(itemId);

    if (element) {
      if (current === element) {
        return;
      }

      itemRefs.value.set(itemId, element);
    } else if (current) {
      itemRefs.value.delete(itemId);
    } else {
      return;
    }

    triggerRef(itemRefs);
    scheduleRefreshRegistrations();
  }

  function setHandleRef(el: ReorderableElement, itemId: string | number) {
    const element = resolveElement(el);
    const current = handleRefs.value.get(itemId);

    if (element) {
      if (current === element) {
        return;
      }

      handleRefs.value.set(itemId, element);
    } else if (current) {
      handleRefs.value.delete(itemId);
    } else {
      return;
    }

    triggerRef(handleRefs);
    scheduleRefreshRegistrations();
  }

  function isEnabled(): boolean {
    return options.enabled?.() ?? true;
  }

  function refreshRegistrations() {
    // Clean up existing registrations
    cleanupFns.forEach((fn) => fn());
    cleanupFns.clear();

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
        cleanupFns.set(itemId, cleanup);
      }
    });
  }

  // Re-register when item IDs change
  watch(
    () => options.getItemIds(),
    () => {
      scheduleRefreshRegistrations();
    },
    {deep: true}
  );

  // Re-register when enabled state changes.
  watch(
    () => isEnabled(),
    () => {
      scheduleRefreshRegistrations();
    }
  );

  onMounted(() => {
    mounted = true;

    // Setup the monitor
    monitorCleanup = setupMonitor();
    scheduleRefreshRegistrations();
  });

  onUnmounted(() => {
    unmounted = true;
    cleanupFns.forEach((fn) => fn());
    cleanupFns.clear();
    monitorCleanup?.();
  });

  function getRowPosition(index: number) {
    if (index === 0) {
      return 'first';
    }

    if (index === itemRefs.value.size - 1) {
      return 'last';
    }

    return 'middle';
  }

  return {
    setItemRef,
    setHandleRef,
    getDragState,
    getDropState,
    getRowPosition,
    refreshRegistrations,
  };
}
