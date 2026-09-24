<script setup lang="ts" generic="T">
  /**
   * The reorderable row list shared by the customize-sources sidebars: pages on
   * the left, sources beside them. It owns the markup, the drag and keyboard
   * reordering, and how a row presents itself; callers describe each row
   * through the callbacks below.
   */
  import {computed, useId} from 'vue';
  import {CraftActionItem, t, type ReorderMove} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';
  import DropIndicator from '@/common/components/DropIndicator.vue';
  import type {Edge} from '@atlaskit/pragmatic-drag-and-drop-hitbox/types';

  const props = defineProps<{
    items: T[];
    /** Stable id for a row — keys the list, the drag registry and selection. */
    itemId: (item: T, index: number) => string;
    /** The row's text. A blank heading renders as “(blank)”; anything else, empty. */
    label: (item: T) => string;
    /** A second line under the label — a source's handle, say. */
    sublabel?: (item: T) => string | null;
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
     * Somewhere else a row can go, offered in its reorder menu after Move
     * up/down — "Move to {page}", say. Chosen, it emits `move-to`.
     */
    moves?: (item: T, index: number) => ReorderMove[];
    /**
     * How many rows move together starting at `index` — a heading and the
     * sources beneath it, say. Defaults to 1: every row moves alone.
     */
    span?: (index: number) => number;
    /** Tags this list's drags, so another list can accept them (`accepts`). */
    dragType?: string;
    /** The `dragType` of another list whose rows can be dropped onto these. */
    accepts?: string;
    /** Rows this returns false for won't take a drop from another list. */
    canDropInto?: (item: T) => boolean;
  }>();

  const emit = defineEmits<{
    (e: 'select', id: string): void;
    /**
     * The rows starting at `from` (see `span`) moved, so that the first of them
     * now sits at `to`.
     */
    (e: 'reorder', from: number, to: number): void;
    /** A row from another list (`accepts`) was dropped onto the row `targetId`. */
    (e: 'drop-into', targetId: string, draggedId: string): void;
    /** The row `id` was sent to one of its `moves`, identified by its `value`. */
    (e: 'move-to', id: string, value: string): void;
  }>();

  // Resolved once per row rather than per binding: the id is read five times in
  // the template, and the actions callback is not free.
  const rows = computed(() =>
    props.items.map((item, index) => ({
      item,
      index,
      id: props.itemId(item, index),
      label: props.label(item).trim(),
      sublabel: props.sublabel?.(item) ?? null,
      icon: props.icon?.(item) ?? null,
      type: props.itemType?.(item) ?? null,
      actions: props.actions?.(item, index) ?? [],
      moves: props.moves?.(item, index) ?? [],
    }))
  );

  function spanOf(index: number): number {
    return Math.max(1, props.span?.(index) ?? 1);
  }

  /**
   * Moves the rows starting at `from` into `gap` — counted from 0 (before the
   * first row) to the row count (after the last). A gap inside or at either end
   * of the rows being moved leaves them where they are.
   */
  function move(from: number, gap: number): void {
    const span = spanOf(from);

    if (gap < 0 || gap > props.items.length) return;
    if (gap >= from && gap <= from + span) return;

    emit('reorder', from, gap > from ? gap - span : gap);
  }

  const {setItemRef, setHandleRef, getDragState, getDropIndex, getDropIntoId} =
    useReorderableItems({
      getItemIds: () => rows.value.map((row) => row.id),
      // The drag layer reports where a single row would end up; the gap it
      // dropped into is what a multi-row move needs.
      onReorder: (from, to) => move(from, to > from ? to + 1 : to),
      enabled: () => props.items.length > 1,
      type: props.dragType,
      dropInto: props.accepts
        ? {
            accepts: (dragged, targetId) => {
              const row = rows.value.find((row) => row.id === String(targetId));

              return (
                dragged.type === props.accepts &&
                !!row &&
                (props.canDropInto?.(row.item) ?? true)
              );
            },
            onDrop: (targetId, dragged) =>
              emit('drop-into', String(targetId), String(dragged.id)),
          }
        : undefined,
    });

  /** The row being dragged, if any — the first of the rows moving with it. */
  const draggingIndex = computed(() =>
    rows.value.findIndex((row) => getDragState(row.id).type !== 'idle')
  );

  /** Whether a row is being dragged, on its own or along with the row above. */
  function isDragging(index: number): boolean {
    const from = draggingIndex.value;

    return from !== -1 && index >= from && index < from + spanOf(from);
  }

  /** Where a row sits for its Move up/Move down actions, counting what it carries. */
  function position(index: number): 'first' | 'middle' | 'last' | 'only' {
    const first = index === 0;
    const last = index + spanOf(index) >= props.items.length;

    if (first && last) return 'only';
    if (first) return 'first';

    return last ? 'last' : 'middle';
  }

  /**
   * Which edge of this row carries the drop line, if any. Every gap is drawn
   * by the row below it — only the gap after the last row falls to that row's
   * bottom — so the line between two rows has exactly one owner.
   */
  function dropEdge(index: number): Edge | null {
    const gap = getDropIndex();
    const from = draggingIndex.value;

    // Nowhere new: the rows would land back where they are.
    if (
      gap === null ||
      (from !== -1 && gap >= from && gap <= from + spanOf(from))
    ) {
      return null;
    }

    if (gap === index) return 'top';
    if (gap === index + 1 && index === props.items.length - 1) return 'bottom';

    return null;
  }

  const uid = useId();
  const labelIds = new Map<string, string>();

  /**
   * The id of a row's label, for its menu button to be described by. Kept per
   * row rather than per position: the button is rendered once, so an id that
   * followed the index would point at another row after a reorder.
   */
  function labelId(id: string): string {
    if (!labelIds.has(id)) labelIds.set(id, `${uid}-label-${labelIds.size}`);

    return labelIds.get(id)!;
  }

  function select(item: T, id: string): void {
    if (props.disabled?.(item)) return;

    emit('select', id);
  }
</script>

<template>
  <ol class="cs-list">
    <!-- The row is a plain list item rather than the button itself: a native
      drag can't start from inside a <button>, and the handle and menu are
      controls of their own that mustn't nest inside another. -->
    <li
      v-for="row in rows"
      :key="row.id"
      :ref="(el) => setItemRef(el as HTMLElement | null, row.id)"
      :data-row-id="row.id"
      :data-color="row.id === selected ? 'accent' : undefined"
      :class="{
        'cs-item': true,
        'cs-item--active': row.id === selected,
        'cs-item--heading': row.type === 'heading',
        'cs-item--dragging': isDragging(row.index),
        'cs-item--drop-target': getDropIntoId() === row.id,
      }"
    >
      <span class="cs-item__control">
        <craft-reorder-button
          class="cs-item__handle"
          :position="position(row.index)"
          :moves="row.moves"
          :ref="(el: HTMLElement) => setHandleRef(el, row.id)"
          @craft-reorder="
            (e: CustomEvent<{direction: 'up' | 'down'}>) =>
              move(
                row.index,
                e.detail.direction === 'up'
                  ? row.index - 1
                  : row.index + spanOf(row.index) + 1
              )
          "
          @craft-move="
            (e: CustomEvent<{value: string}>) =>
              emit('move-to', row.id, e.detail.value)
          "
        />
      </span>

      <craft-action-item
        class="cs-item__select"
        :current="row.id === selected"
        @click="select(row.item, row.id)"
      >
        <craft-icon v-if="row.icon" :name="row.icon" slot="icon" />
        <span class="cs-item__text">
          <span v-if="row.label" :id="labelId(row.id)" class="cs-item__label">{{
            row.label
          }}</span>
          <em
            v-else-if="row.type === 'heading'"
            :id="labelId(row.id)"
            class="cs-item__label"
            >{{ t('(blank)') }}</em
          >
          <span v-else :id="labelId(row.id)" class="cs-item__label"
            >&nbsp;</span
          >
          <span v-if="row.sublabel" class="cs-item__sublabel">{{
            row.sublabel
          }}</span>
        </span>
      </craft-action-item>

      <!-- Every row's button says “Actions”, so it's described by its row. -->
      <span v-if="row.actions.length" class="cs-item__control">
        <ActionMenu :actions="row.actions">
          <template #invoker>
            <craft-button
              slot="invoker"
              type="button"
              size="small"
              icon="ellipsis"
              variant="plain"
              inherit
              :aria-label="t('Actions')"
              :aria-describedby="labelId(row.id)"
            ></craft-button>
          </template>
        </ActionMenu>
      </span>

      <DropIndicator
        contained
        class="cs-item__drop-indicator"
        :edge="dropEdge(row.index)"
      />
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
    --_gap: var(--c-spacing-xs);

    position: relative;
    display: flex;
    // Top, not centre: a label that wraps would otherwise pull the handle and
    // menu down to the middle of it.
    align-items: flex-start;
    gap: var(--c-spacing-xs);
    // Inset, so the handle and menu sit inside the highlighted row rather than
    // on its edges.
    padding-inline: var(--c-spacing-xs);
    border: 1px solid transparent;
    border-radius: var(--c-radius-md);
  }

  // The row, not the button inside it, shows hover and selection, so the handle
  // and menu light up with it. The item's own hover fill is switched off.
  .cs-item__select {
    --c-color-fill-quiet: transparent;
  }

  @media (hover: hover) {
    .cs-item:not(.cs-item--active, .cs-item--dragging):hover {
      background-color: var(--c-color-neutral-fill-quiet);
    }
  }

  .cs-item--heading {
    margin-block-start: var(--c-spacing-md);
    // On the row rather than the label, so the controls' one-line box (`1lh`)
    // measures the heading's line, not the body text's.
    font-size: var(--c-text-sm);

    .cs-item__label {
      font-weight: bold;
    }
  }

  // Quiet unless the row is selected, where it takes the row's accent.
  .cs-item:not(.cs-item--active) .cs-item__sublabel {
    color: var(--c-text-quiet);
  }

  // `data-color="accent"` points the palette tokens at the accent colours, so
  // the label and the `inherit` buttons inside take the row's colours with it.
  .cs-item--active {
    background-color: var(--c-color-fill-quiet);
    border-color: var(--c-color-border-loud);
    color: var(--c-color-on-quiet);
  }

  // The row being dragged stays where it was, greyed out, so you can see where
  // it came from while the line shows where it's going.
  .cs-item--dragging {
    opacity: 0.4;
  }

  // A row from the other list is about to be dropped onto this one.
  .cs-item--drop-target {
    background-color: var(--c-color-accent-fill-quiet);
    border-color: transparent;
  }

  // Centred in the gap between rows rather than on the row's own edge, so the
  // bottom of one row and the top of the next draw the same line.
  // Scoped under `.cs-item` to outrank DropIndicator's own contained offsets.
  .cs-item .cs-item__drop-indicator.drop-indicator--top {
    top: calc(var(--_gap) / -2 - 1px);
  }

  .cs-item .cs-item__drop-indicator.drop-indicator--bottom {
    bottom: calc(var(--_gap) / -2 - 1px);
  }

  // A heading's margin widens the gap above it, so the line above one belongs
  // in the middle of that larger gap.
  .cs-item--heading .cs-item__drop-indicator.drop-indicator--top {
    top: calc((var(--_gap) + var(--c-spacing-md)) / -2 - 1px);
  }

  // Takes the leftover width so a long label truncates rather than squeezing
  // the icon beside it.
  // The handle and the menu each sit in a box as tall as a one-line row — the
  // select button's line plus its padding and border — so they stay level with
  // the label's first line however many lines it wraps to.
  .cs-item__control {
    display: flex;
    flex: none;
    align-items: center;
    height: calc(1lh + 2 * var(--c-spacing-sm) + 2px);
  }

  .cs-item__text {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  // Wraps rather than truncating — overriding the `nowrap` the item's own label
  // would otherwise hand down — and breaks a long word if it has to.
  .cs-item__label {
    min-width: 0;
    white-space: normal;
    overflow-wrap: anywhere;
  }

  .cs-item__sublabel {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .cs-item__sublabel {
    font-family: var(--c-font-mono);
    font-size: 0.8em;
  }
</style>
