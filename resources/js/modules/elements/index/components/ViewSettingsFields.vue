<script setup lang="ts">
  /**
   * How an index shows its elements: view mode, sort, thumbnail size and table
   * columns, or just the `only` ones, so they can be split across menus.
   */
  import {computed, ref, useId, watch} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import Select from '@/common/form/Select.vue';
  import CheckboxGroup from '@/common/form/CheckboxGroup.vue';
  import ViewSettingLabel from './ViewSettingLabel.vue';
  import ViewSettingRadios from './ViewSettingRadios.vue';
  import {viewModeLabel} from '../viewModeLabel';
  import {thumbSizeLabel} from '@/modules/elements/index/composables/useElementIndexThumbSize';
  import type {ViewSettingKind} from '@/modules/elements/index/composables/useElementIndex';
  import type {CheckboxOption} from '@/common/types';
  import type {
    ThumbSize,
    SortOption,
    ViewMode,
  } from '@/modules/elements/types/view-state';

  const props = defineProps<{
    /** The toggleable/reorderable table columns. */
    options: Array<CheckboxOption>;
    /** The sortable attributes for the "Sort by" select; an empty `value` means no sort. */
    sortOptions: Array<SortOption>;
    /** Whether the active sort has an intrinsic direction. */
    sortDirectionLocked?: boolean;
    /** The thumbnail sizes the active view mode offers, if any. */
    thumbSizeOptions?: Array<ThumbSize>;
    /** Whether the active view mode has table columns to choose. */
    showTableColumns?: boolean;
    /** The view modes the source offers, to choose between when there's more than one. */
    viewModes?: Array<ViewMode>;
    /** The settings to show; all of them when left out. */
    only?: ViewSettingKind[];
    /** Settings that differ from the source's, badged as edited. */
    changed?: ViewSettingKind[];
  }>();

  const sortField = defineModel<string>('sortField', {required: true});
  const sortDirection = defineModel<'asc' | 'desc'>('sortDirection', {
    required: true,
  });
  const sortDirectionId = `sort-direction-${useId()}`;
  const tableColumns = defineModel<Array<string>>('tableColumns', {
    required: true,
  });
  const thumbSize = defineModel<ThumbSize>('thumbSize', {default: 'sm'});
  const mode = defineModel<ViewMode['mode']>('mode');

  const emit = defineEmits<{
    (e: 'reorder', options: Array<CheckboxOption>): void;
  }>();

  const shows = (kind: ViewSettingKind) =>
    !props.only || props.only.includes(kind);
  const isChanged = (kind: ViewSettingKind) =>
    Boolean(props.changed?.includes(kind));

  const viewModeChoices = computed(() =>
    (props.viewModes ?? []).map((option) => ({
      value: option.mode,
      label: viewModeLabel(option),
      icon: option.icon,
    }))
  );

  const thumbSizeChoices = computed(() =>
    (props.thumbSizeOptions ?? []).map((value) => ({
      label: thumbSizeLabel(value),
      value,
    }))
  );

  /**
   * The columns as displayed — an open-time snapshot of the canonical order
   * (visible columns in the user's order, then the rest alphabetically; see
   * `useElementIndexColumns`). Mirroring Craft 5: the snapshot refreshes each
   * time the menu *opens*, so toggling checkboxes while it's open never
   * re-sorts rows out from under the pointer — a newly checked column stays
   * put here while appending to the table, and shows in its new position on
   * the next open.
   */
  const displayOptions = ref<Array<CheckboxOption>>([]);

  function takeSnapshot() {
    displayOptions.value = [...props.options];
  }

  takeSnapshot();

  // A source switch replaces the available columns entirely; refresh the
  // snapshot so it doesn't show the previous source's list. Watch the option
  // *values* rather than the array: `options` is a computed that gets a new
  // identity (and order) whenever a checkbox toggles, and re-snapshotting then
  // would re-sort the list mid-edit.
  watch(
    () =>
      props.options
        .map((option) => option.value)
        .sort()
        .join(','),
    () => takeSnapshot()
  );

  function onReorder(next: Array<CheckboxOption>) {
    // Keep showing the order the user just dragged to.
    displayOptions.value = next;
    emit('reorder', next);
  }

  function setSortDirection(direction: 'asc' | 'desc') {
    if (!props.sortDirectionLocked) {
      sortDirection.value = direction;
    }
  }

  defineExpose({takeSnapshot});
</script>

<template>
  <div class="flex flex-col gap-lg">
    <div v-if="shows('viewMode') && (viewModes?.length ?? 0) > 1">
      <ViewSettingLabel
        :label="t('View Mode')"
        :edited="isChanged('viewMode')"
      />
      <ViewSettingRadios
        :model-value="mode ?? ''"
        @update:model-value="mode = $event as ViewMode['mode']"
        :label="t('View Mode')"
        :options="viewModeChoices"
      />
    </div>
    <div v-if="shows('sort') && sortOptions.length">
      <ViewSettingLabel :label="t('Sort by')" :edited="isChanged('sort')" />
      <div class="flex items-end gap-md">
        <Select
          :label="t('Sort by')"
          label-sr-only
          v-model="sortField"
          :options="sortOptions"
        />
        <craft-button-group role="group" :aria-label="t('Sort Direction')">
          <craft-button
            type="button"
            :id="`${sortDirectionId}-asc`"
            icon="asc"
            value="asc"
            :aria-pressed="sortDirection === 'asc'"
            :variant="ButtonVariant.Fill"
            .active="sortDirection === 'asc'"
            .disabled="!sortField || sortDirectionLocked"
            @click="setSortDirection('asc')"
          ></craft-button>
          <craft-button
            type="button"
            :id="`${sortDirectionId}-desc`"
            icon="desc"
            value="desc"
            :aria-pressed="sortDirection === 'desc'"
            :variant="ButtonVariant.Fill"
            .active="sortDirection === 'desc'"
            .disabled="!sortField || sortDirectionLocked"
            @click="setSortDirection('desc')"
          ></craft-button>
        </craft-button-group>
        <!-- Name the icon-only buttons. -->
        <craft-tooltip :for="`${sortDirectionId}-asc`" invoker-relation="label">
          {{ t('Ascending') }}
        </craft-tooltip>
        <craft-tooltip
          :for="`${sortDirectionId}-desc`"
          invoker-relation="label"
        >
          {{ t('Descending') }}
        </craft-tooltip>
      </div>
    </div>
    <div v-if="shows('thumbSize') && thumbSizeChoices.length">
      <ViewSettingLabel
        :label="t('Thumb Size')"
        :edited="isChanged('thumbSize')"
      />
      <ViewSettingRadios
        v-model="thumbSize"
        :label="t('Thumb Size')"
        :options="thumbSizeChoices"
      />
    </div>
    <div v-if="shows('columns') && showTableColumns">
      <ViewSettingLabel
        :label="t('Table Columns')"
        :edited="isChanged('columns')"
      />
      <CheckboxGroup
        :aria-label="t('Table Columns')"
        name="viewState[tableColumns][]"
        v-model="tableColumns"
        :options="displayOptions"
        sortable
        @update:options="onReorder"
      />
    </div>
  </div>
</template>
