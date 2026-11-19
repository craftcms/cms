<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import IndexViewSettings from './IndexViewSettings.vue';
  import {
    VIEW_MENU_KINDS,
    type ViewSettingKind,
  } from '@/modules/elements/index/composables/useElementIndex';
  import {
    isSortDirectionLocked,
    visibleSortOptions,
  } from '@/modules/elements/index/sortOptions';
  import type {CheckboxOption} from '@/common/types';
  import type {
    SortOption,
    ThumbSize,
    ViewMode,
  } from '@/modules/elements/types/view-state';

  /** The Sort or Settings menu, wherever it opens from. */
  const props = defineProps<{
    menu: 'sort' | 'settings';
    viewModes?: ViewMode[];
    columnOptions: CheckboxOption[];
    sortOptions: SortOption[];
    thumbSizeOptions?: ThumbSize[];
    /** The index's search, which decides whether Score can sort. */
    search: string;
    /** Which view settings differ from the source's, in any menu. */
    changed: ViewSettingKind[];
    disabled?: boolean;
  }>();

  const mode = defineModel<ViewMode['mode']>('mode', {required: true});
  const sortField = defineModel<string>('sortField', {required: true});
  const sortDirection = defineModel<'asc' | 'desc'>('sortDirection', {
    required: true,
  });
  const tableColumns = defineModel<string[]>('tableColumns', {required: true});
  const thumbSize = defineModel<ThumbSize>('thumbSize', {required: true});

  const emit = defineEmits<{
    reorder: [options: CheckboxOption[]];
    'use-defaults': [kinds: ViewSettingKind[]];
  }>();

  const kinds = computed(() => VIEW_MENU_KINDS[props.menu]);
  const changedHere = computed(() =>
    props.changed.filter((kind) => kinds.value.includes(kind))
  );
</script>

<template>
  <IndexViewSettings
    :label="menu === 'sort' ? t('Sort') : t('Settings')"
    :icon="menu === 'sort' ? 'arrow-down-wide-short' : 'sliders'"
    :only="kinds"
    :view-modes="viewModes"
    :options="menu === 'settings' ? columnOptions : []"
    :sort-options="
      menu === 'sort' ? visibleSortOptions(sortOptions, search, sortField) : []
    "
    :sort-direction-locked="isSortDirectionLocked(sortField)"
    :thumb-size-options="thumbSizeOptions"
    :show-table-columns="mode === 'table' || mode === 'structure'"
    :disabled="disabled"
    v-model:mode="mode"
    v-model:sort-field="sortField"
    v-model:sort-direction="sortDirection"
    v-model:table-columns="tableColumns"
    v-model:thumb-size="thumbSize"
    :change-count="changedHere.length"
    :changed="changedHere"
    @reorder="(options) => emit('reorder', options)"
    @use-defaults="emit('use-defaults', kinds)"
  >
    <template v-if="$slots.invoker" #invoker>
      <slot name="invoker" />
    </template>
  </IndexViewSettings>
</template>
