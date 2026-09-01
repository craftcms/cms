<script setup lang="ts">
  import {ref, watch} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import Select from '@/common/form/Select.vue';
  import CheckboxGroup from '@/common/form/CheckboxGroup.vue';
  import type {CheckboxOption} from '@/common/types';
  import type {SortOption} from '@/modules/elements/types/view-state';

  const props = defineProps<{
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
              :variant="ButtonVariant.Fill"
              :active="sortDirection === 'asc'"
            ></craft-button>
            <craft-button
              type="button"
              icon="desc"
              value="desc"
              :aria-label="t('Sort descending')"
              :variant="ButtonVariant.Fill"
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
          :options="displayOptions"
          sortable
          @update:options="onReorder"
        />
      </div>
    </div>

    <div slot="content-footer">
      <craft-button
        type="button"
        @click="closePopover"
        :variant="ButtonVariant.Fill"
        >{{ t('Close') }}</craft-button
      >
    </div>
  </craft-popover>
</template>
