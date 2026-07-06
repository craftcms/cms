<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {computed, h} from 'vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {useServerPagination} from '@/modules/admin-table/composables/useServerPagination';
  import {useServerSort} from '@/modules/admin-table/composables/useServerSort';
  import CpLink from '@/common/components/CpLink.vue';
  import Empty from '@/common/components/Empty.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {router} from '@inertiajs/vue3';
  import {
    type PaginationData,
    type SortItem,
    TableSpacing,
  } from '@/common/types';
  import ElementIndexToolbar from './ElementIndexToolbar.vue';
  import {useElementIndexQuery} from '../composables/useElementIndexQuery';
  import type {
    ElementIndexColumn,
    ElementIndexElement,
    ElementIndexSite,
    ElementIndexSortOption,
    ElementIndexStatus,
  } from '../types';

  const props = defineProps<{
    columns: Array<ElementIndexColumn>;
    sortOptions: Array<ElementIndexSortOption>;
    sort: Array<SortItem>;
    elements: Array<ElementIndexElement>;
    pagination: PaginationData | null;
    searchTerm: string | null;
    statuses: Array<ElementIndexStatus>;
    selectedStatus: string | null;
    sites: Array<ElementIndexSite>;
    selectedSiteId: number | null;
    elementTypePluralName?: string;
  }>();

  const {apply} = useElementIndexQuery();

  const sortableAttributes = computed(
    () => new Set(props.sortOptions.map((option) => option.attribute))
  );

  const tableColumns = computed(() =>
    props.columns.map((column) => ({
      id: column.key,
      header: () => column.label,
      enableSorting: sortableAttributes.value.has(column.key),
      cell: ({row}: {row: {original: ElementIndexElement}}) =>
        column.key === 'title'
          ? h('div', {class: 'flex gap-2 items-center'}, [
              row.original.status
                ? h('span', {
                    class: ['status', row.original.status],
                    'aria-hidden': 'true',
                  })
                : null,
              row.original.url
                ? h(
                    CpLink,
                    {
                      href: row.original.url,
                      class: 'font-bold',
                      inertia: false,
                    },
                    () => row.original.title
                  )
                : h('span', {class: 'font-bold'}, row.original.title),
            ])
          : h(DynamicHtmlRenderer, {
              html: row.original.attributeHtml[column.key] ?? '',
            }),
    }))
  );

  const {paginationState, paginationConfig} = useServerPagination({
    initialState: props.pagination ?? {
      current_page: 1,
      per_page: 100,
      total: 0,
      last_page: 1,
      next_page_url: null,
      prev_page_url: null,
      from: 0,
      to: 0,
    },
    onChange: ({query}) => {
      router.get(window.location.pathname, query, {
        only: ['elements', 'pagination'],
        preserveState: true,
        preserveScroll: true,
      });
    },
  });

  const {sortingState, sortingConfig} = useServerSort({
    initialState: props.sort,
    onChange: ({query}) => {
      router.get(window.location.pathname, query, {
        only: ['elements', 'pagination', 'sort'],
        preserveState: true,
        preserveScroll: true,
      });
    },
  });

  const table = useVueTable<ElementIndexElement>({
    get data() {
      return props.elements;
    },
    get columns() {
      return tableColumns.value;
    },
    state: {
      get pagination() {
        return paginationState.value;
      },
      get sorting() {
        return sortingState.value;
      },
    },
    getRowId: (row) => String(row.id),
    getCoreRowModel: getCoreRowModel<ElementIndexElement>(),
    ...paginationConfig,
    ...sortingConfig,
    enableMultiSort: false,
  });
</script>

<template>
  <AdminTable
    :spacing="TableSpacing.Relaxed"
    :table="table"
    :reorderable="false"
    :selectable="false"
    :from="pagination?.from"
    :to="pagination?.to"
    :total="pagination?.total"
    :enable-adjust-page-size="true"
  >
    <template #search-form>
      <ElementIndexToolbar
        :search-term="searchTerm"
        :statuses="statuses"
        :selected-status="selectedStatus"
        :sites="sites"
        :selected-site-id="selectedSiteId"
        @update:search="
          (value) =>
            apply({search: value || null}, [
              'elements',
              'pagination',
              'searchTerm',
            ])
        "
        @update:status="
          (value) =>
            apply({status: value || null}, [
              'elements',
              'pagination',
              'selectedStatus',
            ])
        "
        @update:site="(value) => apply({site: value || null}, [])"
      />
    </template>
    <template #empty-row>
      <Empty
        icon="light/files"
        :label="
          t('No {elements} found.', {
            elements: elementTypePluralName?.toLowerCase() ?? t('elements'),
          })
        "
      >
        <slot name="empty-actions"></slot>
      </Empty>
    </template>
  </AdminTable>
</template>
