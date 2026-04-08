import {reactive} from 'vue';
import {
  draggable,
  dropTargetForElements,
  monitorForElements,
} from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
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

export interface UseDragAndDropOptions {
  onReorder: (startIndex: number, finishIndex: number) => void;
  axis?: Axis;
  allowedEdges?: Edge[];
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
  setupMonitor: () => () => void;
}

const idleDragState: DragState = {type: 'idle'};
const idleDropState: DropState = {type: 'idle'};

export function useDragAndDrop(
  options: UseDragAndDropOptions
): UseDragAndDropReturn {
  const instanceId = Symbol('drag-instance');
  const itemDataKey = Symbol('drag-item');

  const axis = options.axis ?? 'vertical';
  const allowedEdges: Edge[] =
    options.allowedEdges ??
    (axis === 'vertical' ? ['top', 'bottom'] : ['left', 'right']);

  type ItemData = {
    [key: symbol]: true;
    id: string | number;
    index: number;
    instanceId: symbol;
    rect: DOMRect;
  };

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
      rect,
    };
  }

  function isItemData(
    data: Record<string | symbol, unknown>
  ): data is ItemData {
    return data[itemDataKey] === true;
  }

  // Use reactive objects for state - keys are stringified IDs
  const dragStates = reactive<Record<string, DragState>>({});
  const dropStates = reactive<Record<string, DropState>>({});

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
              const preview = element.cloneNode(true) as HTMLElement;
              preview.style.width = `${rect.width}px`;
              preview.style.height = `${rect.height}px`;
              preview.style.transform = 'rotate(2deg)';
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
        },
      }),
      dropTargetForElements({
        element,
        getIsSticky: () => true,
        canDrop({source}) {
          return (
            isItemData(source.data) && source.data.instanceId === instanceId
          );
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
          if (!isItemData(source.data)) return;

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
          if (!isItemData(source.data)) return;

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
          if (!isItemData(source.data)) return;

          // If the dragged item is leaving itself, update its drag state
          if (source.data.id === id) {
            setDragState(id, {type: 'is-dragging-and-left-self'});
            return;
          }

          // Otherwise, clear this item's drop state
          setDropState(id, idleDropState);
        },
        onDrop() {
          setDropState(id, idleDropState);
        },
      })
    );
  }

  function setupMonitor(): () => void {
    return monitorForElements({
      canMonitor({source}) {
        return isItemData(source.data) && source.data.instanceId === instanceId;
      },
      onDrop({location, source}) {
        const target = location.current.dropTargets[0];
        if (!target) return;

        const sourceData = source.data;
        const targetData = target.data;

        if (!isItemData(sourceData) || !isItemData(targetData)) return;

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
    setupMonitor,
  };
}
