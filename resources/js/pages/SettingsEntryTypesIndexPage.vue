<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import IndexLayout from '@/layout/IndexLayout.vue';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {
    createColumnHelper,
    getCoreRowModel,
    type PaginationState,
    type SortingState,
    useVueTable,
  } from '@tanstack/vue-table';
  import {
    type EntryType,
    type PaginationData,
    type SortItem,
    TableSpacing,
  } from '@/types';
  import {computed, h, ref, watch} from 'vue';
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';
  import {Form, router} from '@inertiajs/vue3';
  import {index} from '@actions/Settings/EntryTypesController';
  import AppLayout from '@/layout/AppLayout.vue';
  import Pane from '@/components/Pane.vue';
  import {useServerPagination} from '@/composables/useServerPagination';

  const props = defineProps<{
    title: string;
    pagination: PaginationData;
    sort: Array<SortItem>;
    searchTerm?: string;
    data: Array<{
      id: number;
      title: string;
      chip: string;
      handle: string;
      usages: string;
    }>;
    dataEndpoint: string;
  }>();

  const {paginationState, getNextQueryParams} =
    useServerPagination({
      initialState: props.pagination,
    });
  // const pageParam = Craft.pageTrigger ?? 'page';
  const searchTerm = ref(props.searchTerm ?? '');
  const entryTypes = computed(() => props.data);
  const columnHelper = createColumnHelper<EntryType>();
  const columns = computed(() => [
    columnHelper.accessor('chip', {
      header: t('Entry Type'),
      cell: ({getValue}) => h(DynamicHtmlRenderer, {html: getValue()}),
    }),
    columnHelper.accessor('handle', {
      header: t('Handle'),
      cell: ({getValue}) =>
        h('craft-copy-attribute', {value: getValue()}, getValue()),
    }),
    columnHelper.accessor('usages', {
      header: t('Usages'),
      cell: ({getValue}) => h(DynamicHtmlRenderer, {html: getValue()}),
    }),
  ]);

  // const pageIndex = computed(() =>
  //   props.pagination.current_page ? props.pagination.current_page - 1 : 0
  // );
  // const tablePagination = ref<PaginationState>({
  //   pageIndex: pageIndex.value,
  //   pageSize: props.pagination.per_page,
  // });

  const sorting = ref<SortingState>(
    props.sort
      ? props.sort.map((sort) => ({
          id: sort.field,
          desc: sort.direction === 'desc',
        }))
      : []
  );

  const table = useVueTable<EntryType>({
    get data() {
      return entryTypes.value;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get pagination() {
        return paginationState.value;
      },
      get sorting() {
        return sorting.value;
      },
    },
    getCoreRowModel: getCoreRowModel<EntryType>(),
    manualPagination: true,
    rowCount: props.pagination.total,

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
      const query = getNextQueryParams(updater);

      router.visit(
        index({
          query,
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
      <craft-button variant="primary" type="button" icon="plus">{{
        t('New entry type')
      }}</craft-button>
    </template>

    <Pane :padding="0" appearance="raised">
      <AdminTable
        :spacing="TableSpacing.Relaxed"
        :table="table"
        :reorderable="false"
        :from="pagination.from"
        :to="pagination.to"
        :total="pagination.total"
        :enable-adjust-page-size="true"
      >
        <template #search-form>
          <Form :action="index()" v-slot="{processing}" class="w-full">
            <div class="flex gap-2 items-start">
              <craft-input
                class="flex-1"
                name="search"
                :label="t('Search term')"
                :value="searchTerm"
                label-sr-only
              >
              </craft-input>
              <craft-button type="submit" :loading="processing" slot="suffix">{{
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
