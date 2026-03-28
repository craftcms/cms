<script setup lang="ts">
  import {
    createColumnHelper,
    getCoreRowModel,
    type PaginationState,
    type SortingState,
    useVueTable,
  } from '@tanstack/vue-table';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {computed, h, ref} from 'vue';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import DeleteSectionButton from '@/components/sections/DeleteSectionButton.vue';
  import {create, edit, index} from '@actions/Settings/SectionsController';
  import {Form, router} from '@inertiajs/vue3';
  import AppLayout from '@/layout/AppLayout.vue';
  import Pane from '@/components/Pane.vue';
  import CpLink from '@/components/CpLink.vue';

  export interface SectionModel {
    id: number;
    title: string;
    name: string;
    url: string;
    handle: string;
    type: string;
  }

  interface PaginationData {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    from: number;
    to: number;
  }

  interface SortItem {
    field: string;
    direction: 'desc' | 'asc';
  }

  const props = defineProps<{
    title: string;
    data: Array<SectionModel>;
    pagination: PaginationData;
    sort?: Array<SortItem>;
    searchTerm?: string;
    emptyMessage: string;
    readOnly: boolean;
  }>();

  const columnHelper = createColumnHelper<SectionModel>();
  const columns = ref([
    columnHelper.accessor('name', {
      header: t('Name'),
      cell: ({row, getValue}) =>
        h(
          'a',
          {
            class: 'font-bold',
            href: edit['/admin/settings/sections/{section}'](row.original.id)
              .url,
          },
          getValue()
        ),
    }),
    columnHelper.accessor('handle', {
      header: t('Handle'),
      cell: ({getValue}) =>
        h('craft-copy-attribute', {value: getValue()}, getValue()),
    }),
    columnHelper.accessor('type', {
      header: t('Type'),
    }),
    columnHelper.display({
      id: 'actions',
      cell: ({row}) =>
        h(
          'div',
          {class: 'flex justify-end items-center gap-2'},
          h(DeleteSectionButton, {section: row.original})
        ),
    }),
  ]);

  const pageIndex = computed(() =>
    props.pagination.current_page ? props.pagination.current_page - 1 : 0
  );
  const pageParam = window.Craft?.pageTrigger ?? 'page';
  const tablePagination = ref<PaginationState>({
    pageIndex: pageIndex.value,
    pageSize: props.pagination.per_page,
  });
  const sorting = ref<SortingState>(
    props.sort
      ? props.sort.map((sort) => ({
          id: sort.field,
          desc: sort.direction === 'desc',
        }))
      : []
  );
  const sectionTable = useVueTable({
    get data() {
      return props.data;
    },
    get columns() {
      return columns.value;
    },
    getCoreRowModel: getCoreRowModel<SectionModel>(),
    manualPagination: true,
    manualSorting: true,
    rowCount: props.pagination.total,
    enableMultiSort: true,
    enableSortingRemoval: false,
    state: {
      get pagination() {
        return tablePagination.value;
      },
      get sorting() {
        return sorting.value;
      },
    },

    onSortingChange: (updater) => {
      const next =
        typeof updater === 'function' ? updater(sorting.value) : updater;

      // Convert array of objects to indexed object format
      const sortQueryParams = next.reduce<
        Record<number, {field: string; direction: string}>
      >((acc, sortCol, index) => {
        acc[index] = {
          field: sortCol.id,
          direction: sortCol.desc ? 'desc' : 'asc',
        };
        return acc;
      }, {});

      const currentQuery = new URLSearchParams(window.location.search);
      router.visit(
        index({
          query: {
            ...Object.fromEntries(currentQuery),
            sort: sortQueryParams,
            [pageParam]: 1,
          },
        }),
        {
          only: ['data', 'sort'],
          preserveScroll: true,
        }
      );
    },

    onPaginationChange: (updater) => {
      const next =
        typeof updater === 'function'
          ? updater(tablePagination.value)
          : updater;

      const currentQuery = new URLSearchParams(window.location.search);

      router.visit(
        index({
          query: {
            ...Object.fromEntries(currentQuery),
            [pageParam]: next.pageIndex + 1,
            per_page: next.pageSize,
          },
        }),
        {
          only: ['data', 'pagination'],
          preserveScroll: true,
        }
      );
    },
  });
</script>

<template>
  <AppLayout :title="title">
    <template #actions>
      <CpLink as="craft-button" variant="primary" :href="create()">
        <craft-icon name="plus" slot="prefix"></craft-icon>
        {{ t('New section') }}
      </CpLink>
    </template>

    <Pane :padding="0" appearance="raised">
      <AdminTable
        spacing="relaxed"
        :table="sectionTable"
        :reorderable="false"
        :from="pagination.from"
        :to="pagination.to"
        :total="pagination.total"
        :enable-adjust-page-size="true"
      >
        <template #search-form>
          <Form :action="index()" v-slot="{processing}">
            <div class="flex gap-1 items-center">
              <craft-input
                name="search"
                :label="t('Search term')"
                :value="searchTerm"
                label-sr-only
              ></craft-input>
              <craft-button type="submit" :loading="processing">{{
                t('Search')
              }}</craft-button>
            </div>
          </Form>
        </template>
      </AdminTable>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
