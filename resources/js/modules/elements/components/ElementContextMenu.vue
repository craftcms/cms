<script setup lang="ts">
  import {computed, h} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import type {ElementContextMenuItem} from '@/modules/elements/composables/useElementEditor';

  const props = defineProps<{
    label: string;
    items: Array<ElementContextMenuItem>;
  }>();

  /**
   * The action menu's item contract has no nested-group shape, so group
   * headings ride in as `display` items — the documented escape hatch for
   * arbitrary content — while drafts and revisions stay ordinary links.
   */
  const actions = computed<Array<ActionItem>>(() =>
    props.items.map((item): ActionItem => {
      if (item.type === 'hr') {
        return {type: 'hr'};
      }

      if (item.type === 'heading') {
        return {
          type: 'display',
          is: () =>
            h(
              'h2',
              {
                class:
                  'px-2 pt-2 pb-1 text-xs font-bold text-neutral-text-quiet',
              },
              item.label
            ),
        };
      }

      return {
        type: 'link',
        href: item.href!,
        label: item.label!,
        variant: item.selected ? 'accent' : undefined,
      };
    })
  );
</script>

<template>
  <ActionMenu :actions="actions" :label="label" icon="chevron-down" />
</template>
