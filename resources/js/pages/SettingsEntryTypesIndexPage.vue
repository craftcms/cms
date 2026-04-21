<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {type PaginationData, type SortItem, TableSpacing} from '@/types';
  import {computed, h, ref} from 'vue';
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';
  import {router} from '@inertiajs/vue3';
  import {create, destroy, index} from '@actions/Settings/EntryTypesController';
  import AppLayout from '@/layout/AppLayout.vue';
  import Pane from '@/components/Pane.vue';
  import {useServerPagination} from '@/composables/useServerPagination';
  import SearchForm from '@/components/AdminTable/SearchForm.vue';
  import {useServerSort} from '@/composables/useServerSort';
  import CpLink from '@/components/CpLink.vue';
  import Empty from '@/components/Empty.vue';
  import DeleteButton from '@/components/AdminTable/DeleteButton.vue';
  import {createCraftColumnHelper} from '@/components/AdminTable/createCraftColumnHelper';

  type EntryTypeRow = {
    id: number;
    title: string;
    chip: string;
    handle: string;
    usages: string;
  };

  const props = defineProps<{
    title: string;
    pagination: PaginationData;
    sort: Array<SortItem>;
    searchTerm?: string;
    data: Array<EntryTypeRow>;
    readOnly: boolean;
  }>();

  function deleteEntryType(entryType: EntryTypeRow) {
    if (
      confirm(
        t(
          'Are you sure you want to delete “{name}” and all entries of that type?',
          {
            name: entryType.title,
          }
        )
      )
    ) {
      router.delete(destroy(entryType.id));
    }
  }

  const searchTerm = ref(props.searchTerm ?? '');
  const entryTypes = computed(() => props.data);
  const columnHelper = createCraftColumnHelper<EntryTypeRow>();
  const columnVisibility = computed(() => {
    return {
      name: true,
      handle: true,
      usages: true,
      actions: !props.readOnly,
    };
  });
  const columns = computed(() => [
    columnHelper.display({
      id: 'name',
      header: t('Entry Type'),
      cell: ({row}) => h(DynamicHtmlRenderer, {html: row.original.chip}),
    }),
    columnHelper.accessor('handle', {
      header: t('Handle'),
      meta: {
        cellClass: 'justify-center',
      },
      cell: ({getValue}) =>
        h('craft-copy-attribute', {value: getValue()}, getValue()),
    }),
    columnHelper.accessor('usages', {
      header: t('Usages'),
      cell: ({getValue}) => h(DynamicHtmlRenderer, {html: getValue()}),
    }),
    columnHelper.actions(({row}) => [
      h(DeleteButton, {
        onClick: () => deleteEntryType(row.original),
      }),
    ]),
  ]);

  const {paginationState, paginationConfig} = useServerPagination({
    initialState: props.pagination,
    onChange: ({query}) => {
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

  const {sortingState, sortingConfig} = useServerSort({
    initialState: props.sort,
    onChange: ({query}) => {
      router.visit(
        index({
          query,
        }),
        {
          only: ['data', 'sort'],
          preserveScroll: true,
        }
      );
    },
  });

  const table = useVueTable<EntryTypeRow>({
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
        return sortingState.value;
      },

      get columnVisibility() {
        return columnVisibility.value;
      },
    },
    getCoreRowModel: getCoreRowModel<EntryTypeRow>(),
    ...paginationConfig,
    ...sortingConfig,
  });
</script>

<template>
  <AppLayout :title="title">
    <template #actions>
      <CpLink
        appearance="button"
        :href="create['/admin/settings/entry-types/new']().url"
        variant="primary"
        :inertia="false"
        icon="plus"
      >
        {{ t('New entry type') }}
      </CpLink>
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
        <template #empty-row>
          <Empty icon="light/files" :label="t('No entry types exist yet.')" />
        </template>
        <template #search-form>
          <SearchForm :action="index()" v-model="searchTerm" />
        </template>
      </AdminTable>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
