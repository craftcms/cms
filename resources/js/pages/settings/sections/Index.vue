<script setup lang="ts">
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {h, ref} from 'vue';
  import {t} from '@craftcms/ui/utilities/translate';
  import DeleteSectionButton from '@/modules/sections/components/DeleteSectionButton.vue';
  import {create, edit, index} from '@actions/Settings/SectionsController';
  import {router} from '@inertiajs/vue3';
  import CpLink from '@/common/components/CpLink.vue';
  import useCraftData from '@/common/composables/useCraftData';
  import CalloutReadOnly from '@/common/components/CalloutReadOnly.vue';
  import type {PaginationData, SortItem} from '@/common/types';
  import SearchForm from '@/modules/admin-table/components/SearchForm.vue';
  import {useServerPagination} from '@/modules/admin-table/composables/useServerPagination';
  import {useServerSort} from '@/modules/admin-table/composables/useServerSort';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  export interface SectionModel {
    id: number;
    title: string;
    name: string;
    url: string;
    handle: string;
    type: string;
  }

  const props = defineProps<{
    title: string;
    data: Array<SectionModel>;
    pagination: PaginationData;
    sort: Array<SortItem>;
    searchTerm?: string;
    emptyMessage: string;
  }>();

  const {readOnly} = useCraftData();
  const searchTerm = ref(props.searchTerm ?? '');

  useAppLayout(() => ({title: props.title}));
  const columnHelper = createCraftColumnHelper<SectionModel>();
  const columns = ref([
    columnHelper.accessor('name', {
      header: t('Name'),
      cell: ({row, getValue}) =>
        h(
          'a',
          {
            class: 'cp:font-bold',
            href: edit({section: row.original.id}).url,
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
    columnHelper.actions(({row}) => [
      h(DeleteSectionButton, {section: row.original}),
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

  const sectionTable = useVueTable({
    get data() {
      return props.data;
    },
    get columns() {
      return columns.value;
    },
    getCoreRowModel: getCoreRowModel<SectionModel>(),
    state: {
      get pagination() {
        return paginationState.value;
      },
      get sorting() {
        return sortingState.value;
      },
      get columnVisibility() {
        return {
          actions: !readOnly,
        };
      },
    },
    ...paginationConfig,
    ...sortingConfig,
  });
</script>

<template>
  <LayoutSlot name="actions">
    <CpLink
      as="craft-button"
      variant="accent"
      :href="create()"
      v-if="!readOnly"
    >
      <craft-icon name="plus" slot="prefix"></craft-icon>
      {{ t('New section') }}
    </CpLink>
  </LayoutSlot>

  <CalloutReadOnly v-if="readOnly"></CalloutReadOnly>

  <craft-pane padding="0" appearance="raised">
    <AdminTable
      :title="title"
      :table="sectionTable"
      :reorderable="false"
      :from="pagination.from"
      :to="pagination.to"
      :total="pagination.total"
      :enable-adjust-page-size="true"
    >
      <template #search-form>
        <SearchForm :action="index()" v-model="searchTerm" />
      </template>
    </AdminTable>
  </craft-pane>
</template>
