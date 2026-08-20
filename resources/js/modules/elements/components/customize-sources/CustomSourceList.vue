<script setup lang="ts" generic="T">
  /**
   * The reorderable row list shared by the customize-sources sidebars: pages on
   * the left, sources beside them. It owns the markup, the drag and keyboard
   * reordering, and the selected/dragging states; callers supply the row's
   * label and the contents of its action menu.
   */
  import {computed} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';

  const props = defineProps<{
    items: T[];
    /** Stable id for a row — keys the list, the drag registry and selection. */
    itemId: (item: T, index: number) => string;
    /** Id of the selected row, if any. */
    selected?: string | null;
    /** Rows this returns true for can't be selected. */
    disabled?: (item: T) => boolean;
    /** A row's action menu. Returning nothing renders no menu. */
    actions?: (item: T, index: number) => ActionItem[];
  }>();

  const emit = defineEmits<{
    (e: 'select', id: string): void;
    (e: 'reorder', from: number, to: number): void;
  }>();

  // Resolved once per row rather than per binding: the id is read five times in
  // the template, and the actions callback is not free.
  const rows = computed(() =>
    props.items.map((item, index) => ({
      item,
      index,
      id: props.itemId(item, index),
      actions: props.actions?.(item, index) ?? [],
    }))
  );

  function reorder(from: number, to: number): void {
    if (to < 0 || to > props.items.length - 1) return;

    emit('reorder', from, to);
  }

  const {setItemRef, setHandleRef, getDragState, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => rows.value.map((row) => row.id),
      onReorder: reorder,
      enabled: () => props.items.length > 1,
    });

  function select(item: T, id: string): void {
    if (props.disabled?.(item)) return;

    emit('select', id);
  }
</script>

<template>
  <ol class="cs-list">
    <li
      v-for="row in rows"
      :key="row.id"
      :ref="(el) => setItemRef(el as HTMLElement, row.id)"
      class="cs-item"
      :class="{
        'cs-item--selected': row.id === selected,
        'cs-item--dragging': getDragState(row.id).type === 'is-dragging',
      }"
      :data-drop="getDropState(row.id).type"
    >
      <span
        v-if="rows.length > 1"
        :ref="(el) => setHandleRef(el as HTMLElement, row.id)"
        class="cs-item__handle"
      >
        <craft-reorder-button
          :position="getRowPosition(row.index)"
          @reorder="
            (e: CustomEvent<{direction: 'up' | 'down'}>) =>
              reorder(
                row.index,
                e.detail.direction === 'up' ? row.index - 1 : row.index + 1
              )
          "
        />
      </span>

      <button
        type="button"
        class="cs-item__btn"
        :aria-pressed="row.id === selected"
        :disabled="disabled?.(row.item)"
        @click="select(row.item, row.id)"
      >
        <slot name="label" :item="row.item" :index="row.index" />
      </button>

      <ActionMenu v-if="row.actions.length" :actions="row.actions" />
    </li>
  </ol>
</template>

<style scoped lang="scss">
  .cs-list {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xs);
    margin: 0 0 var(--c-spacing-m);
    padding: 0;
    list-style: none;
  }

  .cs-item {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    padding-inline-end: var(--c-spacing-xs);
    border-radius: var(--c-border-radius-md);
    background-color: var(--c-bg-subtle);
  }

  .cs-item--selected {
    background-color: var(--c-bg-selected, var(--c-color-accent));
    color: var(--c-text-inverted, inherit);
  }

  .cs-item--dragging {
    opacity: 0.4;
  }

  .cs-item__handle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--c-text-quiet);
    cursor: grab;
  }

  .cs-item__handle:active {
    cursor: grabbing;
  }

  .cs-item__btn {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    padding: var(--c-spacing-xs) 0;
    border: none;
    background: none;
    color: inherit;
    font: inherit;
    text-align: start;
    cursor: pointer;
    overflow: hidden;
  }

  // The label is the caller's markup, so the truncation has to reach into the
  // slot rather than sit on the button.
  .cs-item__btn :slotted(span),
  .cs-item__btn :slotted(em) {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
</style>
