<script setup lang="ts">
  import {Appearance, t} from '@craftcms/cp';
  import Select from '@/common/form/Select.vue';
  import CheckboxGroup from '@/common/form/CheckboxGroup.vue';
  import type {CheckboxOption} from '@/common/types';
  import type {SortOption} from '@/modules/elements/types/view-state';

  defineProps<{
    /** The toggleable/reorderable table columns. */
    options: Array<CheckboxOption>;
    /** The sortable attributes for the "Sort by" select. */
    sortOptions: Array<SortOption>;
  }>();

  const sortField = defineModel<string>('sortField', {required: true});
  const sortDirection = defineModel<'asc' | 'desc'>('sortDirection', {
    required: true,
  });
  const tableColumns = defineModel<Array<string>>('tableColumns', {
    required: true,
  });

  const emit = defineEmits<{
    (e: 'reorder', options: Array<CheckboxOption>): void;
  }>();

  function closePopover(event: MouseEvent) {
    (event.currentTarget as HTMLElement).dispatchEvent(
      new Event('close-overlay', {bubbles: true, composed: true})
    );
  }
</script>

<template>
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
            :options="sortOptions"
          />
          <craft-button-group
            @change="
              (event: CustomEvent) => (sortDirection = event.detail.value)
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
              value="desc"
              :aria-label="t('Sort descending')"
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
          v-model="tableColumns"
          :options="options"
          sortable
          @update:options="(next) => emit('reorder', next)"
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
</template>
