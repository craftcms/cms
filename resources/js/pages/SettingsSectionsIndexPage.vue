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
  import type {PaginationData, SortItem} from '@/types';
  import SearchForm from '@/components/AdminTable/SearchForm.vue';
  import {useServerPagination} from '@/composables/useServerPagination';
  import {useServerSort} from '@/composables/useServerSort';

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
    readOnly: boolean;
  }>();

  const searchTerm = ref(props.searchTerm ?? '');
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
    },
    ...paginationConfig,
    ...sortingConfig,
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
          <SearchForm :action="index()" v-model="searchTerm" />
        </template>
      </AdminTable>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
