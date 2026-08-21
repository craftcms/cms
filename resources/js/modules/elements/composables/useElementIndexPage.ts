import {router} from '@inertiajs/vue3';
import {
    getCoreRowModel,
    type RowSelectionState,
    useVueTable,
} from '@tanstack/vue-table';
import {computed, onMounted, onScopeDispose, ref} from 'vue';
import {useContentIndexData} from '@/modules/elements/composables/useContentIndexData';
import {useElementIndexTable} from '@/modules/elements/composables/useElementIndexTable';
import {useConditionBuilder} from '@/modules/elements/composables/useConditionBuilder';
import {useElementIndexColumns} from '@/modules/elements/composables/useElementIndexColumns';
import {useElementIndexFilters} from '@/modules/elements/composables/useElementIndexFilters';
import {useElementIndexLoading} from '@/modules/elements/composables/useElementIndexLoading';
import {useElementIndexPagination} from '@/modules/elements/composables/useElementIndexPagination';
import {useElementIndexSort} from '@/modules/elements/composables/useElementIndexSort';
import {useElementIndexViewMode} from '@/modules/elements/composables/useElementIndexViewMode';
import {useElementIndexViewState} from '@/modules/elements/composables/useElementIndexViewState';
import {
    createIndexVisitor,
    type ElementIndexRoute,
    type IndexRestore,
} from '@/modules/elements/composables/useElementIndexVisits';

type Element = Record<any, any>;

interface UseElementIndexPageOptions {
    /** The page's index route — the one per-page piece of the pipeline. */
    route: ElementIndexRoute;
    /**
     * The always-first, non-toggleable column. Defaults to the element's
     * `title` labeled with the payload's `elementDisplayName`.
     */
    pinnedColumn?: {key: string; label: string};
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
    const {conditions} = useConditionBuilder({
        initialState: elementIndex.currentCondition ?? null,
    });
    const filters = useElementIndexFilters(
        elementIndex,
        viewState,
        options.route,
        conditions
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
    const {mode, restore: restoreViewMode} = useElementIndexViewMode(
        options.route,
        viewState
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

    // The structure view mode only applies to structure sources, so hide it
    // (and any other `structuresOnly` mode) unless the active source is one.
    const visibleViewModes = computed(() =>
        elementIndex.viewModes.filter(
            (viewMode) =>
                !viewMode.structuresOnly ||
                elementIndex.source?.structureId != null
        )
    );

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

    function createCustomizeSourcesModal() {
        // The modal was written for the legacy BaseElementIndex instance, but it
        // only reads a few things off it: the element type (to load/save settings),
        // the current `settings.page`, and the current/root source key (to preselect
        // a row). Its save flow reloads the page, so `asyncSelectSourceByKey` /
        // `$visibleSources` only need to be safe no-ops here.
        const elementIndexShim = {
            elementType: elementIndex.elementType,
            settings: {page: elementIndex.page},
            sourceKey: elementIndex.source?.key ?? null,
            rootSourceKey: elementIndex.source?.key ?? null,
            $visibleSources: {first: () => ({data: () => null})},
            asyncSelectSourceByKey: () => Promise.resolve(),
        };

        // Recreate it each time, mirroring the legacy implementation.
        const modal = new Craft.CustomizeSourcesModal(elementIndexShim, {
            hideOnEsc: false,
            hideOnShadeClick: false,
            onFadeOut: function () {
                modal.destroy();
            },
        });

        return modal;
    }

    const elementTable = useVueTable<Element>({
        get data() {
            return elementIndex.data ?? [];
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
        // Folder rows (asset index) navigate rather than select, so they opt out of
        // selection and bulk actions.
        enableRowSelection: (row) => !row.original?.isFolder,
        onRowSelectionChange: (updater) => {
            rowSelection.value =
                typeof updater === 'function'
                    ? updater(rowSelection.value)
                    : updater;
        },
        getCoreRowModel: getCoreRowModel<Element>(),
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
        loading,
        visibleViewModes,
        rowSelection,
        refreshResults,
        onActionPerformed,
        createCustomizeSourcesModal,
    };
}
