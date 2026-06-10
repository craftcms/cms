<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';
  import ElementSources from '@/modules/elements/ElementSources.vue';
  import type {Source} from '@/modules/elements/types/sources';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import ElementIndexToolbar from '@/modules/elements/components/ElementIndexToolbar.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {ref} from 'vue';
  import type {PaginationData, SortItem} from '@/common/types';
  import {useElementIndexViewState} from '@/modules/elements/composables/useElementIndexViewState';
  import {useElementIndexFilters} from '@/modules/elements/composables/useElementIndexFilters';
  import {useElementIndexColumns} from '@/modules/elements/composables/useElementIndexColumns';
  import {useElementIndexSort} from '@/modules/elements/composables/useElementIndexSort';
  import {useElementIndexPagination} from '@/modules/elements/composables/useElementIndexPagination';
  import type {
    SortOption,
    ViewMode,
    ViewState,
  } from '@/modules/elements/types/view-state';

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
      source?: Source;
      contentHtml?: string;
      search?: string | null;
      status: string;
      viewMode?: string | null;
      statusOptions?: Array<{label: string; value: string}>;
      sectionHandle?: string | number;
      viewState: Partial<ViewState>;
      data: Array<Element>;
      tableColumns: Record<string, {label: string}>;
      viewModes?: Array<ViewMode>;
      baseSortOptions: Array<SortOption>;
      pagination: PaginationData;
      sort: Array<SortItem>;
    }>(),
    {
      context: 'index',
      canHaveDrafts: false,
      criteria: () => Craft.defaultIndexCriteria,
      defaultSource: null,
      defaultSourcePath: null,
    }
  );

  const viewState = useElementIndexViewState(props);
  const filters = useElementIndexFilters(props, viewState);
  const {columns, columnOrder, columnOptions, reorder} = useElementIndexColumns(
    props,
    viewState,
    {key: 'title', label: t('Entry')}
  );
  const {sortingState, sortingConfig, sortField, sortDirection} =
    useElementIndexSort(props, viewState);
  const {paginationState, paginationConfig} = useElementIndexPagination(props);

  const visibleColumns = ref({});
  const elementTable = useVueTable<Element>({
    get data() {
      return props.data;
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
    },
    getCoreRowModel: getCoreRowModel<Element>(),
    ...sortingConfig,
    ...paginationConfig,
    enableMultiSort: false,
  });
</script>

<template>
  <IndexLayout>
    <template #interior-nav>
      <nav aria-labelledby="source-heading">
        <h2 id="source-heading" class="sr-only">
          {{ t('Sources') }}
        </h2>
        <ElementSources :sources="sources" :active-source="source?.key" />
      </nav>

      <div id="source-actions"></div>
    </template>

    <AdminTable
      :table="elementTable"
      :from="pagination.from"
      :to="pagination.to"
      :total="pagination.total"
      :enable-adjust-page-size="true"
    >
      <template #table-header>
        <ElementIndexToolbar
          v-model:search="filters.form.search"
          v-model:status="filters.form.status"
          :processing="filters.form.processing"
          :status-options="statusOptions"
          :view-modes="viewModes"
          :column-options="columnOptions"
          v-model:mode="viewState.mode"
          v-model:sort-field="sortField"
          v-model:sort-direction="sortDirection"
          v-model:table-columns="viewState.tableColumns"
          @submit="filters.submit"
          @reorder="reorder"
        />
      </template>
    </AdminTable>
  </IndexLayout>
</template>

<style scoped lang="scss"></style>
