import {router} from '@inertiajs/vue3';
import {actionClient, t} from '@craftcms/ui';
import {getElementLevelDelta, moveElement} from '@actions/StructuresController';
import {useFlashMessages} from '@/common/composables/useFlashMessages';
import {type RowSelectionState} from '@tanstack/vue-table';
import {useCraftTable} from '@/modules/admin-table/craftTable';
import {computed, onMounted, onScopeDispose, ref, shallowRef, watch} from 'vue';
import {
  type ElementIndexRow,
  useContentIndexData,
} from '@/modules/elements/composables/useContentIndexData';
import {useElementIndexTable} from '@/modules/elements/composables/useElementIndexTable';
import type {ConditionConfig} from '@/modules/conditions/types';
import {useElementIndexColumns} from '@/modules/elements/composables/useElementIndexColumns';
import {useElementIndexFilters} from '@/modules/elements/composables/useElementIndexFilters';
import {useElementIndexLoading} from '@/modules/elements/composables/useElementIndexLoading';
import {useElementIndexPagination} from '@/modules/elements/composables/useElementIndexPagination';
import {useElementIndexSort} from '@/modules/elements/composables/useElementIndexSort';
import {
  cascadeStructureSelection,
  deselectDescendants,
  loadedBranchDepth,
  resolveStructureMove,
  selectDescendantsOfSelected,
  type StructureMove,
  type StructurePlacement,
  useElementIndexStructure,
} from '@/modules/elements/composables/useElementIndexStructure';
import {useElementIndexViewMode} from '@/modules/elements/composables/useElementIndexViewMode';
import {useElementIndexViewState} from '@/modules/elements/composables/useElementIndexViewState';
import {
  createIndexVisitor,
  type ElementIndexRoute,
  type IndexQueryParams,
  type IndexRestore,
} from '@/modules/elements/composables/useElementIndexVisits';
import type {ViewMode} from '@/modules/elements/types/view-state';

interface UseElementIndexPageOptions {
  /** The page's index route — the one per-page piece of the pipeline. */
  route: ElementIndexRoute;
  /**
   * The always-first, non-toggleable column. Defaults to the element's
   * `title` labeled with the payload's `elementDisplayName`.
   */
  pinnedColumn?: {key: string; label: string};
  /** Additional type-specific params included with filter submissions. */
  filterParams?: () => IndexQueryParams;
}

/**
 * The full element index page pipeline: the shared
 * {@link useContentIndexData} payload wired through view state, filters,
 * columns, sorting, pagination, and view mode into a ready TanStack table,
 * plus the page-level behaviors every index repeats (selection keyed by
 * element id, the post-bulk-action partial reload, structure-only view-mode
 * filtering, and the Customize Sources modal shim).
 *
 * Pages supply only their route (and optional pinned-column override);
 * type-specific chrome stays in the page.
 */
export function useElementIndexPage(options: UseElementIndexPageOptions) {
  const elementIndex = useContentIndexData();

  const viewState = useElementIndexViewState(elementIndex);
  const conditions = shallowRef<ConditionConfig | null>(
    elementIndex.currentCondition ?? null
  );
  const filters = useElementIndexFilters(
    elementIndex,
    viewState,
    options.route,
    conditions,
    options.filterParams
  );
  const {
    columns,
    columnOrder,
    columnOptions,
    reorder,
    tableColumns,
    restore: restoreColumns,
  } = useElementIndexColumns(
    elementIndex,
    viewState,
    options.pinnedColumn ?? {
      key: 'title',
      label: elementIndex.elementDisplayName,
    },
    options.route
  );
  const {
    sortingState,
    sortingConfig,
    sortField,
    sortDirection,
    restore: restoreSort,
  } = useElementIndexSort(elementIndex, viewState, {route: options.route});
  const {paginationState, paginationConfig} = useElementIndexPagination(
    elementIndex,
    options.route
  );
  // The structure view mode only applies to structure sources, so hide it
  // (and any other `structuresOnly` mode) unless the active source is one.
  const visibleViewModes = computed(() =>
    elementIndex.viewModes.filter(
      (viewMode) =>
        !viewMode.structuresOnly || elementIndex.source?.structureId != null
    )
  );

  const {mode: savedMode, restore: restoreViewMode} = useElementIndexViewMode(
    options.route,
    viewState
  );

  // The saved mode is shared by every source, so fall back to the first mode
  // the active source offers (as the legacy index did) while keeping the saved
  // one for sources that support it.
  const mode = computed<ViewMode['mode']>({
    get: () =>
      visibleViewModes.value.some(({mode}) => mode === savedMode.value)
        ? savedMode.value
        : (visibleViewModes.value[0]?.mode ?? 'table'),
    set: (value) => {
      savedMode.value = value;
    },
  });
  const structureView = useElementIndexStructure(
    elementIndex,
    viewState,
    options.route
  );
  const {loading} = useElementIndexLoading();

  // The server renders the initial page for its defaults — it can't see the
  // client's persisted view state — so view mode, sort, and columns may each
  // need re-requesting on load. Compose every contribution into a SINGLE visit:
  // fired separately they'd each go through Inertia's interruptible sync request
  // stream and abort one another, dropping whichever params lost the race (e.g.
  // the view mode, leaving cards rendered against table-shaped data).
  const restoreVisitor = createIndexVisitor(options.route);

  onMounted(() => {
    const restores = [
      restoreColumns(),
      restoreSort(),
      restoreViewMode(),
      structureView.restore(),
    ].filter((restore): restore is IndexRestore => restore !== null);

    if (restores.length === 0) {
      return;
    }

    restoreVisitor.merge(
      Object.assign({}, ...restores.map((restore) => restore.params)),
      {
        only: [...new Set(restores.flatMap((restore) => restore.only))],
        replace: true,
      }
    );
  });

  const visibleColumns = ref({});

  // Selection is keyed by element id (see `getRowId`), so it survives sorting
  // and pagination. Read the current selection from `rowSelection` (a map of
  // element id → selected) or via `elementTable.getSelectedRowModel()`.
  const rowSelection = ref<RowSelectionState>({});

  /**
   * Re-pull the server-rendered list and counts, the same way the
   * view-mode/filter composables do — a partial Inertia reload of just the
   * index props.
   *
   * Everything else stays put: the shell, the sources sidebar, scroll position,
   * and the selection (`rowSelection` lives out here, keyed by element id).
   */
  function refreshResults() {
    router.reload({
      only: ['data', 'pagination', 'badgeCounts'],
    });
  }

  // After a bulk action succeeds, refresh and clear any lingering selection —
  // the rows it applied to may not even be in the list any more. The table also
  // clears its own selection optimistically when the action fires.
  function onActionPerformed() {
    rowSelection.value = {};
    refreshResults();
  }

  // Structure mode hides collapsed branches straight away, ahead of the
  // server's response.
  const tableData = computed(() =>
    structureView.visibleRows(elementIndex.data ?? [])
  );

  // A selected parent stands for its whole branch, so children that arrive
  // later (an expand, a reload) join the selection too.
  watch(tableData, (rows) => {
    if (structureView.isStructure.value) {
      rowSelection.value = selectDescendantsOfSelected(
        rowSelection.value,
        rows
      );
    }
  });

  /**
   * Expands or collapses a structure row. Collapsing releases the rows it
   * hides from the selection, so a bulk action never reaches rows the user
   * can no longer see.
   */
  function toggleStructure(id: string | number) {
    if (!structureView.isCollapsed(id)) {
      rowSelection.value = deselectDescendants(
        rowSelection.value,
        tableData.value,
        id
      );
    }

    structureView.toggle(id);
  }

  const {flash} = useFlashMessages();

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
      structureView.isStructure.value &&
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
        flash(
          'error',
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
      flash(
        'error',
        error?.response?.data?.message ?? t('Couldn’t save the new position.')
      );
      return;
    }

    flash('success', t('New position saved.'));

    if (
      placement.newParentId !== undefined &&
      structureView.isCollapsed(placement.newParentId)
    ) {
      toggleStructure(placement.newParentId);
    } else {
      router.reload({only: ['data', 'pagination'], showProgress: false});
    }
  }

  const elementTable = useCraftTable<ElementIndexRow>({
    get data() {
      return tableData.value;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get columnOrder() {
        return columnOrder.value;
      },
      get columnVisibility() {
        return visibleColumns.value;
      },
      get sorting() {
        return sortingState.value;
      },
      get pagination() {
        return paginationState.value;
      },
      get rowSelection() {
        return rowSelection.value;
      },
    },
    getRowId: (row) => String(row.id),
    enableRowSelection: true,
    onRowSelectionChange: (updater) => {
      const next =
        updater instanceof Function ? updater(rowSelection.value) : updater;
      rowSelection.value = structureView.isStructure.value
        ? cascadeStructureSelection(rowSelection.value, next, tableData.value)
        : next;
    },
    ...sortingConfig,
    ...paginationConfig,
    enableMultiSort: false,
  });

  // Publish this index as the active one so sibling components (e.g. the asset
  // page's drag-and-drop) can reach its table + refresh without prop-drilling.
  // Clear it on teardown, but only if it's still the current one — during an SPA
  // page swap the next page may register before this one unmounts.
  const {table: activeTable, register} = useElementIndexTable();
  register({table: elementTable, onActionPerformed, refreshResults});
  onScopeDispose(() => {
    if (activeTable.value === elementTable) {
      register(null);
    }
  });

  return {
    elementIndex,
    elementTable,
    viewState,
    conditions,
    filters,
    columnOptions,
    tableColumns,
    reorder,
    sortField,
    sortDirection,
    mode,
    structureView,
    toggleStructure,
    canReorderStructure,
    canMoveRow,
    moveStructureRow,
    loading,
    visibleViewModes,
    rowSelection,
    refreshResults,
    onActionPerformed,
  };
}
