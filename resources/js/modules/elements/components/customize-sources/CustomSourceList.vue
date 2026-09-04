<script setup lang="ts" generic="T">
  /**
   * The reorderable row list shared by the customize-sources sidebars: pages on
   * the left, sources beside them. It owns the markup, the drag and keyboard
   * reordering, and how a row presents itself; callers describe each row
   * through the callbacks below.
   */
  import {computed} from 'vue';
  import {CraftActionItem, t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import type {Edge} from '@atlaskit/pragmatic-drag-and-drop-hitbox/types';
  import {
    type DragData,
    useReorderableItems,
  } from '@/common/composables/useReorderableItems';
  import PageIcon from './PageIcon.vue';
  import VarDump from '@/common/components/VarDump.vue';

  const props = defineProps<{
    items: T[];
    /** Stable id for a row — keys the list, the drag registry and selection. */
    itemId: (item: T, index: number) => string;
    /** The row's text. A blank one renders as “(blank)”. */
    label: (item: T) => string;
    /** An icon to lead the row with, if any. */
    icon?: (item: T) => string | null;
    /**
     * The row's kind, surfaced as `cs-item--<kind>` alongside `cs-item`. Kept
     * opaque so this list can mark a heading apart without knowing what a
     * source is.
     */
    itemType?: (item: T) => string | null;
    /** Id of the selected row, if any. */
    selected?: string | null;
    /** Rows this returns true for can't be selected. */
    disabled?: (item: T) => boolean;
    /** A row's action menu. Returning nothing renders no menu. */
    actions?: (item: T, index: number) => ActionItem[];
    /**
     * What a row's drag carries for the other list to read — a source key, say.
     * Rows this returns nothing for can still be reordered here, they just
     * can't be dropped anywhere else.
     */
    dragData?: (item: T, index: number) => DragData;
    /**
     * Whether a row dragged out of the other list can be dropped into this
     * one, which emits `foreign-drop` rather than reordering.
     */
    canDropForeign?: (data: DragData) => boolean;
  }>();

  const emit = defineEmits<{
    (e: 'select', id: string): void;
    (e: 'reorder', from: number, to: number): void;
    /** `index` is where the row would be inserted, 0 through `items.length`. */
    (e: 'foreign-drop', data: DragData, index: number): void;
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
      type: props.itemType?.(item) ?? null,
      actions: props.actions?.(item, index) ?? [],
    }))
  );

  function reorder(from: number, to: number): void {
    if (to < 0 || to > props.items.length - 1) return;

    emit('reorder', from, to);
  }

  function rowById(id: string | number) {
    return rows.value.find((row) => row.id === String(id));
  }

  const {setItemRef, setHandleRef, getDragState, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => rows.value.map((row) => row.id),
      onReorder: reorder,
      // A lone row has nothing to reorder with, but it can still be dragged
      // over to the other list, and be dropped on.
      enabled: () =>
        props.items.length > 1 ||
        props.dragData !== undefined ||
        props.canDropForeign !== undefined,
      dragData: (id, index) => {
        const row = rowById(id);

        return row ? (props.dragData?.(row.item, index) ?? {}) : {};
      },
      canDropForeign: (data) => props.canDropForeign?.(data) ?? false,
      onForeignDrop: (data, target) =>
        emit(
          'foreign-drop',
          data,
          target.index + (target.edge === 'bottom' ? 1 : 0)
        ),
    });

  /**
   * Which side of a row a foreign drag would land on, so the insertion point is
   * marked the way it will be applied.
   */
  function foreignDropEdge(id: string): Edge | null {
    const state = getDropState(id);

    return state.type === 'is-over-foreign' ? state.closestEdge : null;
  }

  function select(item: T, id: string): void {
    if (props.disabled?.(item)) return;

    emit('select', id);
  }
</script>

<template>
  <ol class="cs-list">
    <craft-action-item
      v-for="row in rows"
      :key="row.id"
      @click="select(row.item, row.id)"
      :ref="(el: HTMLElement) => setItemRef(el, row.id)"
      :active="row.id === selected"
      :class="{
        'cs-item': true,
        'cs-item--heading': row.type === 'heading',
        'cs-item--dragging': getDragState(row.id).type === 'is-dragging',
        'cs-item--drop-before': foreignDropEdge(row.id) === 'top',
        'cs-item--drop-after': foreignDropEdge(row.id) === 'bottom',
      }"
    >
      <craft-reorder-button
        slot="prefix"
        :position="getRowPosition(row.index)"
        :ref="(el: HTMLElement) => setHandleRef(el, row.id)"
        @reorder="
          (e: CustomEvent<{direction: 'up' | 'down'}>) =>
            reorder(
              row.index,
              e.detail.direction === 'up' ? row.index - 1 : row.index + 1
            )
        "
      />
      <craft-icon v-if="row.icon" :name="row.icon" slot="icon" />
      <span v-if="row.label" class="cs-item__label">{{ row.label }}</span>
      <strong v-else class="font-bold cs-item__label">{{
        t('(blank)')
      }}</strong>

      <ActionMenu
        v-if="row.actions.length"
        :actions="row.actions"
        slot="suffix"
      />
    </craft-action-item>
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
    position: relative;
  }

  // Where a source dropped into this list would land.
  .cs-item--drop-before::before,
  .cs-item--drop-after::after {
    content: '';
    position: absolute;
    inset-inline: 0;
    height: calc(2rem / 16);
    background-color: var(--c-color-accent-fill-loud);
  }

  .cs-item--drop-before::before {
    top: calc(-1rem / 16);
  }

  .cs-item--drop-after::after {
    bottom: calc(-1rem / 16);
  }

  .cs-item--heading {
    margin-block-start: var(--c-spacing-md);

    .cs-item__label {
      font-size: var(--c-text-sm);
      font-weight: bold;
    }
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
