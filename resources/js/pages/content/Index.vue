<script setup lang="ts">
  import {t, Appearance} from '@craftcms/cp';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';
  import {router, useForm} from '@inertiajs/vue3';
  import ElementSources from '@/modules/elements/ElementSources.vue';
  import type {Source} from '@/modules/elements/types/sources';
  import CraftSelectRich from '@craftcms/cp/vue/CraftSelectRich.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import {index} from '@/routes/craft/cp/content/index.js';
  import ElementStatus from '@/modules/elements/ElementStatus.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {computed, onMounted, ref, watch} from 'vue';
  import CheckboxGroup from '@/common/form/CheckboxGroup.vue';
  import Select from '@/common/form/Select.vue';
  import {useLocalStorage} from '@/common/composables/useStorage';
  import type {PaginationData, SortItem} from '@/common/types';
  import {useServerSort} from '@/modules/admin-table/composables/useServerSort';

  type Element = Record<any, any>;
  type ViewMode = {
    mode: 'table' | 'cards' | 'structure';
    title: string;
    icon: string;
    structuresOnly?: boolean;
    availableOnMobile?: boolean;
  };

  /**
   * @TODO should inlineEditing and static be a "mode"?
   */
  type ViewState = {
    inlineEditing: boolean;
    mode: ViewMode['mode'];
    tableColumns: Array<string>;
    nestedInputNamespace?: string | null;
    showHeaderColumn: boolean;
    sort: Array<SortItem>;
    static: boolean;
  };

  type SortOption = {
    label: string;
    attr: string;
    defaultDir: 'asc' | 'desc';
  };

  const props = withDefaults(
    defineProps<{
      elementType: string;
      elementDisplayName: string;
      elementPluralDisplayName: string;
      context?: string;
      canHaveDrafts?: boolean;
      criteria?: Record<string, any>;
      page: string;
      sources: Array<Source>;
      source?: Source;
      contentHtml?: string;
      search?: string | null;
      status: string;
      viewMode?: string | null;
      statusOptions?: Array<{label: string; value: string}>;
      sectionHandle?: string | number;
      viewState: Partial<ViewState>;
      data: Array<Element>;
      tableColumns: Record<string, {label: string}>;
      viewModes?: Array<ViewMode>;
      baseSortOptions: Array<SortOption>;
      pagination?: PaginationData;
      sort: Array<SortItem>;
    }>(),
    {
      context: 'index',
      canHaveDrafts: false,
      criteria: () => Craft.defaultIndexCriteria,
      defaultSource: null,
      defaultSourcePath: null,
    }
  );

  const initialViewState: ViewState = {
    inlineEditing: false,
    mode: 'table',
    tableColumns: ['title', 'dateCreated'],
    nestedInputNamespace: null,
    showHeaderColumn: true,
    sort: props.sort ?? [
      {
        field: 'dateCreated',
        direction: 'desc',
      },
    ],
    static: false,
    ...props.viewState,
  };

  const viewState = useLocalStorage<ViewState>(
    `elementindex.${props.elementType}.${props.context}`,
    initialViewState
  );

  const searchForm = useForm({
    search: props.search ?? '',
    status: props.status,
    sort: props.sort ?? viewState.value.sort,
    viewMode: viewState.value.mode ?? props.viewMode ?? 'table',
  });

  function handleSearch() {
    searchForm
      // Carry the persisted view mode along with the filter submit.
      .transform((data) => ({
        ...data,
        viewMode: viewState.value.mode,
      }))
      .submit(
        index({
          page: props.page ?? '',
          sectionHandle: props.sectionHandle ?? undefined,
        })
      );
  }

  const columnHelper = createCraftColumnHelper<Element>();
  const columns = computed(() => [
    columnHelper.html('title', {
      header: t('Entry'),
    }),

    ...Object.entries(props.tableColumns)
      .filter(([key]) => viewState.value.tableColumns?.includes(key))
      .map(([key, value]) => {
        return columnHelper.html(key, {
          header: value.label,
        });
      }),
  ]);

  const {sortingState, sortingConfig, onSortingChange} = useServerSort({
    initialState: searchForm.sort,
    onChange: ({query}) => {
      router.visit(
        index(
          {
            page: props.page ?? '',
            sectionHandle: props.sectionHandle ?? undefined,
          },
          {query}
        ),
        {
          only: ['data', 'sort'],
          preserveState: true,
          preserveScroll: true,
        }
      );
    },
  });

  function sortItemsToQuery(items: Array<SortItem>) {
    return items.reduce<Record<string, {field: string; direction: string}>>(
      (acc, item) => {
        acc[0] = {field: item.field, direction: item.direction};
        return acc;
      },
      {}
    );
  }

  // The URL is the source of truth: whenever the server confirms a sort (after
  // a sort-driven visit), mirror it into the table state, the search form, and
  // local storage so the choice is remembered on the user's next visit.
  watch(
    () => props.sort,
    (sort) => {
      const next = sort ?? [];
      sortingState.value = next.map((item) => ({
        id: item.field,
        desc: item.direction === 'desc',
      }));
      searchForm.sort = next;
      viewState.value.sort = next;
    }
  );

  // On load, if the URL doesn't specify a sort but we have one persisted from a
  // previous visit, restore it into the URL (without adding a history entry).
  onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const persisted = viewState.value.sort;

    if (params.has('sort') || !persisted?.length) {
      return;
    }

    if (JSON.stringify(persisted) === JSON.stringify(props.sort)) {
      return;
    }

    router.visit(
      index(
        {
          page: props.page ?? '',
          sectionHandle: props.sectionHandle ?? undefined,
        },
        {
          query: {
            ...Object.fromEntries(params),
            sort: sortItemsToQuery(persisted),
          },
        }
      ),
      {only: ['data', 'sort'], preserveScroll: true, replace: true}
    );
  });

  // Two-way bindings for the single-column sort controls in the "View" popover.
  // They read from and write through the same sorting state as the column
  // headers, so the popover, the headers, the URL, and local storage stay in
  // sync.
  const sortField = computed<string>({
    get: () => sortingState.value[0]?.id ?? 'title',
    set: (field) =>
      onSortingChange([
        {id: field, desc: sortingState.value[0]?.desc ?? false},
      ]),
  });

  const sortDirection = computed<'asc' | 'desc'>({
    get: () => (sortingState.value[0]?.desc ? 'desc' : 'asc'),
    set: (direction) =>
      onSortingChange([{id: sortField.value, desc: direction === 'desc'}]),
  });

  /**
   * A list of the available columns to be toggled on and off. Returned
   * with the title first. The other options are ordered by if they are checked
   * or not.
   */
  const tableColumnOptions = computed(() => {
    return [
      {
        label: t('Entry'),
        value: 'title',
        disabled: true,
      },
      ...Object.entries(props.tableColumns).map(([key, value]) => ({
        label: value.label,
        value: key,
      })),
    ];
  });

  const sortedColumnOptions = computed(() => {
    const checked = viewState.value.tableColumns ?? [];
    return [
      ...tableColumnOptions.value.filter((option) =>
        checked.includes(option.value)
      ),
      ...tableColumnOptions.value.filter(
        (option) => !checked.includes(option.value)
      ),
    ];
  });

  const visibleColumns = ref({});
  const elementTable = useVueTable<Element>({
    get data() {
      return props.data;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get columnOrder() {
        return ['title', 'dateCreated'];
      },
      get columnVisibility() {
        return visibleColumns.value;
      },
      get sorting() {
        return sortingState.value;
      },
    },
    getCoreRowModel: getCoreRowModel<Element>(),
    ...sortingConfig,
    enableMultiSort: false,
  });

  function closePopover(event: MouseEvent) {
    (event.currentTarget as HTMLElement).dispatchEvent(
      new Event('close-overlay', {bubbles: true, composed: true})
    );
  }
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

    <AdminTable :table="elementTable">
      <template #table-header>
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
              v-model="searchForm.search"
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

            <craft-button-group
              name="viewState[mode]"
              v-model="viewState.mode"
              @change="
                (event: CustomEvent) => (viewState.mode = event.detail.value)
              "
            >
              <template v-for="mode in viewModes" :key="mode.mode">
                <craft-button
                  type="button"
                  :appearance="Appearance.Fill"
                  :icon="mode.icon"
                  :aria-label="mode.title"
                  :active="viewState.mode === mode.mode"
                  :value="mode.mode"
                ></craft-button>
              </template>
            </craft-button-group>

            <craft-popover>
              <craft-button
                type="button"
                slot="invoker"
                icon="sliders"
                :appearance="Appearance.Fill"
              >
                {{ t('View') }}
              </craft-button>

              <div slot="content-body" class="gap-4">
                <div>
                  <div class="flex items-end gap-2">
                    <Select
                      :label="t('Sort by')"
                      v-model="sortField"
                      :options="tableColumnOptions"
                    />
                    <craft-button-group
                      name="viewState[sort][0][direction]"
                      v-model="viewState.sort[0]!.direction"
                      @change="
                        (event: CustomEvent) =>
                          (sortDirection = event.detail.value)
                      "
                    >
                      <craft-button
                        type="button"
                        icon="asc"
                        value="asc"
                        :aria-label="t('Sort ascending')"
                        :appearance="Appearance.Fill"
                        :active="sortDirection === 'asc'"
                      ></craft-button>
                      <craft-button
                        type="button"
                        icon="desc"
                        :aria-label="t('Sort descending')"
                        value="desc"
                        :appearance="Appearance.Fill"
                        :active="sortDirection === 'desc'"
                      ></craft-button>
                    </craft-button-group>
                  </div>
                </div>
                <div>
                  <CheckboxGroup
                    :label="t('Table Columns')"
                    name="viewState[tableColumns][]"
                    v-model="viewState.tableColumns"
                    :options="sortedColumnOptions"
                    allow-select-all
                  />
                </div>
              </div>
              <div slot="content-footer">
                <craft-button
                  type="button"
                  @click="closePopover"
                  :appearance="Appearance.Fill"
                  >{{ t('Close') }}</craft-button
                >
              </div>
            </craft-popover>

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
