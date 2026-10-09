<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {useCraftTable} from '@/common/table/craftTable';
  import {computed, h, ref} from 'vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import type {PaginationData, SortItem} from '@/common/types';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import SearchForm from '@/modules/admin-table/components/SearchForm.vue';
  import {useServerPagination} from '@/common/table/useServerPagination';
  import {useServerSort} from '@/common/table/useServerSort';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
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
  const columns = computed(() =>
    columnHelper.columns([
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
    ])
  );

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
  const table = useCraftTable<Workflow>({
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
    ...paginationConfig,
    ...sortingConfig,
  });
</script>

<template>
  <LayoutSlot v-if="!readOnly" name="content-actions">
    <CpButtonLink :href="create().url" variant="primary" icon="plus">
      {{ t('New workflow') }}
    </CpButtonLink>
  </LayoutSlot>

  <AdminTable
    padded
    :table="table"
    :reorderable="false"
    :from="pagination.from"
    :to="pagination.to"
    :total="pagination.total"
    :enable-adjust-page-size="true"
  >
    <template #empty-row>
      <craft-empty
        icon="light/clipboard-list-check"
        :label="t('No approval workflows exist yet.')"
      ></craft-empty>
    </template>
    <template #table-header>
      <SearchForm :action="index()" v-model="searchTerm" />
    </template>
  </AdminTable>
</template>
