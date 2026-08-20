<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import CustomSourceList from './CustomSourceList.vue';
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

  function onReorder(from: number, to: number): void {
    const items = visible.value;

    emit('reorder', indexOf(items[from]!), indexOf(items[to]!));
  }

  /** A source with no key can't be addressed; fall back to its position. */
  function itemId(source: SourceRow, index: number): string {
    return source.key ?? `unkeyed-${index}`;
  }

  function unkeyed(source: SourceRow): boolean {
    return !source.key;
  }

  function label(source: SourceRow): string {
    return source.label.trim();
  }

  function actions(source: SourceRow): ActionItem[] {
    const items: ActionItem[] = [];

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
  <CustomSourceList
    :items="visible"
    :item-id="itemId"
    :selected="selectedKey"
    :disabled="unkeyed"
    :actions="actions"
    @select="(key) => emit('select', key)"
    @reorder="onReorder"
  >
    <template #label="{item}">
      <span v-if="label(item)">{{ label(item) }}</span>
      <em v-else>{{ t('(blank)') }}</em>
    </template>
  </CustomSourceList>

  <ActionMenu
    class="cs-add"
    :label="t('Source actions')"
    :actions="[
      {label: t('New heading'), onClick: () => emit('add', 'heading')},
      {label: t('New custom source'), onClick: () => emit('add', 'custom')},
    ]"
  />
</template>
