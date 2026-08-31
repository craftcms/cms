<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {type PaginationData, type SortItem} from '@/common/types';
  import {computed, h, ref} from 'vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {Link, router} from '@inertiajs/vue3';
  import {create, destroy, index} from '@actions/Settings/EntryTypesController';
  import {useServerPagination} from '@/modules/admin-table/composables/useServerPagination';
  import SearchForm from '@/modules/admin-table/components/SearchForm.vue';
  import {useServerSort} from '@/modules/admin-table/composables/useServerSort';
  import Empty from '@/common/components/Empty.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  type EntryTypeRow = CraftCms.Cms.Entry.Data.EntryTypeIndexData;

  const props = defineProps<{
    title: string;
    pagination: PaginationData;
    sort: Array<SortItem>;
    searchTerm?: string;
    data: Array<EntryTypeRow>;
    readOnly: boolean;
  }>();

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
        confirm: t(
          'Are you sure you want to delete “{name}” and all entries of that type?',
          {name: row.original.title}
        ),
        onClick: () => router.delete(destroy({entryType: row.original.id})),
      }),
    ]),
  ]);

  const {paginationState, paginationConfig} = useServerPagination({
    initialState: props.pagination,
    onChange: ({query}) => {
      router.visit(
        index(
          {},
          {
            query,
          }
        ),
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
        index(
          {},
          {
            query,
          }
        ),
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

  useAppLayout({title: props.title});
</script>

<template>
  <LayoutSlot name="actions">
    <Link as="craft-button" :href="create().url" variant="primary" icon="plus">
      {{ t('New entry type') }}
    </Link>
  </LayoutSlot>

  <craft-pane padding="0" appearance="raised">
    <AdminTable
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
  </craft-pane>
</template>
