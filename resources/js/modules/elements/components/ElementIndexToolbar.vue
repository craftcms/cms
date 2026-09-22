<script setup lang="ts">
  import {Appearance, t} from '@craftcms/ui';
  import CraftSelectRich from '@craftcms/ui/vue/CraftSelectRich.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import ElementStatus from '@/modules/elements/ElementStatus.vue';
  import IndexViewSettings from '@/modules/elements/components/IndexViewSettings.vue';
  import type {CheckboxOption} from '@/common/types';
  import type {SortOption, ViewMode} from '@/modules/elements/types/view-state';
  import FilterHud from './FilterHud.vue';
  import type {ConditionConfig} from '@/modules/conditions/types';
  import {ref} from 'vue';

  defineProps<{
    statusOptions?: Array<{label: string; value: string}>;
    viewModes?: Array<ViewMode>;
    columnOptions: Array<CheckboxOption>;
    sortOptions: Array<SortOption>;
    processing?: boolean;
  }>();

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
  const conditions = defineModel<ConditionConfig | null>('conditions', {
    required: true,
  });

  const emit = defineEmits<{
    (e: 'submit'): void;
    (e: 'reorder', options: Array<CheckboxOption>): void;
  }>();

  const filterActive = ref(false);
  const filterAnchor = ref<HTMLElement>();
</script>

<template>
  <form @submit="emit('submit')" class="w-full">
    <div class="element-toolbar">
      <div v-if="statusOptions?.length" class="element-toolbar__status">
        <CraftSelectRich
          v-model="status"
          :options="statusOptions"
          :label="t('Status')"
          label-sr-only
          @model-value-changed="
            (event: CustomEvent) => {
              if (event.detail.isTriggeredByUser) {
                emit('submit');
              }
            }
          "
        >
          <template #option="{option}">
            <ElementStatus :label="option.label" :value="option.value" />
          </template>
        </CraftSelectRich>
      </div>

      <div ref="filterAnchor" class="element-toolbar__filter">
        <CraftInput
          name="search"
          :label="t('Search term')"
          v-model="search"
          label-sr-only
        >
          <div slot="suffix" class="flex">
            <craft-button
              type="button"
              icon
              size="small"
              variant="plain"
              v-if="search"
              @click="search = ''"
            >
              <craft-icon name="x" :label="t('Clear search')"></craft-icon>
            </craft-button>
            <craft-button
              type="button"
              icon
              size="small"
              variant="plain"
              @click="filterActive = true"
              :class="{'is-active': !!conditions}"
            >
              <craft-icon
                name="filter"
                :label="t('Filter results')"
              ></craft-icon>
            </craft-button>
          </div>
        </CraftInput>

        <FilterHud
          v-if="filterActive"
          :anchor="filterAnchor"
          @close="filterActive = false"
          @apply="emit('submit')"
          v-model="conditions"
        />
      </div>

      <div class="element-toolbar__state">
        <div class="flex gap-sm justify-end">
          <craft-button-group
            name="viewState[mode]"
            .value="mode"
            @change="(event: CustomEvent) => (mode = event.detail.value)"
          >
            <template v-for="viewMode in viewModes" :key="viewMode.mode">
              <craft-button
                type="button"
                :variant="Appearance.Fill"
                :icon="viewMode.icon"
                :aria-label="viewMode.title"
                :value="viewMode.mode"
              ></craft-button>
            </template>
          </craft-button-group>

          <IndexViewSettings
            :options="columnOptions"
            :sort-options="sortOptions"
            v-model:sort-field="sortField"
            v-model:sort-direction="sortDirection"
            v-model:table-columns="tableColumns"
            @reorder="(options) => emit('reorder', options)"
          />
        </div>
      </div>

      <!-- The index's own actions: the entries index puts its New Entry button
        here. Search still submits on Enter — the field is the form's only text
        input, so the browser submits it implicitly. -->
      <div class="element-toolbar__actions">
        <slot name="actions"></slot>
      </div>
    </div>
  </form>
</template>

<style scoped lang="postcss">
  .element-toolbar {
    display: grid;
    gap: var(--c-spacing-md) 0;
    justify-content: space-between;
    grid-template-columns: repeat(3, auto);
    grid-template-areas: 'status state state' 'filter filter filter' 'actions actions actions';

    @media screen and (min-width: 480px) {
      gap: var(--c-spacing-md);
      grid-template-columns: auto minmax(0, 1fr) auto;
      grid-template-areas: 'status filter state' 'actions actions actions';
    }

    @media (width >= var(--breakpoint-sm)) {
      gap: var(--c-spacing-md);
      grid-template-columns: auto minmax(0, 1fr) auto auto;
      grid-template-areas: 'status filter state actions';
    }
  }

  .element-toolbar__status {
    grid-area: status;
  }

  .element-toolbar__state {
    grid-area: state;
  }

  .element-toolbar__actions {
    grid-area: actions;
  }

  .element-toolbar__filter {
    grid-area: filter;
  }
</style>
