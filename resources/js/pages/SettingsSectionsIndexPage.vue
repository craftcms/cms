<script setup lang="ts">
  import {
    createColumnHelper,
    getCoreRowModel,
    type PaginationState,
    useVueTable,
  } from '@tanstack/vue-table';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {computed, h, ref} from 'vue';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import DeleteSectionButton from '@/components/sections/DeleteSectionButton.vue';
  import {create, edit, index} from '@actions/Settings/SectionsController';
  import {router} from '@inertiajs/vue3';
  import AppLayout from '@/layout/AppLayout.vue';
  import Pane from '@/components/Pane.vue';

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

  const props = defineProps<{
    title: string;
    data: Array<SectionModel>;
    pagination: PaginationData;
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
  const pagination = ref<PaginationState>({
    pageIndex: pageIndex.value,
    pageSize: props.pagination.per_page,
  });
  const sectionTable = useVueTable({
    get data() {
      return props.data;
    },
    get columns() {
      return columns.value;
    },
    getCoreRowModel: getCoreRowModel<SectionModel>(),
    manualPagination: true,
    rowCount: props.pagination.total,
    state: {
      get pagination() {
        return pagination.value;
      },
    },

    onPaginationChange: (updater) => {
      const next =
        typeof updater === 'function' ? updater(pagination.value) : updater;

      // Inertia visit instead of fetch — triggers a server roundtrip
      router.visit(
        index({
          query: {
            page: next.pageIndex + 1,
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
      <a :href="create().url">
        <craft-icon name="plus" slot="prefix"></craft-icon>
        {{ t('New section') }}
      </a>
    </template>

    <Pane :padding="0" appearance="raised">
      <AdminTable
        spacing="relaxed"
        :table="sectionTable"
        :reorderable="false"
      />
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
