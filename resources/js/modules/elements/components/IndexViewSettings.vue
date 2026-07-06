<script setup lang="ts">
  import {ref, watch} from 'vue';
  import {Appearance, t} from '@craftcms/cp';
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
   * The columns as displayed in the popover. Mirroring Craft 5: each time the
   * popover *opens*, the checked columns group to the top (keeping their
   * display order) so they're easy to drag into new positions — but the list
   * is a snapshot, so toggling checkboxes while it's open doesn't re-sort
   * anything out from under the pointer. Only a drag persists a new order.
   */
  const displayOptions = ref<Array<CheckboxOption>>([]);

  function groupCheckedFirst() {
    const checked = new Set(tableColumns.value);

    displayOptions.value = [
      // The pinned column (disabled + always on) leads, then the checked
      // columns in display order, then everything else.
      ...props.options.filter(
        (option) => option.disabled || checked.has(option.value)
      ),
      ...props.options.filter(
        (option) => !option.disabled && !checked.has(option.value)
      ),
    ];
  }

  groupCheckedFirst();

  // A source switch replaces the available columns entirely; refresh the
  // snapshot so the popover doesn't show the previous source's list. Watch the
  // option *values* rather than the array: `options` is a computed that gets a
  // new identity whenever a checkbox toggles, and regrouping then would
  // re-sort the list out from under the pointer.
  watch(
    () => props.options.map((option) => option.value).join(','),
    () => groupCheckedFirst()
  );

  function onOpenedChanged(event: Event) {
    if ((event as CustomEvent).detail?.opened) {
      groupCheckedFirst();
    }
  }

  function onReorder(next: Array<CheckboxOption>) {
    // Keep the popover showing the order the user just dragged to.
    displayOptions.value = next;
    emit('reorder', next);
  }

  function closePopover(event: MouseEvent) {
    (event.currentTarget as HTMLElement).dispatchEvent(
      new Event('close-overlay', {bubbles: true, composed: true})
    );
  }
</script>

<template>
  <craft-popover @opened-changed="onOpenedChanged">
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
        :appearance="Appearance.Fill"
        >{{ t('Close') }}</craft-button
      >
    </div>
  </craft-popover>
</template>
