<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
  } from '@tanstack/vue-table';
  import {computed, h, ref} from 'vue';
  import type {PaginationData, SortItem} from '@/common/types';
  import {useServerPagination} from '@/modules/admin-table/composables/useServerPagination';
  import {router} from '@inertiajs/vue3';
  import {create, destroy, index} from '@actions/FieldsController';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import {useServerSort} from '@/modules/admin-table/composables/useServerSort';
  import SearchForm from '@/modules/admin-table/components/SearchForm.vue';
  import Empty from '@/common/components/Empty.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  type FieldRow = {
    id: number;
    title: string;
    translatable: boolean;
    searchable: boolean;
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
    isMultiSite: boolean;
  }>();

  const searchTerm = ref(props.searchTerm ?? '');
  const columnHelper = createColumnHelper<FieldRow>();
  const columnVisibility = computed(() => {
    return {
      name: true,
      searchable: true,
      translatable: props.isMultiSite,
      handle: true,
      type: true,
      usages: true,
      actions: !props.readOnly,
    };
  });
  const columns = ref([
    columnHelper.accessor('title', {
      header: t('Name'),
      meta: {
        trackSize: '1.5fr',
      },
      cell: ({row, getValue}) =>
        h(
          CpLink,
          {href: row.original.url, inertia: false, class: 'font-bold'},
          getValue
        ),
    }),
    columnHelper.accessor('searchable', {
      header: t('Searchable'),
      meta: {
        trackSize: '34px',
        headerSrOnly: true,
      },
      enableSorting: false,
      cell: ({row}) => {
        if (row.original.searchable) {
          return h('craft-icon', {
            appearance: 'badge',
            name: 'magnifying-glass',
            label: t('This field’s values are used as search keywords.'),
          });
        }
      },
    }),
    columnHelper.accessor('translatable', {
      header: t('Translatable'),
      meta: {
        trackSize: '34px',
        headerSrOnly: true,
      },
      enableSorting: false,
      cell: ({getValue}) => {
        if (getValue()) {
          return h('craft-icon', {
            appearance: 'badge',
            name: 'custom-icons/language',
            label: getValue(),
          });
        }
      },
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
      meta: {
        trackSize: '60px',
      },
      cell: ({row}) =>
        h('div', {class: 'self-end flex justify-end'}, [
          h(DeleteButton, {
            confirm: t('Are you sure you want to delete “{name}”?', {
              name: row.original.title,
            }),
            onClick: () => router.delete(destroy({fieldId: row.original.id})),
          }),
        ]),
    }),
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

  useAppLayout({title: props.title});
</script>

<template>
  <LayoutSlot name="actions">
    <CpLink
      :inertia="false"
      appearance="button"
      variant="accent"
      :href="create()"
      icon="plus"
    >
      {{ t('New field') }}
    </CpLink>
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
        <Empty icon="light/pen-to-square" :label="t('No fields exist yet.')" />
      </template>
      <template #search-form>
        <SearchForm v-model="searchTerm" />
      </template>
    </AdminTable>
  </craft-pane>
</template>
