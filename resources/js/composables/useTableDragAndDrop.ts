import {onMounted, onUnmounted, shallowRef, triggerRef} from 'vue';
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
import {pointerOutsideOfPreview} from '@atlaskit/pragmatic-drag-and-drop/element/pointer-outside-of-preview';
import {setCustomNativeDragPreview} from '@atlaskit/pragmatic-drag-and-drop/element/set-custom-native-drag-preview';
import {getReorderDestinationIndex} from '@atlaskit/pragmatic-drag-and-drop-hitbox/util/get-reorder-destination-index';
import {combine} from '@atlaskit/pragmatic-drag-and-drop/combine';

const rowDataKey = Symbol('table-row');

export type RowData = {
  [rowDataKey]: true;
  id: string | number;
  index: number;
  instanceId: symbol;
};

function getRowData(
  id: string | number,
  index: number,
  instanceId: symbol
): RowData {
  return {
    [rowDataKey]: true,
    id,
    index,
    instanceId,
  };
}

function isRowData(data: Record<string | symbol, unknown>): data is RowData {
  return data[rowDataKey] === true;
}

export type DragState =
  | {type: 'idle'}
  | {type: 'selected'}
  | {type: 'dragging'; container: HTMLElement};
export type DropState = {edge: Edge | null};

export function useTableDragAndDrop(options: {
  onReorder: (startIndex: number, finishIndex: number) => void;
  getRowId: (row: any) => string | number;
}) {
  const instanceId = Symbol('table-drag-instance');

  // Use shallowRef + triggerRef for Map reactivity
  const dragState = shallowRef<Map<string | number, DragState>>(new Map());
  const dropState = shallowRef<Map<string | number, DropState>>(new Map());
  const cleanupFns: Array<() => void> = [];

  function setDragState(id: string | number, state: DragState) {
    dragState.value.set(id, state);
    triggerRef(dragState);
  }

  function setDropState(id: string | number, state: DropState) {
    dropState.value.set(id, state);
    triggerRef(dropState);
  }

  function getDragState(id: string | number): DragState {
    return dragState.value.get(id) ?? {type: 'idle'};
  }

  function getDropState(id: string | number): DropState {
    return dropState.value.get(id) ?? {edge: null};
  }

  function registerRow(
    element: HTMLElement,
    handleElement: HTMLElement | null,
    id: string | number,
    index: number
  ) {
    const dragElement = handleElement ?? element;

    const cleanup = combine(
      draggable({
        element: dragElement,
        getInitialData: () => getRowData(id, index, instanceId),
        onGenerateDragPreview({nativeSetDragImage}) {
          setCustomNativeDragPreview({
            getOffset: pointerOutsideOfPreview({
              x: 'var(--c-spacing)',
              y: 'var(--c-spacing)',
            }),
            render({container}) {
              setDragState(id, {type: 'dragging', container});
              return () => setDragState(id, {type: 'idle'});
            },
            nativeSetDragImage,
          });
        },
        onDragStart() {
          setDragState(id, {type: 'selected'});
        },
        onDrop() {
          setDragState(id, {type: 'idle'});
        },
      }),
      dropTargetForElements({
        element,
        canDrop({source}) {
          return (
            isRowData(source.data) && source.data.instanceId === instanceId
          );
        },
        getData({input}) {
          return attachClosestEdge(getRowData(id, index, instanceId), {
            element,
            input,
            allowedEdges: ['top', 'bottom'],
          });
        },
        onDragEnter({source, self}) {
          updateDropState(source, self, id, index);
        },
        onDrag({source, self}) {
          updateDropState(source, self, id, index);
        },
        onDragLeave() {
          setDropState(id, {edge: null});
        },
        onDrop() {
          setDropState(id, {edge: null});
        },
      })
    );

    cleanupFns.push(cleanup);
    return cleanup;
  }

  function updateDropState(
    source: any,
    self: any,
    id: string | number,
    index: number
  ) {
    if (!isRowData(source.data)) return;

    const sourceIndex = source.data.index;
    if (sourceIndex === index) {
      setDropState(id, {edge: null});
      return;
    }
    const edge = extractClosestEdge(self.data);
    const isItemBeforeSource = index === sourceIndex - 1;
    const isItemAfterSource = index === sourceIndex + 1;

    const isDropIndicatorHidden =
      (isItemBeforeSource && edge === 'bottom') ||
      (isItemAfterSource && edge === 'top');

    setDropState(id, {edge: isDropIndicatorHidden ? null : edge});
  }

  onMounted(() => {
    const monitorCleanup = monitorForElements({
      canMonitor({source}) {
        return isRowData(source.data) && source.data.instanceId === instanceId;
      },
      onDrop({location, source}) {
        const target = location.current.dropTargets[0];
        if (!target) return;

        const sourceData = source.data;
        const targetData = target.data;

        if (!isRowData(sourceData) || !isRowData(targetData)) return;

        const startIndex = sourceData.index;
        const indexOfTarget = targetData.index;
        const closestEdgeOfTarget = extractClosestEdge(targetData);

        const finishIndex = getReorderDestinationIndex({
          startIndex,
          closestEdgeOfTarget,
          indexOfTarget,
          axis: 'vertical',
        });

        if (finishIndex !== startIndex) {
          options.onReorder(startIndex, finishIndex);
        }
      },
    });

    cleanupFns.push(monitorCleanup);
  });

  onUnmounted(() => {
    cleanupFns.forEach((fn) => fn());
  });

  return {
    registerRow,
    getDragState,
    getDropState,
    instanceId,
  };
}
