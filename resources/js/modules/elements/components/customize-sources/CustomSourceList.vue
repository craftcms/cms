<script setup lang="ts" generic="T">
  /**
   * The reorderable row list shared by the customize-sources sidebars: pages on
   * the left, sources beside them. It owns the markup, the drag and keyboard
   * reordering, and the selected/dragging states; callers supply the row's
   * label and its action menu.
   */
  import {useReorderableItems} from '@/common/composables/useReorderableItems';

  const props = defineProps<{
    items: T[];
    /** Stable id for a row — keys the list, the drag registry and selection. */
    itemId: (item: T, index: number) => string;
    /** Id of the selected row, if any. */
    selected?: string | null;
    /** Rows this returns true for can't be selected. */
    disabled?: (item: T) => boolean;
  }>();

  const emit = defineEmits<{
    (e: 'select', id: string): void;
    (e: 'reorder', from: number, to: number): void;
  }>();

  function id(item: T, index: number): string {
    return props.itemId(item, index);
  }

  function reorder(from: number, to: number): void {
    if (to < 0 || to > props.items.length - 1) return;
    emit('reorder', from, to);
  }

  const {setItemRef, setHandleRef, getDragState, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => props.items.map((item, index) => id(item, index)),
      onReorder: reorder,
      enabled: () => props.items.length > 1,
    });

  function select(item: T, index: number): void {
    if (props.disabled?.(item)) return;

    emit('select', id(item, index));
  }
</script>

<template>
  <ol class="cs-list">
    <li
      v-for="(item, index) in items"
      :key="id(item, index)"
      :ref="(el) => setItemRef(el as HTMLElement, id(item, index))"
      class="cs-item"
      :class="{
        'cs-item--selected': id(item, index) === selected,
        'cs-item--dragging':
          getDragState(id(item, index)).type === 'is-dragging',
      }"
      :data-drop="getDropState(id(item, index)).type"
    >
      <span
        v-if="items.length > 1"
        :ref="(el) => setHandleRef(el as HTMLElement, id(item, index))"
        class="cs-item__handle"
      >
        <craft-reorder-button
          :position="getRowPosition(index)"
          @reorder="
            (e: CustomEvent<{direction: 'up' | 'down'}>) =>
              reorder(
                index,
                e.detail.direction === 'up' ? index - 1 : index + 1
              )
          "
        />
      </span>

      <button
        type="button"
        class="cs-item__btn"
        :aria-pressed="id(item, index) === selected"
        :disabled="disabled?.(item)"
        @click="select(item, index)"
      >
        <slot name="label" :item="item" :index="index" />
      </button>

      <slot name="actions" :item="item" :index="index" />
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
