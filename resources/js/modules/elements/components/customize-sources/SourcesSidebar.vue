<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';
  import type {PageRow, SourceRow} from './types';

  const props = defineProps<{
    sources: SourceRow[];
    pages: PageRow[];
    selectedKey: string | null;
    /** The page whose sources are shown, or null on a single-page element type. */
    page: string | null;
  }>();

  const emit = defineEmits<{
    (e: 'select', key: string): void;
    (e: 'reorder', from: number, to: number): void;
    (e: 'remove', key: string): void;
    (e: 'move-to-page', key: string, page: string): void;
    (e: 'add', type: 'heading' | 'custom'): void;
  }>();

  // Only the current page's sources are listed, but reordering has to act on
  // indexes into the full list.
  const visible = computed(() =>
    props.sources.filter(
      (source) => props.page === null || source.page === props.page
    )
  );

  function indexOf(source: SourceRow): number {
    return props.sources.indexOf(source);
  }

  function reorder(from: number, to: number): void {
    const items = visible.value;
    if (to < 0 || to > items.length - 1) return;
    emit('reorder', indexOf(items[from]!), indexOf(items[to]!));
  }

  function itemId(source: SourceRow, index: number): string {
    return source.key ?? `unkeyed-${index}`;
  }

  const {setItemRef, setHandleRef, getDragState, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => visible.value.map(itemId),
      onReorder: reorder,
      enabled: () => visible.value.length > 1,
    });

  function label(source: SourceRow): string {
    return source.label.trim();
  }

  function actions(source: SourceRow, index: number): ActionItem[] {
    const items: ActionItem[] = [
      {
        label: t('Move up'),
        disabled: index === 0,
        onClick: () => reorder(index, index - 1),
      },
      {
        label: t('Move down'),
        disabled: index === visible.value.length - 1,
        onClick: () => reorder(index, index + 1),
      },
    ];

    for (const page of props.pages) {
      if (page.name !== source.page && source.key) {
        items.push({
          label: t('Move to {page}', {page: page.name}),
          onClick: () => emit('move-to-page', source.key!, page.name),
        });
      }
    }

    // Native sources are disabled rather than deleted — they come from the
    // element type, not from project config.
    if (source.key && source.type !== 'native') {
      items.push({
        label: t('Delete'),
        variant: 'danger',
        onClick: () => emit('remove', source.key!),
      });
    }

    return items;
  }
</script>

<template>
  <ol class="cs-list">
    <li
      v-for="(source, index) in visible"
      :key="itemId(source, index)"
      :ref="(el) => setItemRef(el as HTMLElement, itemId(source, index))"
      class="cs-item"
      :class="{
        'cs-item--selected': source.key === selectedKey,
        'cs-item--dragging':
          getDragState(itemId(source, index)).type === 'is-dragging',
      }"
      :data-drop="getDropState(itemId(source, index)).type"
    >
      <span
        v-if="visible.length > 1"
        :ref="(el) => setHandleRef(el as HTMLElement, itemId(source, index))"
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
        :aria-pressed="source.key === selectedKey"
        :disabled="!source.key"
        @click="source.key && emit('select', source.key)"
      >
        <span v-if="label(source)">{{ label(source) }}</span>
        <em v-else>{{ t('(blank)') }}</em>
      </button>

      <ActionMenu v-if="source.key" :actions="actions(source, index)" />
    </li>
  </ol>

  <ActionMenu
    class="cs-add"
    :label="t('Source actions')"
    :actions="[
      {label: t('New heading'), onClick: () => emit('add', 'heading')},
      {label: t('New custom source'), onClick: () => emit('add', 'custom')},
    ]"
  />
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
    padding: var(--c-spacing-xs) 0;
    border: none;
    background: none;
    color: inherit;
    font: inherit;
    text-align: start;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
</style>
