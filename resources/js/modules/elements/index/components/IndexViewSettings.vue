<script setup lang="ts">
  import {computed, nextTick, ref, useId, useTemplateRef, watch} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import Select from '@/common/form/Select.vue';
  import CheckboxGroup from '@/common/form/CheckboxGroup.vue';
  import type {CheckboxOption} from '@/common/types';
  import type {
    ThumbSize,
    SortOption,
  } from '@/modules/elements/types/view-state';

  const props = defineProps<{
    /** The toggleable/reorderable table columns. */
    options: Array<CheckboxOption>;
    /** The sortable attributes for the "Sort by" select; an empty `value` means no sort. */
    sortOptions: Array<SortOption>;
    /** Whether the active sort has an intrinsic direction. */
    sortDirectionLocked?: boolean;
    disabled?: boolean;
    /**
     * The thumbnail sizes the active view mode offers. The Thumb Size setting
     * only shows
     * when there are some.
     */
    thumbSizeOptions?: Array<ThumbSize>;
    /** Whether the active view mode has table columns to choose. */
    showTableColumns?: boolean;
    /** Whether any setting differs from its default, offering to reset them. */
    customized?: boolean;
  }>();

  const sortField = defineModel<string>('sortField', {required: true});
  const sortDirection = defineModel<'asc' | 'desc'>('sortDirection', {
    required: true,
  });
  const tableColumns = defineModel<Array<string>>('tableColumns', {
    required: true,
  });

  const thumbSize = defineModel<ThumbSize>('thumbSize', {default: 'sm'});

  // Unique per popover, since an index can share the page with another (e.g.
  // an element selection modal).
  const thumbSizeIdPrefix = `thumb-size-${useId()}`;

  const thumbSizeLabels: Record<ThumbSize, string> = {
    sm: t('Small'),
    md: t('Medium'),
    lg: t('Large'),
    xl: t('XL'),
    '2xl': t('2XL'),
  };

  const thumbSizeChoices = computed(() =>
    (props.thumbSizeOptions ?? []).map((value) => ({
      label: thumbSizeLabels[value],
      value,
    }))
  );

  // The group also announces its initial value once it mounts, which can be
  // a stale one from before the URL's size was restored. Only the user's own
  // choices change the size.
  function onThumbSizeChanged(event: Event) {
    if (event instanceof CustomEvent && event.detail?.isTriggeredByUser) {
      thumbSize.value = (
        event.target as HTMLElement & {modelValue: ThumbSize}
      ).modelValue;
    }
  }

  const emit = defineEmits<{
    (e: 'reorder', options: Array<CheckboxOption>): void;
    (e: 'use-defaults'): void;
  }>();

  /**
   * The columns as displayed in the popover — an open-time snapshot of the
   * canonical order (visible columns in the user's order, then the rest
   * alphabetically; see `useElementIndexColumns`). Mirroring Craft 5: the
   * snapshot refreshes each time the popover *opens*, so toggling checkboxes
   * while it's open never re-sorts rows out from under the pointer — a newly
   * checked column stays put here while appending to the table, and shows in
   * its new position on the next open.
   */
  const displayOptions = ref<Array<CheckboxOption>>([]);

  function takeSnapshot() {
    displayOptions.value = [...props.options];
  }

  takeSnapshot();

  // A source switch replaces the available columns entirely; refresh the
  // snapshot so the popover doesn't show the previous source's list. Watch the
  // option *values* rather than the array: `options` is a computed that gets a
  // new identity (and order) whenever a checkbox toggles, and re-snapshotting
  // then would re-sort the list mid-edit.
  watch(
    () =>
      props.options
        .map((option) => option.value)
        .sort()
        .join(','),
    () => takeSnapshot()
  );

  function onOpenedChanged(event: Event) {
    if (event instanceof CustomEvent && event.detail?.opened) {
      takeSnapshot();
    }
  }

  function onReorder(next: Array<CheckboxOption>) {
    // Keep the popover showing the order the user just dragged to.
    displayOptions.value = next;
    emit('reorder', next);
  }

  function setSortDirection(direction: 'asc' | 'desc') {
    if (!props.sortDirectionLocked) {
      sortDirection.value = direction;
    }
  }

  const closeButton = useTemplateRef<HTMLElement>('closeButton');

  // The button goes away once the settings are back to their defaults, so
  // focus moves on to the one beside it rather than being lost.
  async function useDefaults() {
    emit('use-defaults');
    await nextTick();
    closeButton.value?.focus();
  }

  function closePopover(event: MouseEvent) {
    if (event.currentTarget instanceof HTMLElement) {
      event.currentTarget.dispatchEvent(
        new Event('close-overlay', {bubbles: true, composed: true})
      );
    }
  }
</script>

<template>
  <craft-popover @opened-changed="onOpenedChanged">
    <craft-button
      type="button"
      slot="invoker"
      icon="sliders"
      :variant="ButtonVariant.Fill"
      .disabled="disabled"
    >
      {{ t('View') }}
    </craft-button>

    <div slot="content-body" class="gap-lg">
      <div v-if="sortOptions.length">
        <div class="flex items-end gap-md">
          <Select
            :label="t('Sort by')"
            v-model="sortField"
            :options="sortOptions"
          />
          <craft-button-group role="group" :aria-label="t('Sort Direction')">
            <craft-button
              type="button"
              icon="asc"
              value="asc"
              :aria-label="t('Sort ascending')"
              :aria-pressed="sortDirection === 'asc'"
              :variant="ButtonVariant.Fill"
              .active="sortDirection === 'asc'"
              .disabled="!sortField || sortDirectionLocked"
              @click="setSortDirection('asc')"
            ></craft-button>
            <craft-button
              type="button"
              icon="desc"
              value="desc"
              :aria-label="t('Sort descending')"
              :aria-pressed="sortDirection === 'desc'"
              :variant="ButtonVariant.Fill"
              .active="sortDirection === 'desc'"
              .disabled="!sortField || sortDirectionLocked"
              @click="setSortDirection('desc')"
            ></craft-button>
          </craft-button-group>
        </div>
      </div>
      <div v-if="thumbSizeChoices.length">
        <craft-radio-group
          buttons
          :name="thumbSizeIdPrefix"
          :label="t('Thumb Size')"
          .modelValue="thumbSize"
          @model-value-changed="onThumbSizeChanged"
        >
          <craft-radio
            v-for="option in thumbSizeChoices"
            :key="option.value"
            .choiceValue="option.value"
            .checked="thumbSize === option.value"
          >
            <input
              slot="input"
              :id="`${thumbSizeIdPrefix}-${option.value}`"
              type="radio"
              :name="thumbSizeIdPrefix"
              :value="option.value"
              :checked="thumbSize === option.value"
            />
            <label slot="label" :for="`${thumbSizeIdPrefix}-${option.value}`">
              {{ option.label }}
            </label>
          </craft-radio>
        </craft-radio-group>
      </div>
      <div v-if="showTableColumns">
        <CheckboxGroup
          :label="t('Table Columns')"
          name="viewState[tableColumns][]"
          v-model="tableColumns"
          :options="displayOptions"
          sortable
          @update:options="onReorder"
        />
      </div>
    </div>

    <div slot="content-footer" class="flex items-center gap-md">
      <craft-button
        v-if="customized"
        type="button"
        :variant="ButtonVariant.Plain"
        @click="useDefaults"
        >{{ t('Use defaults') }}</craft-button
      >
      <craft-button
        ref="closeButton"
        class="ms-auto"
        type="button"
        @click="closePopover"
        :variant="ButtonVariant.Fill"
        >{{ t('Close') }}</craft-button
      >
    </div>
  </craft-popover>
</template>
