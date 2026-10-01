import {computed} from 'vue';
import {actionClient, t} from '@craftcms/ui';
import {getElementLevelDelta, moveElement} from '@actions/StructuresController';
import {useMessages} from '@/modules/messages/useMessages';
import type {ElementIndexModel} from '../types/model';
import {
  loadedBranchDepth,
  resolveStructureMove,
  type StructureMove,
  type StructurePlacement,
} from './useElementIndexStructure';

export function useElementStructureMoves(index: ElementIndexModel) {
  const {elementIndex, structureView, toggleStructure} = index.view;
  const tableData = index.view.data;
  const messages = useMessages();

  function hasActiveFilter(): boolean {
    const rules = elementIndex.currentCondition?.conditionRules;
    return Array.isArray(rules)
      ? rules.length > 0
      : (rules?.rules.length ?? 0) > 0;
  }

  /**
   * Whether structure rows can be reordered. Mirrors Craft 5's `canSort`: a
   * move only makes sense against the full, canonical tree, so searching,
   * filtering, and draft/trash listings all rule it out.
   */
  const canReorderStructure = computed(
    () =>
      elementIndex.source?.structureId != null &&
      index.view.mode.value === 'structure' &&
      !!elementIndex.structure?.editable &&
      !elementIndex.search &&
      !elementIndex.status &&
      !hasActiveFilter() &&
      !elementIndex.drafts &&
      !elementIndex.trashed
  );

  function placementFor(
    id: string | number,
    move: StructureMove
  ): StructurePlacement | null {
    return resolveStructureMove(tableData.value, id, move, {
      // Off-page roots may precede the first row, so only page 1 can
      // place a row at the very start of the tree.
      startsAtTop: (elementIndex.pagination?.current_page ?? 1) === 1,
    });
  }

  /**
   * Whether the placement would push the row's branch past `maxLevels`, or
   * `null` when the branch is collapsed and its depth isn't loaded.
   */
  function exceedsMaxLevels(
    id: string | number,
    placement: StructurePlacement
  ): boolean | null {
    const maxLevels = elementIndex.structure?.maxLevels;
    if (!maxLevels) {
      return false;
    }

    const depth = loadedBranchDepth(
      tableData.value,
      id,
      structureView.isCollapsed
    );
    return depth === null ? null : placement.level + depth > maxLevels;
  }

  function canMoveRow(id: string | number, move: StructureMove): boolean {
    if (!canReorderStructure.value) {
      return false;
    }

    const placement = placementFor(id, move);
    return placement !== null && exceedsMaxLevels(id, placement) !== true;
  }

  /**
   * Saves a structure move, then quietly refreshes the list (opening the new
   * parent if it was collapsed, so the moved row stays in view).
   */
  async function moveStructureRow(id: string | number, move: StructureMove) {
    const structure = elementIndex.structure;
    const placement = canReorderStructure.value ? placementFor(id, move) : null;
    const row = tableData.value.find((r) => String(r.id) === String(id));

    if (!structure || !placement || !row) {
      return;
    }

    // `row.id` rather than `id`: the drag layer keys its DOM maps by string,
    // and the server's `integer` validation accepts a numeric string without
    // casting it, so forwarding that key hands a string to an int parameter.
    const base = {
      structureId: structure.id,
      elementId: row.id,
      siteId: row.siteId,
    };

    try {
      let exceeds = exceedsMaxLevels(id, placement);

      if (exceeds === null) {
        const {data} = await actionClient.post(
          getElementLevelDelta.url(),
          base
        );
        exceeds = placement.level + Number(data.delta) > structure.maxLevels!;
      }

      if (exceeds) {
        messages.error(
          t('This structure only allows {max} levels.', {
            max: structure.maxLevels,
          })
        );
        return;
      }

      await actionClient.post(moveElement.url(), {
        ...base,
        prevId: placement.prevId,
        parentId: placement.parentId,
      });
    } catch (error: any) {
      messages.error(
        error?.response?.data?.message ?? t('Couldn’t save the new position.')
      );
      return;
    }

    messages.success(t('New position saved.'));

    if (
      placement.newParentId !== undefined &&
      structureView.isCollapsed(placement.newParentId)
    ) {
      toggleStructure(placement.newParentId);
    } else {
      void index.refresh();
    }
  }

  return {
    get reorderable() {
      return canReorderStructure.value;
    },
    canMoveRow,
    moveRow: moveStructureRow,
  };
}
