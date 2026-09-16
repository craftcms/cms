import {nextTick, onMounted, onUnmounted, reactive, watch} from 'vue';
import {
  draggable,
  dropTargetForElements,
  monitorForElements,
} from '@atlaskit/pragmatic-drag-and-drop/element/adapter';
import {combine} from '@atlaskit/pragmatic-drag-and-drop/combine';
import {preserveOffsetOnSource} from '@atlaskit/pragmatic-drag-and-drop/element/preserve-offset-on-source';
import {setCustomNativeDragPreview} from '@atlaskit/pragmatic-drag-and-drop/element/set-custom-native-drag-preview';
import {
  attachInstruction,
  extractInstruction,
  type Instruction,
  type ItemMode,
} from '@atlaskit/pragmatic-drag-and-drop-hitbox/tree-item';
import {
  descendantIndexes,
  type StructureMove,
  type StructureRow,
} from '@/modules/elements/composables/useElementIndexStructure';

/** Matches the per-level indentation the table renders. */
export const STRUCTURE_INDENT = 44;

export type StructureDropType = Exclude<
  Instruction['type'],
  'instruction-blocked'
>;

export interface UseStructureDragOptions {
  /** The rendered rows, in tree order. */
  getRows: () => ReadonlyArray<StructureRow>;
  enabled: () => boolean;
  onMove: (sourceId: string | number, move: StructureMove) => void;
  /** Drop types to refuse for a source over a target (e.g. past `maxLevels`). */
  blockedDrops?: (
    sourceId: string | number,
    targetId: string | number
  ) => Array<StructureDropType>;
}

const itemKey = 'craftStructureItem';

/**
 * Tree drag-and-drop for structure rows, on the tree-item hitbox: dropping on
 * a row's upper or lower edge places the dragged branch before or after it,
 * its middle makes it the first child, and dragging left past the last row of
 * a group moves it out to an ancestor's level.
 *
 * Levels are measured from each row's title wrapper (where indentation
 * starts), not the row, which also holds the handle/toggle/checkbox columns.
 */
export function useStructureDrag(options: UseStructureDragOptions) {
  const instanceId = Symbol('structure-drag');
  const rowRefs = new Map<string, HTMLElement>();
  const handleRefs = new Map<string, HTMLElement>();
  const cleanups = new Map<string, () => void>();
  let monitorCleanup: (() => void) | null = null;

  const state = reactive({
    draggingId: null as string | null,
    instructions: {} as Record<string, Instruction | null>,
  });

  const levelOf = (row: StructureRow) => row.level ?? 1;

  function rowIndex(id: string | number): number {
    return options.getRows().findIndex((row) => String(row.id) === String(id));
  }

  function modeFor(index: number): ItemMode {
    const rows = options.getRows();
    const row = rows[index]!;
    const next = rows[index + 1];

    if (next && levelOf(next) > levelOf(row)) {
      return 'expanded';
    }

    return !next || levelOf(next) < levelOf(row) ? 'last-in-group' : 'standard';
  }

  /** Whether `targetId` is the dragged row or somewhere in its branch. */
  function isInBranch(sourceId: string, targetId: string): boolean {
    const rows = options.getRows();
    const index = rowIndex(sourceId);

    return (
      index !== -1 &&
      [index, ...descendantIndexes(rows, index)].some(
        (i) => String(rows[i]!.id) === targetId
      )
    );
  }

  function toMove(
    instruction: Instruction,
    targetId: string
  ): StructureMove | null {
    switch (instruction.type) {
      case 'reorder-above':
        return {type: 'before', targetId};
      case 'reorder-below':
        return {type: 'after', targetId};
      case 'make-child':
        return {type: 'child', targetId};
      case 'reparent':
        return {
          type: 'reparent',
          targetId,
          level: instruction.desiredLevel + 1,
        };
      default:
        return null;
    }
  }

  function register(id: string, index: number): () => void {
    const rowEl = rowRefs.get(id)!;
    const dragEl = handleRefs.get(id) ?? rowEl;

    return combine(
      draggable({
        element: dragEl,
        getInitialData: () => ({[itemKey]: true, id, instanceId}),
        onGenerateDragPreview({nativeSetDragImage, location}) {
          const rect = rowEl.getBoundingClientRect();
          setCustomNativeDragPreview({
            getOffset: preserveOffsetOnSource({
              element: rowEl,
              input: location.current.input,
            }),
            render({container}) {
              const preview = rowEl.cloneNode(true) as HTMLElement;
              preview.style.width = `${rect.width}px`;
              preview.style.height = `${rect.height}px`;
              container.appendChild(preview);
              return () => preview.remove();
            },
            nativeSetDragImage,
          });
        },
        onDragStart: () => {
          state.draggingId = id;
        },
        onDrop: () => {
          state.draggingId = null;
        },
      }),
      dropTargetForElements({
        element: rowEl,
        getIsSticky: () => true,
        canDrop: ({source}) =>
          source.data[itemKey] === true &&
          source.data.instanceId === instanceId &&
          !isInBranch(String(source.data.id), id),
        getData: ({input, source}) => {
          const row = options.getRows()[index]!;
          return attachInstruction(
            {[itemKey]: true, id},
            {
              element: rowEl.querySelector('.cp-table-structure') ?? rowEl,
              input,
              currentLevel: levelOf(row) - 1,
              indentPerLevel: STRUCTURE_INDENT,
              mode: modeFor(index),
              block: options.blockedDrops?.(String(source.data.id), id),
            }
          );
        },
        onDrag: ({self}) => {
          state.instructions[id] = extractInstruction(self.data);
        },
        onDragLeave: () => {
          state.instructions[id] = null;
        },
        onDrop: () => {
          state.instructions[id] = null;
        },
      })
    );
  }

  function refresh() {
    cleanups.forEach((cleanup) => cleanup());
    cleanups.clear();

    if (!options.enabled()) {
      return;
    }

    options.getRows().forEach((row, index) => {
      const id = String(row.id);
      if (rowRefs.has(id)) {
        cleanups.set(id, register(id, index));
      }
    });
  }

  function setRowRef(el: Element | null, id: string | number) {
    if (el instanceof HTMLElement) {
      rowRefs.set(String(id), el);
    } else {
      rowRefs.delete(String(id));
    }
  }

  function setHandleRef(el: Element | null, id: string | number) {
    if (el instanceof HTMLElement) {
      handleRefs.set(String(id), el);
    } else {
      handleRefs.delete(String(id));
    }
  }

  /** The drop instruction currently showing on a row, if any. */
  function instructionFor(id: string | number): Instruction | null {
    const instruction = state.instructions[String(id)] ?? null;
    return instruction?.type === 'instruction-blocked' ? null : instruction;
  }

  function isDragging(id: string | number): boolean {
    return state.draggingId === String(id);
  }

  watch(
    () => [
      options.enabled(),
      options.getRows().map((row) => `${row.id}:${levelOf(row)}`),
    ],
    () => nextTick(refresh),
    {deep: true}
  );

  onMounted(() => {
    monitorCleanup = monitorForElements({
      canMonitor: ({source}) =>
        source.data[itemKey] === true && source.data.instanceId === instanceId,
      onDrop: ({location, source}) => {
        const target = location.current.dropTargets[0];
        const instruction = target ? extractInstruction(target.data) : null;
        const targetId = target?.data.id;

        if (!instruction || typeof targetId !== 'string') {
          return;
        }

        const move = toMove(instruction, targetId);
        if (move && targetId !== source.data.id) {
          options.onMove(String(source.data.id), move);
        }
      },
    });
    void nextTick(refresh);
  });

  onUnmounted(() => {
    cleanups.forEach((cleanup) => cleanup());
    monitorCleanup?.();
  });

  return {setRowRef, setHandleRef, instructionFor, isDragging};
}
