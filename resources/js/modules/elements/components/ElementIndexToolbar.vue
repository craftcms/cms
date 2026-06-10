<script setup lang="ts">
  import {Appearance, t} from '@craftcms/cp';
  import CraftSelectRich from '@craftcms/cp/vue/CraftSelectRich.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import ElementStatus from '@/modules/elements/ElementStatus.vue';
  import IndexViewSettings from '@/modules/elements/components/IndexViewSettings.vue';
  import type {CheckboxOption} from '@/common/types';
  import type {ViewMode} from '@/modules/elements/types/view-state';

  defineProps<{
    statusOptions?: Array<{label: string; value: string}>;
    viewModes?: Array<ViewMode>;
    columnOptions: Array<CheckboxOption>;
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

  const emit = defineEmits<{
    (e: 'submit'): void;
    (e: 'reorder', options: Array<CheckboxOption>): void;
  }>();
</script>

<template>
  <form @submit="emit('submit')" class="w-full">
    <div class="flex gap-2 items-center">
      <div>
        <CraftSelectRich
          v-model="status"
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
        v-model="search"
        label-sr-only
      >
        <craft-button
          type="button"
          slot="suffix"
          icon
          size="small"
          appearance="plain"
        >
          <craft-icon name="filter" :label="t('Filter results')"></craft-icon>
        </craft-button>
      </CraftInput>

      <craft-button-group
        name="viewState[mode]"
        @change="(event: CustomEvent) => (mode = event.detail.value)"
      >
        <template v-for="viewMode in viewModes" :key="viewMode.mode">
          <craft-button
            type="button"
            :appearance="Appearance.Fill"
            :icon="viewMode.icon"
            :aria-label="viewMode.title"
            :active="mode === viewMode.mode"
            :value="viewMode.mode"
          ></craft-button>
        </template>
      </craft-button-group>

      <IndexViewSettings
        :options="columnOptions"
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
