import {
  type ComponentPublicInstance,
  type MaybeRefOrGetter,
  onUnmounted,
  ref,
  toValue,
  watch,
  type Ref,
} from 'vue';
import {draggable} from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import {disableNativeDragPreview} from '@atlaskit/pragmatic-drag-and-drop/element/disable-native-drag-preview';
import {preventUnhandled} from '@atlaskit/pragmatic-drag-and-drop/prevent-unhandled';

export interface UseResizableOptions {
  /** Element to resize */
  target: Ref<HTMLElement | null>;
  /** Resize direction. Default: 'horizontal' */
  direction?: 'horizontal' | 'vertical';
  /** Which logical edge the handle is on (RTL-aware). Default: 'end' */
  edge?: MaybeRefOrGetter<'start' | 'end'>;
  /** Minimum size constraint */
  minSize?: MaybeRefOrGetter<number>;
  /** Maximum size constraint */
  maxSize?: MaybeRefOrGetter<number>;
  /** Starting size (if not set, uses element's current size) */
  initialSize?: number;
  /** Enable/disable resize. Default: true */
  enabled?: MaybeRefOrGetter<boolean>;
  /** Called when resize starts */
  onResizeStart?: (size: number) => void;
  /** Called during resize */
  onResize?: (size: number) => void;
  /** Called when resize ends */
  onResizeEnd?: (size: number) => void;
}

export interface UseResizableReturn {
  /** Function to bind to the resize handle element's ref */
  setHandleRef: (el: Element | ComponentPublicInstance | null) => void;
  /** Current size of the element */
  size: Ref<number>;
  /** Whether resize is in progress */
  isResizing: Ref<boolean>;
  /** Reset to initial size */
  reset: () => void;
  /** Manually set the size */
  setSize: (n: number) => void;
}

/**
 * Composable for making elements resizable using @atlaskit/pragmatic-drag-and-drop.
 *
 * @example
 * ```vue
 * <script setup>
 * const panelRef = ref<HTMLElement | null>(null);
 *
 * const { setHandleRef, size, isResizing } = useResizable({
 *   target: panelRef,
 *   direction: 'horizontal',
 *   edge: 'start',
 *   minSize: 320,
 *   maxSize: 1200,
 * });
 * </script>
 *
 * <template>
 *   <div ref="panelRef" :style="{ width: `${size}px` }">
 *     <div :ref="setHandleRef" class="resize-handle" />
 *     Content
 *   </div>
 * </template>
 * ```
 */
export function useResizable(options: UseResizableOptions): UseResizableReturn {
  const {
    target,
    direction = 'horizontal',
    edge = 'end',
    minSize,
    maxSize,
    initialSize,
    enabled,
    onResizeStart,
    onResize,
    onResizeEnd,
  } = options;

  const handleRef = ref<HTMLElement | null>(null);
  const size = ref(initialSize ?? 0);
  const isResizing = ref(false);
  const initialSizeValue = initialSize ?? 0;

  let cleanup: (() => void) | null = null;
  let startPosition = 0;
  let startSize = 0;

  /**
   * Clamp size to min/max constraints
   */
  function clampSize(value: number): number {
    const min = toValue(minSize) ?? 0;
    const max = toValue(maxSize) ?? Infinity;
    return Math.min(max, Math.max(min, value));
  }

  /**
   * Calculate delta based on direction and edge.
   * For horizontal resizing:
   * - 'start' edge: dragging toward start increases size
   * - 'end' edge: dragging toward end increases size
   */
  function calculateDelta(startPos: number, currentPos: number): number {
    const rawDelta = currentPos - startPos;
    const edgeValue = toValue(edge) ?? 'end';

    if (direction === 'horizontal') {
      // For horizontal resizing, we need to consider text direction for RTL support
      const isRTL =
        document.documentElement.dir === 'rtl' ||
        getComputedStyle(document.documentElement).direction === 'rtl';

      if (edgeValue === 'start') {
        // Handle on start edge: dragging toward start (left in LTR) increases width
        return isRTL ? rawDelta : -rawDelta;
      } else {
        // Handle on end edge: dragging toward end (right in LTR) increases width
        return isRTL ? -rawDelta : rawDelta;
      }
    } else {
      // Vertical resizing
      if (edgeValue === 'start') {
        // Handle on top: dragging up increases height
        return -rawDelta;
      } else {
        // Handle on bottom: dragging down increases height
        return rawDelta;
      }
    }
  }

  function setupDraggable() {
    if (cleanup) {
      cleanup();
      cleanup = null;
    }

    const handle = handleRef.value;
    if (!handle) return;

    if (toValue(enabled) === false) return;

    cleanup = draggable({
      element: handle,
      onGenerateDragPreview({nativeSetDragImage}) {
        disableNativeDragPreview({nativeSetDragImage});
        preventUnhandled.start();
      },
      onDragStart({location}) {
        const targetEl = target.value;
        if (!targetEl) return;

        isResizing.value = true;

        // Capture initial position and size
        if (direction === 'horizontal') {
          startPosition = location.initial.input.clientX;
          startSize = targetEl.offsetWidth;
        } else {
          startPosition = location.initial.input.clientY;
          startSize = targetEl.offsetHeight;
        }

        // Set cursor and disable text selection
        document.body.style.cursor =
          direction === 'horizontal' ? 'ew-resize' : 'ns-resize';
        document.body.style.userSelect = 'none';

        onResizeStart?.(startSize);
      },
      onDrag({location}) {
        const targetEl = target.value;
        if (!targetEl) return;

        const currentPosition =
          direction === 'horizontal'
            ? location.current.input.clientX
            : location.current.input.clientY;

        const delta = calculateDelta(startPosition, currentPosition);
        const newSize = clampSize(startSize + delta);

        // Apply size to element
        if (direction === 'horizontal') {
          targetEl.style.width = `${newSize}px`;
        } else {
          targetEl.style.height = `${newSize}px`;
        }

        size.value = newSize;
        onResize?.(newSize);
      },
      onDrop() {
        preventUnhandled.stop();
        isResizing.value = false;

        // Reset cursor and text selection
        document.body.style.cursor = '';
        document.body.style.userSelect = '';

        onResizeEnd?.(size.value);
      },
    });
  }

  /**
   * Ref binding function for the resize handle
   */
  function setHandleRef(el: Element | ComponentPublicInstance | null) {
    handleRef.value = el instanceof HTMLElement ? el : null;
  }

  /**
   * Reset to initial size
   */
  function reset() {
    const targetEl = target.value;
    if (!targetEl) return;

    size.value = initialSizeValue;

    if (direction === 'horizontal') {
      targetEl.style.width = initialSizeValue ? `${initialSizeValue}px` : '';
    } else {
      targetEl.style.height = initialSizeValue ? `${initialSizeValue}px` : '';
    }
  }

  /**
   * Manually set the size
   */
  function setSize(n: number) {
    const targetEl = target.value;
    if (!targetEl) return;

    const clampedSize = clampSize(n);
    size.value = clampedSize;

    if (direction === 'horizontal') {
      targetEl.style.width = `${clampedSize}px`;
    } else {
      targetEl.style.height = `${clampedSize}px`;
    }
  }

  // Watch handle ref to setup/teardown draggable
  watch(handleRef, setupDraggable, {immediate: true});

  // Re-setup when enabled changes
  if (enabled !== undefined) {
    watch(() => toValue(enabled), setupDraggable);
  }

  // Cleanup on unmount
  onUnmounted(() => {
    if (cleanup) {
      cleanup();
      cleanup = null;
    }
  });

  return {
    setHandleRef,
    size,
    isResizing,
    reset,
    setSize,
  };
}
