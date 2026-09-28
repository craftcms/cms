import {reactive, ref} from 'vue';
import {
  draggable,
  dropTargetForElements,
  monitorForElements,
} from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import type {ElementDragPayload} from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import {
  attachClosestEdge,
  type Edge,
  extractClosestEdge,
} from '@atlaskit/pragmatic-drag-and-drop-hitbox/closest-edge';
import {preserveOffsetOnSource} from '@atlaskit/pragmatic-drag-and-drop/element/preserve-offset-on-source';
import {setCustomNativeDragPreview} from '@atlaskit/pragmatic-drag-and-drop/element/set-custom-native-drag-preview';
import {getReorderDestinationIndex} from '@atlaskit/pragmatic-drag-and-drop-hitbox/util/get-reorder-destination-index';
import {combine} from '@atlaskit/pragmatic-drag-and-drop/combine';

export type Axis = 'vertical' | 'horizontal';

// States for the item being dragged
export type DragState =
  | {type: 'idle'}
  | {type: 'is-dragging'}
  | {type: 'is-dragging-and-left-self'};

// States for items being dragged over
export type DropState =
  | {type: 'idle'}
  | {type: 'is-over'; closestEdge: Edge; draggingRect: DOMRect};

/** An item being dragged, as another list sees it. */
export interface DraggedItem {
  /** The dragging list's `type`, if it set one. */
  type?: string;
  id: string | number;
}

export interface UseDragAndDropOptions {
  onReorder: (startIndex: number, finishIndex: number) => void;
  axis?: Axis;
  allowedEdges?: Edge[];
  /** Tags this list's drags, so another list's `dropInto` can tell them apart. */
  type?: string;
  /**
   * Lets this list's items take a drag from another list dropped onto them,
   * rather than between them — a source dropped on a page, say. The list's own
   * items still only reorder.
   */
  dropInto?: {
    accepts: (dragged: DraggedItem, targetId: string | number) => boolean;
    onDrop: (targetId: string | number, dragged: DraggedItem) => void;
  };
}

export interface UseDragAndDropReturn {
  registerItem: (
    element: HTMLElement,
    handleElement: HTMLElement | null,
    id: string | number,
    index: number
  ) => () => void;
  getDragState: (id: string | number) => DragState;
  getDropState: (id: string | number) => DropState;
  /**
   * The gap the dragged item would land in, counted from 0 (before the first
   * item) to the item count (after the last), or null when there's nowhere
   * new to put it.
   *
   * One answer for the whole list, where `getDropState()` answers per item:
   * the bottom of B and the top of C are the same gap, so a list that draws
   * its line from this can't draw it twice.
   */
  getDropIndex: () => number | null;
  /** The item another list's drag would be dropped onto, if any (`dropInto`). */
  getDropIntoId: () => string | number | null;
  setupMonitor: () => () => void;
}

const idleDragState: DragState = {type: 'idle'};
const idleDropState: DropState = {type: 'idle'};

export function useDragAndDrop(
  options: UseDragAndDropOptions
): UseDragAndDropReturn {
  const instanceId = Symbol('drag-instance');
  const itemDataKey = 'craftDragItem';

  const axis = options.axis ?? 'vertical';
  const allowedEdges: Edge[] =
    options.allowedEdges ??
    (axis === 'vertical' ? ['top', 'bottom'] : ['left', 'right']);

  type ItemData = {
    craftDragItem: true;
    id: string | number;
    index: number;
    instanceId: symbol;
    type?: string;
    rect: DOMRect;
  };

  function isItemData(data: ElementDragPayload['data']): data is ItemData {
    return data[itemDataKey] === true;
  }

  function getItemData(
    id: string | number,
    index: number,
    rect: DOMRect
  ): ItemData {
    return {
      [itemDataKey]: true,
      id,
      index,
      instanceId,
      type: options.type,
      rect,
    };
  }

  /** A drag from this list, as opposed to one passing over from another. */
  function isOwnItem(data: ElementDragPayload['data']): data is ItemData {
    return isItemData(data) && data.instanceId === instanceId;
  }

  /** Whether another list's drag can be dropped onto the item `id`. */
  function acceptsInto(
    data: ElementDragPayload['data'],
    id: string | number
  ): data is ItemData {
    return (
      !!options.dropInto &&
      isItemData(data) &&
      data.instanceId !== instanceId &&
      options.dropInto.accepts({type: data.type, id: data.id}, id)
    );
  }

  const dropIntoId = ref<string | number | null>(null);

  function getDropIntoId(): string | number | null {
    return dropIntoId.value;
  }

  // Use reactive objects for state - keys are stringified IDs
  const dragStates = reactive<Record<string, DragState>>({});
  const dropStates = reactive<Record<string, DropState>>({});

  const dropIndex = ref<number | null>(null);

  function getDropIndex(): number | null {
    return dropIndex.value;
  }

  /** The gap under the pointer, from the innermost drop target. */
  function updateDropIndex(
    source: ElementDragPayload,
    target: {data: Record<string | symbol, unknown>} | undefined
  ) {
    const sourceData = source.data;
    const targetData = target?.data;

    // Over the dragged item itself it would stay where it is, which the item's
    // own placeholder already shows. Over another list — dropping onto one of
    // its items — it isn't going anywhere in this one.
    if (
      !targetData ||
      !isItemData(sourceData) ||
      !isOwnItem(targetData) ||
      targetData.id === sourceData.id
    ) {
      dropIndex.value = null;
      return;
    }

    const edge = extractClosestEdge(targetData);

    dropIndex.value =
      edge === 'bottom' || edge === 'right'
        ? targetData.index + 1
        : targetData.index;
  }

  function setDragState(id: string | number, state: DragState) {
    dragStates[String(id)] = state;
  }

  function setDropState(id: string | number, state: DropState) {
    dropStates[String(id)] = state;
  }

  function getDragState(id: string | number): DragState {
    return dragStates[String(id)] ?? idleDragState;
  }

  function getDropState(id: string | number): DropState {
    return dropStates[String(id)] ?? idleDropState;
  }

  function registerItem(
    element: HTMLElement,
    handleElement: HTMLElement | null,
    id: string | number,
    index: number
  ): () => void {
    const dragElement = handleElement ?? element;

    // Initialize state for this item so Vue can track it
    const key = String(id);
    if (!(key in dragStates)) {
      dragStates[key] = idleDragState;
    }
    if (!(key in dropStates)) {
      dropStates[key] = idleDropState;
    }

    return combine(
      draggable({
        element: dragElement,
        getInitialData: () =>
          getItemData(id, index, element.getBoundingClientRect()),
        onGenerateDragPreview({nativeSetDragImage, location}) {
          const rect = element.getBoundingClientRect();

          setCustomNativeDragPreview({
            getOffset: preserveOffsetOnSource({
              element,
              input: location.current.input,
            }),
            render({container}) {
              // Clone the element for the drag preview (must be synchronous)
              const preview = element.cloneNode(true);
              if (!(preview instanceof HTMLElement)) {
                throw new Error('Expected an HTML drag preview.');
              }
              preview.style.width = `${rect.width}px`;
              preview.style.height = `${rect.height}px`;
              container.appendChild(preview);

              return () => preview.remove();
            },
            nativeSetDragImage,
          });
        },
        onDragStart() {
          setDragState(id, {type: 'is-dragging'});
        },
        onDrop() {
          setDragState(id, idleDragState);

          // Native HTML drag swallows the trailing pointerup on the source, so
          // an interactive drag handle (e.g. a button) can stay stuck in
          // :hover. Briefly disabling pointer events forces the browser to drop
          // it. (`craft-button`'s own pressed state is released on `dragend`.)
          dragElement.style.pointerEvents = 'none';
          requestAnimationFrame(() => {
            dragElement.style.pointerEvents = '';
          });
        },
      }),
      dropTargetForElements({
        element,
        // Sticky keeps the line where it was while the pointer crosses the gap
        // between rows. A drop onto an item mustn't outlast the pointer leaving
        // it, or letting go anywhere would still drop it there.
        getIsSticky: ({source}) => isOwnItem(source.data),
        canDrop({source}) {
          return isOwnItem(source.data) || acceptsInto(source.data, id);
        },
        getData({input}) {
          return attachClosestEdge(
            getItemData(id, index, element.getBoundingClientRect()),
            {
              element,
              input,
              allowedEdges,
            }
          );
        },
        onDragEnter({source, self}) {
          if (!isOwnItem(source.data)) {
            dropIntoId.value = id;
            return;
          }

          // Ignore if dragging over self
          if (source.data.id === id) return;

          const closestEdge = extractClosestEdge(self.data);
          if (!closestEdge) return;

          setDropState(id, {
            type: 'is-over',
            closestEdge,
            draggingRect: source.data.rect,
          });
        },
        onDrag({source, self}) {
          if (!isOwnItem(source.data)) return;

          // Ignore if dragging over self
          if (source.data.id === id) return;

          const closestEdge = extractClosestEdge(self.data);
          if (!closestEdge) return;

          // Only update if changed (optimization)
          const current = getDropState(id);
          if (
            current.type === 'is-over' &&
            current.closestEdge === closestEdge
          ) {
            return;
          }

          setDropState(id, {
            type: 'is-over',
            closestEdge,
            draggingRect: source.data.rect,
          });
        },
        onDragLeave({source}) {
          if (!isOwnItem(source.data)) {
            if (dropIntoId.value === id) dropIntoId.value = null;
            return;
          }

          // If the dragged item is leaving itself, update its drag state
          if (source.data.id === id) {
            setDragState(id, {type: 'is-dragging-and-left-self'});
            return;
          }

          // Otherwise, clear this item's drop state
          setDropState(id, idleDropState);
        },
        onDrop({source, location}) {
          setDropState(id, idleDropState);

          if (isOwnItem(source.data) || !isItemData(source.data)) return;

          dropIntoId.value = null;

          // Only the innermost target takes the drop.
          if (location.current.dropTargets[0]?.element === element) {
            options.dropInto?.onDrop(id, {
              type: source.data.type,
              id: source.data.id,
            });
          }
        },
      })
    );
  }

  function setupMonitor(): () => void {
    return monitorForElements({
      canMonitor({source}) {
        return isItemData(source.data) && source.data.instanceId === instanceId;
      },
      onDropTargetChange({location, source}) {
        updateDropIndex(source, location.current.dropTargets[0]);
      },
      onDrag({location, source}) {
        // Moving within a target changes its closest edge without changing
        // the target.
        updateDropIndex(source, location.current.dropTargets[0]);
      },
      onDrop({location, source}) {
        dropIndex.value = null;

        const target = location.current.dropTargets[0];
        if (!target) return;

        const sourceData = source.data;
        const targetData = target.data;

        // Dropped onto another list's item: that list handles it (`dropInto`).
        if (!isItemData(sourceData) || !isOwnItem(targetData)) return;

        const startIndex = sourceData.index;
        const indexOfTarget = targetData.index;
        const closestEdgeOfTarget = extractClosestEdge(targetData);

        const finishIndex = getReorderDestinationIndex({
          startIndex,
          closestEdgeOfTarget,
          indexOfTarget,
          axis,
        });

        if (finishIndex !== startIndex) {
          options.onReorder(startIndex, finishIndex);
        }
      },
    });
  }

  return {
    registerItem,
    getDragState,
    getDropState,
    getDropIndex,
    getDropIntoId,
    setupMonitor,
  };
}
