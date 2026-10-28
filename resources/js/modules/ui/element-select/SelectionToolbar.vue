<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {Selectable} from '@/common/composables/useSelectable';
  import type {ActionItem} from '@/common/types';

  /**
   * An element-select field's selection bar: select all, the selection's
   * count and actions, and how full the field is.
   */
  const props = defineProps<{
    selection: Selectable<number>;
    actions: ActionItem[];
    /** How many elements the field relates. */
    total: number;
    limit: number | null;
  }>();

  const {selectedIds, hasSelection, allSelected, someSelected} =
    props.selection;

  /**
   * Built here rather than inline: the ICU braces in the plural form read as
   * nested interpolation to the template compiler.
   */
  const selectionCountLabel = computed(() =>
    t('{num, number} {num, plural, =1{item} other{items}} selected', {
      num: selectedIds.value.length,
    })
  );

  /**
   * `craft-checkbox` dispatches from the host rather than an inner `<input>`, so
   * the checked state has to be read off the target as a plain property.
   */
  function checkboxValue(event: Event): boolean {
    return Boolean((event.target as {checked?: boolean} | null)?.checked);
  }
</script>

<template>
  <div class="element-selections-header flex justify-between items-center">
    <div class="flex items-center gap-2">
      <craft-checkbox
        label-sr-only
        .checked="allSelected"
        .indeterminate="someSelected"
        @model-value-changed="selection.toggleAll(checkboxValue($event))"
      >
        <label slot="label">{{ t('Select all') }}</label>
      </craft-checkbox>

      <template v-if="hasSelection">
        <div class="text-xs font-bold">{{ selectionCountLabel }}</div>
        <craft-button
          type="button"
          size="small"
          variant="plain"
          @click="selection.clear()"
        >
          {{ t('Clear selection') }}
        </craft-button>
      </template>
    </div>

    <div class="flex items-center gap-sm ms-auto">
      <ActionMenu
        :actions="actions"
        :class="{
          invisible: !hasSelection || !actions.length,
        }"
      >
        <template #invoker="{attributes}">
          <craft-button
            type="button"
            variant="plain"
            size="small"
            flush
            v-bind="attributes"
          >
            {{ t('Actions') }}
            <craft-icon name="chevron-down" slot="suffix"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>

      <craft-badge
        size="small"
        no-prefix
        v-if="limit !== null && (!hasSelection || !actions.length)"
      >
        {{ total }}/{{ limit }}
      </craft-badge>
    </div>
  </div>
</template>

<style scoped>
  .element-selections-header {
    margin-block-end: var(--c-spacing-md);
    min-height: calc(var(--c-size-control-sm) + var(--c-spacing-sm) + 1px);
    padding-block: 0 var(--c-spacing-sm);
    padding-inline: 0;
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }
</style>
