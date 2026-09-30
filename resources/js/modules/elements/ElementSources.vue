<script setup lang="ts">
  import {computed} from 'vue';
  import ActionList from '@/common/components/ActionList.vue';
  import type {
    ActionItemButton,
    ActionItemGroup,
    ActionItems,
  } from '@/common/types';
  import type {Source, SourceItem} from './types/sources';

  const props = defineProps<{
    sources: Source[];
    activeSource?: string | null;
  }>();
  const emit = defineEmits<{select: [key: string]}>();

  function toAction(source: SourceItem): ActionItemButton {
    return {
      type: 'button',
      label: source.label,
      selected: source.key === props.activeSource,
      attrs: {button: ''},
      onClick: () => {
        if (source.key !== props.activeSource) {
          emit('select', source.key);
        }
      },
    };
  }

  const actions = computed<ActionItems>(() => {
    const result: ActionItems = [];
    let group: ActionItemGroup | null = null;

    for (const source of props.sources) {
      if (source.type === 'heading') {
        group = source.heading
          ? {type: 'group', heading: source.heading, items: []}
          : null;

        if (group) {
          result.push(group);
        }

        for (const child of source.children ?? []) {
          (group?.items ?? result).push(toAction(child));
        }

        continue;
      }

      (group?.items ?? result).push(toAction(source));
    }

    return result;
  });
</script>

<template>
  <craft-nav-list>
    <ActionList :actions="actions" as="craft-nav-item" mode="inline" />
  </craft-nav-list>
</template>
