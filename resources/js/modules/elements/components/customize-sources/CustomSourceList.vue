<script setup lang="ts" generic="T">
  /**
   * The reorderable row list shared by the customize-sources sidebars: pages on
   * the left, sources beside them. It owns the markup, the drag and keyboard
   * reordering, and how a row presents itself; callers describe each row
   * through the callbacks below.
   */
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';
  import PageIcon from './PageIcon.vue';

  const props = defineProps<{
    items: T[];
    /** Stable id for a row — keys the list, the drag registry and selection. */
    itemId: (item: T, index: number) => string;
    /** The row's text. A blank one renders as “(blank)”. */
    label: (item: T) => string;
    /** An icon to lead the row with, if any. */
    icon?: (item: T) => string | null;
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
      label: props.label(item).trim(),
      icon: props.icon?.(item) ?? null,
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
        <PageIcon v-if="row.icon" :icon="row.icon" />
        <span v-if="row.label" class="cs-item__label">{{ row.label }}</span>
        <em v-else class="cs-item__label">{{ t('(blank)') }}</em>
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
    margin: 0 0 var(--c-spacing-md);
    padding: 0;
    list-style: none;
  }

  .cs-item {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    padding-inline-end: var(--c-spacing-xs);
    border-radius: var(--c-radius-md);
    // Set explicitly: the retired legacy stylesheet still ships an unscoped
    // `.cs-item` rule, and this shouldn't depend on which one wins.
    background-color: var(--c-surface-default);
  }

  .cs-item--selected {
    background-color: var(--c-color-static-accent-fill);
    color: var(--c-color-static-accent-on);
  }

  // Against the accent fill, the handle and menu need the same contrast the
  // label gets.
  .cs-item--selected .cs-item__handle {
    color: inherit;
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
    gap: var(--c-spacing-md);
    padding: var(--c-spacing-xs) 0;
    border: none;
    background: none;
    color: inherit;
    font: inherit;
    text-align: start;
    cursor: pointer;
    overflow: hidden;
  }

  // Takes the leftover width so a long label truncates rather than squeezing
  // the icon beside it.
  .cs-item__label {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
</style>
