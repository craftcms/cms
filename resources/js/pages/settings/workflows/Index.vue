<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {computed, h, ref} from 'vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import type {PaginationData, SortItem} from '@/common/types';
  import Empty from '@/common/components/Empty.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import SearchForm from '@/modules/admin-table/components/SearchForm.vue';
  import {useServerPagination} from '@/modules/admin-table/composables/useServerPagination';
  import {useServerSort} from '@/modules/admin-table/composables/useServerSort';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {
    create,
    destroy,
    edit,
    index,
  } from '@actions/Settings/WorkflowsController';

  interface Workflow {
    id: number;
    name: string;
    stages: number;
  }

  const props = defineProps<{
    title: string;
    data: Workflow[];
    pagination: PaginationData;
    sort: SortItem[];
    searchTerm?: string;
    readOnly: boolean;
  }>();
  const searchTerm = ref(props.searchTerm ?? '');

  useAppLayout({title: props.title, form: null});
  const columnHelper = createCraftColumnHelper<Workflow>();
  const columns = computed(() => [
    columnHelper.link('name', {
      header: t('Name'),
      props: ({row}) => ({href: edit({workflow: row.original.id}).url}),
    }),
    columnHelper.accessor('stages', {
      header: t('Stages'),
      enableSorting: false,
    }),
    columnHelper.actions(({row}) => [
      h(DeleteButton, {
        confirm: t('Are you sure you want to delete “{name}”?', {
          name: row.original.name,
        }),
        onClick: () => router.delete(destroy({workflow: row.original.id})),
      }),
    ]),
  ]);

  const {paginationState, paginationConfig} = useServerPagination({
    initialState: props.pagination,
    onChange: ({query}) => {
      router.visit(index({}, {query}), {
        only: ['data', 'pagination'],
        preserveScroll: true,
      });
    },
  });
  const {sortingState, sortingConfig} = useServerSort({
    initialState: props.sort,
    onChange: ({query}) => {
      router.visit(index({}, {query}), {
        only: ['data', 'sort'],
        preserveScroll: true,
      });
    },
  });
  const table = useVueTable<Workflow>({
    get data() {
      return props.data;
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
        return {actions: !props.readOnly};
      },
    },
    getCoreRowModel: getCoreRowModel<Workflow>(),
    ...paginationConfig,
    ...sortingConfig,
  });
</script>

<template>
  <LayoutSlot v-if="!readOnly" name="content-actions">
    <CpLink
      :href="create().url"
      variant="accent"
      appearance="button"
      icon="plus"
    >
      {{ t('New workflow') }}
    </CpLink>
  </LayoutSlot>

  <CpContainer>
    <AdminTable
      :table="table"
      :reorderable="false"
      :from="pagination.from"
      :to="pagination.to"
      :total="pagination.total"
      :enable-adjust-page-size="true"
    >
      <template #empty-row>
        <Empty
          icon="light/clipboard-list-check"
          :label="t('No approval workflows exist yet.')"
        />
      </template>
      <template #table-header>
        <SearchForm :action="index()" v-model="searchTerm" />
      </template>
    </AdminTable>
  </CpContainer>
</template>
