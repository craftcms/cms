<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AppLayout from '@/layout/AppLayout.vue';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
  } from '@tanstack/vue-table';
  import {computed, h, ref} from 'vue';
  import type {PaginationData, SortItem} from '@/types';
  import {useServerPagination} from '@/composables/useServerPagination';
  import Pane from '@/components/Pane.vue';
  import {router} from '@inertiajs/vue3';
  import {create, destroy, index} from '@actions/FieldsController';
  import DeleteButton from '@/components/AdminTable/DeleteButton.vue';
  import CpLink from '@/components/CpLink.vue';
  import {useServerSort} from '@/composables/useServerSort';
  import SearchForm from '@/components/AdminTable/SearchForm.vue';
  import Empty from '@/components/Empty.vue';

  type FieldRow = {
    id: number;
    title: string;
    translatable: boolean;
    url: string;
    handle: string;
    type: {
      isMissing: boolean;
      label: string;
      icon: string;
    };
    usages: string;
  };

  const props = defineProps<{
    title: string;
    readOnly: boolean;
    data: Array<FieldRow>;
    sort: Array<SortItem>;
    searchTerm: string | null;
    pagination: PaginationData;
  }>();

  function deleteField(field: FieldRow) {
    if (
      confirm(
        t('Are you sure you want to delete “{name}”?', {
          name: field.title,
        })
      )
    ) {
      router.delete(destroy({fieldId: field.id}));
    }
  }

  const searchTerm = ref(props.searchTerm ?? '');
  const columnHelper = createColumnHelper<FieldRow>();
  const columnVisibility = computed(() => {
    return {
      name: true,
      handle: true,
      type: true,
      usages: true,
      actions: !props.readOnly,
    };
  });
  const columns = ref([
    columnHelper.accessor('title', {
      header: t('Name'),
      cell: ({row, getValue}) =>
        h(CpLink, {href: row.original.url, inertia: false}, getValue),
    }),
    columnHelper.accessor('handle', {
      header: t('Handle'),
      cell: ({getValue}) =>
        h('craft-copy-attribute', {value: getValue()}, getValue),
    }),
    columnHelper.display({
      id: 'type',
      header: t('Type'),
      cell: ({row}) => {
        if (row.original.type.isMissing) {
          return t('Missing');
        }

        return h('div', {class: 'flex items-center gap-2'}, [
          h('craft-icon', row.original.type.icon),
          h('span', row.original.type.label),
        ]);
      },
    }),
    columnHelper.accessor('usages', {
      header: t('Used by'),
    }),
    columnHelper.display({
      id: 'actions',
      cell: ({row}) =>
        h('div', {class: 'flex justify-end'}, [
          h(DeleteButton, {
            onClick: () => deleteField(row.original),
          }),
        ]),
    }),
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

  const table = useVueTable({
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
      get columnVisibility() {
        return columnVisibility.value;
      },
      get sorting() {
        return sortingState.value;
      },
    },

    getCoreRowModel: getCoreRowModel<FieldRow>(),
    ...paginationConfig,
    ...sortingConfig,
  });
</script>

<template>
  <AppLayout :title="title">
    <template #actions>
      <CpLink
        :inertia="false"
        appearance="button"
        variant="primary"
        :href="create()"
        icon="plus"
      >
        {{ t('New field') }}
      </CpLink>
    </template>

    <Pane :padding="0" appearance="raised">
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
            icon="light/pen-to-square"
            :label="t('No fields exist yet.')"
          />
        </template>
        <template #search-form>
          <SearchForm v-model="searchTerm" />
        </template>
      </AdminTable>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
