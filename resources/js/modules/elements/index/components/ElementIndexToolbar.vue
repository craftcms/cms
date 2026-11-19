<script setup lang="ts">
  import {Appearance, t} from '@craftcms/ui';
  import CraftSelectRich from '@craftcms/ui/vue/CraftSelectRich.vue';
  import CraftInputSearch from '@craftcms/ui/vue/CraftInputSearch.vue';
  import ElementStatus from '@/modules/elements/ElementStatus.vue';
  import ViewSettingsMenu from '@/modules/elements/index/components/ViewSettingsMenu.vue';
  import type {CheckboxOption} from '@/common/types';
  import type {
    ThumbSize,
    SortOption,
    ViewMode,
  } from '@/modules/elements/types/view-state';
  import FilterHud from './FilterHud.vue';
  import type {ViewSettingKind} from '@/modules/elements/index/composables/useElementIndex';
  import type {ConditionConfig} from '@/modules/conditions/types';
  import {rootGroup} from '@/modules/conditions/conditionTree';
  import type {IndexSite} from '@/modules/elements/types/sites';
  import {computed, ref} from 'vue';

  const props = withDefaults(
    defineProps<{
      statusOptions?: Array<{
        label: string;
        value: string;
        icon?: string;
        color?: string;
      }>;
      viewModes?: Array<ViewMode>;
      columnOptions: Array<CheckboxOption>;
      /** The thumbnail sizes the active view mode offers. */
      thumbSizeOptions?: Array<ThumbSize>;
      sortOptions: Array<SortOption>;
      /** Which view settings differ from the source's, view mode included. */
      viewChangedSettings?: ViewSettingKind[];
      processing?: boolean;
      /**
       * The sites this index can be switched between.
       *
       * Only an index with nowhere else to put the menu passes these — the
       * element selector modal. A full index page has a header, and puts its site
       * menu in the crumbs there, the same way the legacy index does.
       */
      sites?: Array<IndexSite>;
      /** The active site's handle, when `sites` are being offered. */
      siteHandle?: string | null;
      asForm?: boolean;
      /**
       * Whether the filters differ from where they started. By default, any
       * filter counts.
       */
      filtersEdited?: boolean;
    }>(),
    {asForm: true, filtersEdited: undefined}
  );

  const search = defineModel<string>('search', {required: true});
  const status = defineModel<string>('status', {required: true});
  const mode = defineModel<ViewMode['mode']>('mode', {required: true});
  const sortField = defineModel<string>('sortField', {required: true});
  const sortDirection = defineModel<'asc' | 'desc'>('sortDirection', {
    required: true,
  });
  const tableColumns = defineModel<Array<string>>('tableColumns', {
    required: true,
  });
  const thumbSize = defineModel<ThumbSize>('thumbSize', {required: true});
  const conditions = defineModel<ConditionConfig | null>('conditions', {
    required: true,
  });

  const emit = defineEmits<{
    (e: 'submit'): void;
    (e: 'reorder', options: Array<CheckboxOption>): void;
    (e: 'use-default-view', kinds: ViewSettingKind[]): void;
    (e: 'site-change', handle: string): void;
  }>();

  const filterActive = ref(false);
  /** How many filters are applied: the condition's top-level rules and groups. */
  const filterCount = computed(() => rootGroup(conditions.value).rules.length);
  const showFilterIndicator = computed(
    () => props.filtersEdited ?? filterCount.value > 0
  );
  const filterButton = ref<HTMLElement>();

  // One site is no choice at all, so the menu only earns its place from two.
  const showSiteMenu = computed(() => (props.sites?.length ?? 0) > 1);

  function statusOptionIcon(value: string | number): string | undefined {
    return props.statusOptions?.find((option) => option.value === value)?.icon;
  }

  function statusOptionColor(value: string | number): string | undefined {
    return props.statusOptions?.find((option) => option.value === value)?.color;
  }

  const siteOptions = computed(() =>
    (props.sites ?? []).map((site) => ({label: site.name, value: site.handle}))
  );

  function onSearchEnter(event: KeyboardEvent): void {
    if (props.asForm !== false) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    emit('submit');
  }
</script>

<template>
  <component
    :is="asForm === false ? 'div' : 'form'"
    class="w-full"
    @submit.prevent="emit('submit')"
  >
    <div
      :class="{
        'element-toolbar': true,
        'element-toolbar--actions': !!$slots.actions,
      }"
    >
      <div
        v-if="showSiteMenu || statusOptions?.length"
        class="element-toolbar__status"
      >
        <div v-if="showSiteMenu" class="element-toolbar__site">
          <CraftSelectRich
            :model-value="siteHandle ?? undefined"
            :options="siteOptions"
            :label="t('Site')"
            label-sr-only
            .disabled="processing"
            @change="
              (event: Event) =>
                emit(
                  'site-change',
                  (event.currentTarget as unknown as {value: string}).value
                )
            "
          >
            <template #option="{option}">
              <!-- The field shows a copy of this option, out of reach of
                utility classes, so the layout is inline and the icon's name
                is an attribute the copy keeps. -->
              <span
                style="
                  display: inline-flex;
                  align-items: center;
                  gap: var(--c-spacing-sm);
                "
              >
                <craft-icon :name.attr="'world'"></craft-icon>
                {{ option.label }}
              </span>
            </template>
          </CraftSelectRich>
        </div>

        <CraftSelectRich
          v-if="statusOptions?.length"
          v-model="status"
          :options="statusOptions"
          :label="t('Status')"
          label-sr-only
          .disabled="processing"
          @change="emit('submit')"
        >
          <template #option="{option}">
            <span
              v-if="statusOptionIcon(option.value)"
              style="
                display: inline-flex;
                align-items: center;
                gap: var(--c-spacing-sm);
              "
            >
              <craft-icon
                :name.attr="statusOptionIcon(option.value)"
              ></craft-icon>
              {{ option.label }}
            </span>
            <ElementStatus
              v-else
              :label="option.label"
              :value="option.value"
              :color="statusOptionColor(option.value)"
            />
          </template>
        </CraftSelectRich>
      </div>

      <div class="element-toolbar__filter">
        <CraftInputSearch
          class="element-toolbar__search"
          name="search"
          v-model="search"
          @keydown.enter="onSearchEnter"
        />

        <slot name="search-options"></slot>
      </div>

      <div class="element-toolbar__state">
        <div class="flex gap-md justify-end">
          <craft-button
            ref="filterButton"
            type="button"
            :variant="Appearance.Fill"
            .disabled="processing"
            :aria-expanded="filterActive"
            @click="filterActive = !filterActive"
            icon="filter"
          >
            {{ t('Filter') }}
            <craft-badge-indicator
              v-if="showFilterIndicator"
              slot="suffix"
              style="margin-inline-start: var(--c-spacing-xs)"
              :alt-text="t('Filters applied')"
            ></craft-badge-indicator>
          </craft-button>

          <FilterHud
            v-if="filterActive && filterButton"
            :anchor="filterButton"
            align="end"
            @close="filterActive = false"
            @apply="emit('submit')"
            v-model="conditions"
          />

          <!-- The order the elements come in, then how they're shown. -->
          <ViewSettingsMenu
            v-for="menu in ['sort', 'settings'] as const"
            :key="menu"
            :menu="menu"
            :view-modes="viewModes"
            :column-options="columnOptions"
            :sort-options="sortOptions"
            :thumb-size-options="thumbSizeOptions"
            :search="search"
            :changed="viewChangedSettings ?? []"
            :disabled="processing"
            v-model:mode="mode"
            v-model:sort-field="sortField"
            v-model:sort-direction="sortDirection"
            v-model:table-columns="tableColumns"
            v-model:thumb-size="thumbSize"
            @reorder="(options) => emit('reorder', options)"
            @use-defaults="(kinds) => emit('use-default-view', kinds)"
          />
        </div>
      </div>

      <!-- The index's own actions: the entries index puts its New Entry button
        here. Search still submits on Enter — the field is the form's only text
        input, so the browser submits it implicitly. -->
      <div v-if="$slots.actions" class="element-toolbar__actions">
        <slot name="actions"></slot>
      </div>
    </div>
  </component>
</template>

<style scoped lang="postcss">
  .element-toolbar {
    display: grid;
    gap: var(--c-spacing-md);
    justify-content: space-between;
    grid-template-columns: minmax(0, 1fr);
    grid-template-areas: 'status' 'filter' 'state';

    @container cp-content-view (width >= 480px) {
      gap: var(--c-spacing-md);
      grid-template-columns: auto minmax(0, 1fr) auto;
      grid-template-areas: 'status filter state';
    }
  }

  /* Only reserve room for actions when there are some. An empty track would
     still take a gap, leaving a hole at the end of the row. */
  .element-toolbar--actions {
    grid-template-areas: 'status' 'filter' 'state' 'actions';

    @container cp-content-view (width >= 480px) {
      grid-template-areas: 'status filter state' 'actions actions actions';
    }

    @container cp-content-view (width >= var(--breakpoint-sm)) {
      grid-template-columns: auto minmax(0, 1fr) auto auto;
      grid-template-areas: 'status filter state actions';
    }
  }

  .element-toolbar > :first-child {
    grid-column-start: status-start;
  }

  /* Site and status share a cell rather than the grid gaining a column, so
     the breakpoint ladder above stays the one definition of the layout. */
  .element-toolbar__status {
    grid-area: status;
    display: flex;
    gap: var(--c-spacing-sm);
  }

  .element-toolbar__state {
    grid-area: state;
    min-width: 0;

    @container cp-content-view (width < 480px) {
      justify-self: start;
    }
  }

  .element-toolbar__actions {
    grid-area: actions;
  }

  .element-toolbar__filter {
    grid-area: filter;
    display: flex;
    gap: var(--c-spacing-sm);
    align-items: center;
  }

  .element-toolbar__search {
    flex: 1;
    min-width: 0;
  }
</style>
