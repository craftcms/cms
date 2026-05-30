<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';
  import ElementSources from '@/modules/elements/ElementSources.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {useServerPagination} from '@/modules/admin-table/composables/useServerPagination';
  import {useServerSort} from '@/modules/admin-table/composables/useServerSort';
  import {router, useForm} from '@inertiajs/vue3';
  import {computed, h} from 'vue';
  import type {PaginationData, SortItem} from '@/common/types';
  import CraftSelectRich from '@craftcms/cp/vue/CraftSelectRich.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import {index} from '@/routes/craft/cp/content/index.js';
  import ElementStatus from '@/modules/elements/ElementStatus.vue';

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
      sectionHandle?: string | number;
      page: string;
      source?: Source | null;
      elements: Array<EntryElement>;
      pagination: PaginationData;
      elementStatuses: Record<string, string>;
      statusOptions: Array<{label: string; value: string}>;
      sort: Array<SortItem>;
      q?: string | null;
      status?: string | null;
      viewMode?: string | null;
    }>(),
    {
      source: null,
      viewMode: 'table',
      q: null,
      status: '',
    }
  );

  const statusOptions = computed(() => {
    return Object.entries({all: 'All', ...props.elementStatuses}).map(
      ([key, value]) => {
        if (key === 'all') {
          return {
            label: t('All'),
            value: '',
          };
        }

        return {
          label: value,
          value: key,
        };
      }
    );
  });

  const {paginationState, paginationConfig} = useServerPagination({
    initialState: props.pagination,
    onChange: ({query}) => {
      router.visit(
        index({page: props.page, sectionHandle: props.sectionHandle}),
        {
          data: query,
          only: ['elements', 'pagination'],
          preserveScroll: true,
        }
      );
    },
  });

  const {sortingState, sortingConfig} = useServerSort({
    initialState: props.sort,
    onChange: ({query}) => {
      router.visit(
        index({page: props.page, sectionHandle: props.sectionHandle}),
        {
          data: query,
          only: ['elements', 'pagination', 'sort'],
          preserveScroll: true,
        }
      );
    },
  });

  const searchForm = useForm({
    q: props.q ?? '',
    status: props.status,
    viewMode: props.viewMode,
  });

  function handleSearch() {
    searchForm.submit(
      index({page: props.page, sectionHandle: props.sectionHandle})
    );
  }

  const columnHelper = createCraftColumnHelper<EntryElement>();
  const elementTable = useVueTable({
    get data() {
      return props.elements ?? [];
    },
    get columns() {
      return [
        columnHelper.link('title', {
          header: t('Title'),
          props: ({row}) => ({
            href: row.original.cpEditUrl,
          }),
        }),
        columnHelper.accessor('slug', {
          header: t('Slug'),
        }),
        columnHelper.date('dateCreated', {
          header: t('Date Created'),
        }),
        columnHelper.date('dateUpdated', {
          header: t('Date Updated'),
        }),
        columnHelper.accessor('status', {
          header: t('Status'),
          cell: ({getValue}) =>
            getValue()
              ? h(ElementStatus, {value: getValue(), mode: 'badge'})
              : 'N/A',
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
        <form @submit="handleSearch" class="w-full">
          <div class="flex gap-2 items-center">
            <div>
              <CraftSelectRich
                v-model="searchForm.status"
                :options="statusOptions"
                :label="t('Status')"
                label-sr-only
              >
                <template #option="{option}">
                  <ElementStatus :label="option.label" :value="option.value" />
                </template>
              </CraftSelectRich>
            </div>

            <CraftInput
              class="flex-1"
              name="search"
              :label="t('Search term')"
              v-model="searchForm.q"
              label-sr-only
            >
              <craft-button
                type="button"
                slot="suffix"
                icon
                size="small"
                appearance="plain"
              >
                <craft-icon
                  name="filter"
                  :label="t('Filter results')"
                ></craft-icon>
              </craft-button>
            </CraftInput>

            <craft-button-group v-model="searchForm.viewMode">
              <craft-button
                type="button"
                appearance="filled"
                icon="list"
                :aria-label="t('Display in a table')"
                value="table"
                :active="searchForm.viewMode === 'table'"
              ></craft-button>
              <craft-button
                type="button"
                appearance="filled"
                icon="custom-icons/element-cards"
                :aria-label="t('Display as cards')"
                value="cards"
                :active="searchForm.viewMode === 'cards'"
              ></craft-button>
            </craft-button-group>

            <craft-action-menu>
              <craft-button
                type="button"
                slot="invoker"
                icon="sliders"
                appearance="filled"
              >
                {{ t('View') }}
              </craft-button>

              <div slot="content">Hey</div>
            </craft-action-menu>

            <div>
              <craft-button type="submit" :loading="searchForm.processing">{{
                t('Update')
              }}</craft-button>
            </div>
          </div>
        </form>
      </template>
    </AdminTable>
  </IndexLayout>
</template>

<style scoped lang="scss"></style>
