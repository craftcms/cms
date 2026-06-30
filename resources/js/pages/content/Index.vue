<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';
  import ElementSources from '@/modules/elements/ElementSources.vue';
  import type {Source, SourceItem} from '@/modules/elements/types/sources';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import ElementIndexToolbar from '@/modules/elements/components/ElementIndexToolbar.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {
    getCoreRowModel,
    type RowSelectionState,
    useVueTable,
  } from '@tanstack/vue-table';
  import {type Component, computed, ref} from 'vue';
  import {
    type PaginationData,
    type SortItem,
    TableSpacing,
  } from '@/common/types';
  import {useElementIndexViewState} from '@/modules/elements/composables/useElementIndexViewState';
  import {useElementIndexFilters} from '@/modules/elements/composables/useElementIndexFilters';
  import {useElementIndexColumns} from '@/modules/elements/composables/useElementIndexColumns';
  import {useElementIndexSort} from '@/modules/elements/composables/useElementIndexSort';
  import {useElementIndexPagination} from '@/modules/elements/composables/useElementIndexPagination';
  import {useElementIndexViewMode} from '@/modules/elements/composables/useElementIndexViewMode';
  import {useElementIndexLoading} from '@/modules/elements/composables/useElementIndexLoading';
  import type {
    SortOption,
    ViewMode,
    ViewState,
  } from '@/modules/elements/types/view-state';
  import type {BulkActionItem} from '@/modules/elements/types/actions';
  import {router} from '@inertiajs/vue3';

  type Element = Record<any, any>;

  const props = withDefaults(
    defineProps<{
      elementType: string;
      elementDisplayName: string;
      elementPluralDisplayName: string;
      context?: string;
      canHaveDrafts?: boolean;
      criteria?: Record<string, any>;
      page: string;
      sources: Array<Source>;
      source?: SourceItem;
      contentHtml?: string;
      search?: string | null;
      status: string | null;
      viewMode?: string | null;
      statusOptions?: Array<{label: string; value: string}>;
      sectionHandle?: string | number;
      viewState: Partial<ViewState>;
      data?: Array<Element>;
      tableColumns: Array<{label: string; value: string}>;
      defaultTableColumns?: Array<string>;
      viewModes?: Array<ViewMode>;
      baseSortOptions: Array<SortOption>;
      pagination: PaginationData;
      sort: Array<SortItem>;
      // The serialized bulk actions available for the active source (null when
      // the source/context offers none).
      actions?: Array<BulkActionItem> | null;
    }>(),
    {
      context: 'index',
      canHaveDrafts: false,
      data: () => [],
      criteria: () => Craft.defaultIndexCriteria,
    }
  );

  const viewState = useElementIndexViewState(props);
  const filters = useElementIndexFilters(props, viewState);
  const {columns, columnOrder, columnOptions, reorder, tableColumns} =
    useElementIndexColumns(props, viewState, {key: 'title', label: t('Entry')});
  const {sortingState, sortingConfig, sortField, sortDirection} =
    useElementIndexSort(props, viewState);
  const {paginationState, paginationConfig} = useElementIndexPagination(props);
  const {mode} = useElementIndexViewMode(props, viewState);
  const {loading} = useElementIndexLoading();

  // The structure view mode only applies to structure sources, so hide it
  // (and any other `structuresOnly` mode) unless the active source is one.
  const visibleViewModes = computed(() =>
    (props.viewModes ?? []).filter(
      (viewMode) =>
        !viewMode.structuresOnly || props.source?.structureId != null
    )
  );

  const visibleColumns = ref({});

  // Selection is keyed by element id (see `getRowId`), so it survives sorting
  // and pagination. Read the current selection from `rowSelection` (a map of
  // element id → selected) or via `elementTable.getSelectedRowModel()`.
  const rowSelection = ref<RowSelectionState>({});

  // After a bulk action succeeds, refresh the server-rendered list + counts the
  // same way the view-mode/filter composables do (a partial Inertia reload that
  // only re-pulls the index props), then clear any lingering selection. The
  // table also clears its own selection optimistically when the action fires.
  function onActionPerformed() {
    rowSelection.value = {};
    router.reload({
      only: ['data', 'pagination', 'badgeCounts'],
    });
  }

  function createCustomizeSourcesModal() {
    // The modal was written for the legacy BaseElementIndex instance, but it
    // only reads a few things off it: the element type (to load/save settings),
    // the current `settings.page`, and the current/root source key (to preselect
    // a row). Its save flow reloads the page, so `asyncSelectSourceByKey` /
    // `$visibleSources` only need to be safe no-ops here.
    const elementIndexShim = {
      elementType: props.elementType,
      settings: {page: props.page},
      sourceKey: props.source?.key ?? null,
      rootSourceKey: props.source?.key ?? null,
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
      return props.data ?? [];
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
      rowSelection.value =
        typeof updater === 'function' ? updater(rowSelection.value) : updater;
    },
    getCoreRowModel: getCoreRowModel<Element>(),
    ...sortingConfig,
    ...paginationConfig,
    enableMultiSort: false,
  });

  // ElementCards and AdminTable render the same data via two different
  // layouts; they share most props, but each has a couple of its own (`data`
  // for cards, `spacing` for the table) that the other doesn't declare.
  // Keeping those separate from `sharedProps` avoids leaking them as raw DOM
  // attributes onto whichever component isn't rendered (neither sets
  // `inheritAttrs: false`).
  const indexComponent = computed<Component>(() =>
    mode.value === 'cards' ? ElementCards : AdminTable
  );

  const sharedProps = computed(() => ({
    table: elementTable,
    selectable: true,
    loading: loading.value,
    from: props.pagination.from,
    to: props.pagination.to,
    total: props.pagination.total,
    enableAdjustPageSize: true,
    actions: props.actions,
    elementType: props.elementType,
    source: props.source?.key,
    context: props.context,
  }));

  const modeSpecificProps = computed(() =>
    mode.value === 'cards'
      ? {data: props.data}
      : {spacing: TableSpacing.Spacious}
  );
</script>

<template>
  <IndexLayout>
    <template #interior-nav>
      <nav aria-labelledby="source-heading">
        <h2 id="source-heading" class="sr-only">
          {{ t('Sources') }}
        </h2>
        <ElementSources
          :sources="sources"
          :active-source="source?.key"
          :view-mode="mode"
        />
      </nav>
    </template>

    <template #subnav-actions>
      <ActionMenu
        :actions="[
          {
            label: t('Customize sources'),
            onClick: () => createCustomizeSourcesModal(),
          },
        ]"
      />
    </template>

    <component
      :is="indexComponent"
      v-bind="{...sharedProps, ...modeSpecificProps}"
      @action-performed="onActionPerformed"
    >
      <template #table-header>
        <ElementIndexToolbar
          v-model:search="filters.form.search"
          v-model:status="filters.form.status"
          :processing="filters.form.processing"
          :status-options="statusOptions"
          :view-modes="visibleViewModes"
          :column-options="columnOptions"
          v-model:mode="mode"
          v-model:sort-field="sortField"
          v-model:sort-direction="sortDirection"
          v-model:table-columns="tableColumns"
          @submit="filters.submit"
          @reorder="reorder"
        />
      </template>
    </component>
  </IndexLayout>
</template>

<style scoped lang="scss"></style>
