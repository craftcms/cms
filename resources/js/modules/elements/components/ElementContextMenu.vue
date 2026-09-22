<script setup lang="ts">
  import {computed} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {
    ActionItem,
    ActionItemGroup,
    ActionItemLink,
  } from '@/common/types';
  import type {ElementContextMenuItem} from '@/modules/elements/composables/useElementEditor';

  const props = defineProps<{
    label: string;
    items: Array<ElementContextMenuItem>;
  }>();

  /**
   * The server sends a flat list, with each heading followed by the links it
   * heads, so those runs become groups and render their headings like any
   * other menu's.
   */
  const actions = computed<Array<ActionItem>>(() => {
    const result: Array<ActionItem> = [];
    let group: ActionItemGroup | null = null;

    for (const item of props.items) {
      if (item.type === 'hr') {
        group = null;
        result.push({type: 'hr'});
      } else if (item.type === 'heading') {
        group = {type: 'group', heading: item.label, items: []};
        result.push(group);
      } else {
        const link: ActionItemLink = {
          type: 'link',
          href: item.href!,
          label: item.label!,
          variant: item.selected ? 'accent' : undefined,
        };

        if (group) {
          group.items.push(link);
        } else {
          result.push(link);
        }
      }
    }

    return result;
  });
</script>

<template>
  <ActionMenu :actions="actions" :label="label" icon="chevron-down" />
</template>
