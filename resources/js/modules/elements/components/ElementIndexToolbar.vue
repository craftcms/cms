<script setup lang="ts">
  import {Appearance, t} from '@craftcms/ui';
  import CraftSelectRich from '@craftcms/ui/vue/CraftSelectRich.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import ElementStatus from '@/modules/elements/ElementStatus.vue';
  import IndexViewSettings from '@/modules/elements/components/IndexViewSettings.vue';
  import type {CheckboxOption} from '@/common/types';
  import type {SortOption, ViewMode} from '@/modules/elements/types/view-state';
  import FilterHud from './FilterHud.vue';
  import type {ConditionConfig} from '@/modules/elements/composables/useConditionBuilder';
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
</script>

<template>
  <form @submit="emit('submit')" class="w-full">
    <div class="flex gap-2 items-center">
      <div v-if="statusOptions?.length">
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

      <div class="relative flex-1">
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
          @close="filterActive = false"
          @apply="emit('submit')"
          v-model="conditions"
        />
      </div>

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

      <div>
        <craft-button type="submit" :loading="processing">{{
          t('Update')
        }}</craft-button>
      </div>
    </div>
  </form>
</template>
