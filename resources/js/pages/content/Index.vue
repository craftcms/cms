<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';
  import ElementSources from '@/modules/elements/ElementSources.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {useServerPagination} from '@/modules/admin-table/composables/useServerPagination';
  import {useServerSort} from '@/modules/admin-table/composables/useServerSort';
  import {router} from '@inertiajs/vue3';
  import {ref} from 'vue';
  import SearchForm from '@/modules/admin-table/components/SearchForm.vue';
  import type {PaginationData, SortItem} from '@/common/types';

  type Source = any;

  interface EntryElement {
    id: number;
    title: string | null;
    slug: string | null;
    uri: string | null;
    url: string | null;
    status: string | null;
    enabled: boolean;
    cpEditUrl: string | null;
    dateCreated: string | null;
    dateUpdated: string | null;
    attributes?: Record<string, string>;
  }

  const props = withDefaults(
    defineProps<{
      sources: Array<Source>;
      sectionHandle?: string;
      page: string;
      source?: Source | null;
      elements: Array<EntryElement>;
      pagination: PaginationData;
      sort: Array<SortItem>;
    }>(),
    {source: null}
  );
  const searchTerm = ref('');

  const {paginationState, paginationConfig} = useServerPagination({
    initialState: props.pagination,
    onChange: ({query}) => {
      router.visit(window.location.pathname, {
        data: query,
        only: ['elements', 'pagination'],
        preserveScroll: true,
      });
    },
  });

  const {sortingState, sortingConfig} = useServerSort({
    initialState: props.sort,
    onChange: ({query}) => {
      router.visit(window.location.pathname, {
        data: query,
        only: ['elements', 'pagination', 'sort'],
        preserveScroll: true,
      });
    },
  });

  const columnHelper = createCraftColumnHelper<EntryElement>();
  const elementTable = useVueTable({
    get data() {
      return props.elements ?? [];
    },
    get columns() {
      return [
        columnHelper.link('title', {
          header: t('Title'),
        }),
        columnHelper.accessor('slug', {
          header: t('Slug'),
        }),
        columnHelper.accessor('dateCreated', {
          header: t('Date Created'),
        }),
        columnHelper.accessor('dateUpdated', {
          header: t('Date Updated'),
        }),
        columnHelper.accessor('status', {
          header: t('Status'),
        }),
      ];
    },
    state: {
      get pagination() {
        return paginationState.value;
      },
      get sorting() {
        return sortingState.value;
      },
      get columnVisibility() {
        return {
          title: true,
          slug: true,
          dateCreated: true,
          dateUpdated: true,
          status: true,
        };
      },
    },
    getCoreRowModel: getCoreRowModel<EntryElement>(),
    ...paginationConfig,
    ...sortingConfig,
  });
</script>

<template>
  <IndexLayout>
    <template #interior-nav>
      <nav aria-labelledby="source-heading">
        <h2 id="source-heading" class="sr-only">
          {{ t('Sources') }}
        </h2>
        <ElementSources :sources="sources" :active-source="source?.key" />
      </nav>

      <div id="source-actions"></div>
    </template>

    <AdminTable
      :table="elementTable"
      :from="pagination.from"
      :to="pagination.to"
      :total="pagination.total"
    >
      <template #search-form>
        <SearchForm v-model="searchTerm" />
      </template>
    </AdminTable>
  </IndexLayout>
</template>

<style scoped lang="scss"></style>
