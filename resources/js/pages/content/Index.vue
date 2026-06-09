<script setup lang="ts">
  import {t, Appearance} from '@craftcms/cp';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';
  import {useForm} from '@inertiajs/vue3';
  import ElementSources from '@/modules/elements/ElementSources.vue';
  import type {Source} from '@/modules/elements/types/sources';
  import CraftSelectRich from '@craftcms/cp/vue/CraftSelectRich.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import {index} from '@/routes/craft/cp/content/index.js';
  import ElementStatus from '@/modules/elements/ElementStatus.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {computed, ref} from 'vue';
  import CheckboxGroup from '@/common/form/CheckboxGroup.vue';
  import Select from '@/common/form/Select.vue';
  import {useLocalStorage} from '@/common/composables/useStorage';
  import type {PaginationData, SortItem} from '@/common/types';

  type Element = Record<any, any>;
  type ViewMode = {
    type: 'table' | 'cards' | 'structure';
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
    mode: ViewMode['type'];
    tableColumns: Array<string>;
    nestedInputNamespace?: string | null;
    showHeaderColumn: boolean;
    order: string;
    sort: 'asc' | 'desc';
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
      criteria?: Record<any, any>;
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
      elements: Array<Element>;
      tableColumns: Record<string, {label: string}>;
      viewModes?: Array<ViewMode>;
      baseSortOptions: Array<SortOption>;
      pagination: PaginationData;
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
    order: 'dateCreated',
    sort: 'desc',
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
    viewMode: viewState.value.mode ?? props.viewMode ?? 'table',
  });

  function handleSearch() {
    searchForm.submit(
      index({page: props.page, sectionHandle: props.sectionHandle})
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

  const tableColumnOptions = computed(() => [
    {
      label: t('Entry'),
      value: 'title',
      disabled: true,
    },
    ...Object.entries(props.tableColumns).map(([key, value]) => ({
      label: value.label,
      value: key,
    })),
  ]);

  // Persisted view state may not include tableColumns (older stored payloads),
  // so proxy it with a safe default for the CheckboxGroup's required string[].
  // const tableColumns = computed<string[]>({
  //   get: () => viewState.value.tableColumns ?? [],
  //   set: (columns) => {
  //     viewState.value.tableColumns = columns;
  //   },
  // });

  const visibleColumns = ref({});
  const elementTable = useVueTable<Element>({
    get data() {
      return props.elements;
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
    },
    getCoreRowModel: getCoreRowModel<Element>(),
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
              v-model="searchForm.viewMode"
              name="viewMode"
              @change="
                (event: CustomEvent) =>
                  (searchForm.viewMode = event.detail.value)
              "
            >
              <template v-for="mode in viewModes" :key="mode.type">
                <craft-button
                  type="button"
                  :appearance="Appearance.Fill"
                  :icon="mode.icon"
                  :aria-label="mode.title"
                  :active="searchForm.viewMode === mode.type"
                  :value="mode.type"
                ></craft-button>
              </template>
            </craft-button-group>

            <craft-action-menu>
              <craft-button
                type="button"
                slot="invoker"
                icon="sliders"
                :appearance="Appearance.Fill"
              >
                {{ t('View') }}
              </craft-button>

              <div slot="content">
                <div class="p-2">
                  <div class="flex items-end gap-2">
                    <Select
                      :label="t('Sort by')"
                      name="viewState[order]"
                      v-model="viewState.order"
                      :options="tableColumnOptions"
                    />
                    <craft-button-group
                      name="viewState[sort]"
                      v-model="viewState.sort"
                    >
                      <craft-button
                        type="button"
                        icon="asc"
                        value="asc"
                        aria-label="t('Sort ascending')"
                        :appearance="Appearance.Fill"
                        :active="viewState.sort === 'asc'"
                      ></craft-button>
                      <craft-button
                        type="button"
                        icon="desc"
                        aria-label="t('Sort descending')"
                        value="desc"
                        :appearance="Appearance.Fill"
                        :active="viewState.sort === 'desc'"
                      ></craft-button>
                    </craft-button-group>
                  </div>
                </div>
                <div class="p-2">
                  <CheckboxGroup
                    :label="t('Table Columns')"
                    name="viewState[tableColumns]"
                    v-model="viewState.tableColumns"
                    :options="tableColumnOptions"
                    allow-select-all
                  />
                </div>

                <div class="p-2"></div>
              </div>
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
    <!--<VarDump :data="$props" />-->
    <!--<div id="elements" v-if="contentHtml">-->
    <!--  <div class="p-1">-->
    <!--    <form @submit="handleSearch" class="w-full">-->
    <!--      <div class="flex gap-2 items-center">-->
    <!--        <div>-->
    <!--          <CraftSelectRich-->
    <!--            v-model="searchForm.status"-->
    <!--            :options="statusOptions"-->
    <!--            :label="t('Status')"-->
    <!--            label-sr-only-->
    <!--          >-->
    <!--            <template #option="{option}">-->
    <!--              <ElementStatus :label="option.label" :value="option.value" />-->
    <!--            </template>-->
    <!--          </CraftSelectRich>-->
    <!--        </div>-->

    <!--        <CraftInput-->
    <!--          class="flex-1"-->
    <!--          name="search"-->
    <!--          :label="t('Search term')"-->
    <!--          v-model="searchForm.search"-->
    <!--          label-sr-only-->
    <!--        >-->
    <!--          <craft-button-->
    <!--            type="button"-->
    <!--            slot="suffix"-->
    <!--            icon-->
    <!--            size="small"-->
    <!--            appearance="plain"-->
    <!--          >-->
    <!--            <craft-icon-->
    <!--              name="filter"-->
    <!--              :label="t('Filter results')"-->
    <!--            ></craft-icon>-->
    <!--          </craft-button>-->
    <!--        </CraftInput>-->

    <!--        <craft-button-group-->
    <!--          v-model="searchForm.viewMode"-->
    <!--          name="viewMode"-->
    <!--          @change="-->
    <!--            (event: CustomEvent) =>-->
    <!--              (searchForm.viewMode = event.detail.value)-->
    <!--          "-->
    <!--        >-->
    <!--          <craft-button-->
    <!--            type="button"-->
    <!--            :appearance="Appearance.Fill"-->
    <!--            icon="list"-->
    <!--            :aria-label="t('Display in a table')"-->
    <!--            :active="searchForm.viewMode === 'table'"-->
    <!--            value="table"-->
    <!--          ></craft-button>-->
    <!--          <craft-button-->
    <!--            type="button"-->
    <!--            :appearance="Appearance.Fill"-->
    <!--            icon="custom-icons/element-cards"-->
    <!--            :aria-label="t('Display as cards')"-->
    <!--            :active="searchForm.viewMode === 'cards'"-->
    <!--            value="cards"-->
    <!--          ></craft-button>-->
    <!--        </craft-button-group>-->

    <!--        <craft-action-menu>-->
    <!--          <craft-button-->
    <!--            type="button"-->
    <!--            slot="invoker"-->
    <!--            icon="sliders"-->
    <!--            :appearance="Appearance.Fill"-->
    <!--          >-->
    <!--            {{ t('View') }}-->
    <!--          </craft-button>-->

    <!--          <div slot="content">Hey</div>-->
    <!--        </craft-action-menu>-->

    <!--        <div>-->
    <!--          <craft-button type="submit" :loading="searchForm.processing">{{-->
    <!--            t('Update')-->
    <!--          }}</craft-button>-->
    <!--        </div>-->
    <!--      </div>-->
    <!--    </form>-->
    <!--  </div>-->

    <!--<DynamicHtmlRenderer :html="contentHtml" />-->
    <!--</div>-->
  </IndexLayout>
</template>

<style scoped lang="scss"></style>
