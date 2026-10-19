<script setup lang="ts">
  // Leaf module, not the barrel — the barrel registers every `craft-*` element.
  // `craft-action-menu`'s `actions` is a JS property (`attribute: false`), so
  // the element must be defined before Vue patches it.
  import '@craftcms/ui/components/action-menu/action-menu';
  import {computed, inject} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';
  import {FieldActionItems} from './runtime';
  import type {UiNodePayload} from './types';

  type ActionMenuNodeProps = {
    label?: string;
    icon?: string;
    items?: ActionItems;
  };

  // `UiNode` hands every node the same prop set (values, errors, scope, …).
  // This node is stateless, so the extras would otherwise fall through as
  // attributes onto the host element.
  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    node: UiNodePayload<ActionMenuNodeProps>;
    /** The UI scope the node renders in; its uid is only unique within it. */
    scope?: string[];
  }>();

  // Repeated scoped content, such as Matrix blocks, renders the same uids once
  // per scope, so the scope is part of what identifies this menu.
  const focusKey = computed(() =>
    JSON.stringify([...(props.scope ?? []), props.node.uid])
  );

  // A field's control may say what its menu should read (see FieldActionItems).
  const resolveItems = inject(FieldActionItems, undefined);
  const items = computed(() => {
    const items = props.node.props.items ?? [];

    return resolveItems?.value?.(items) ?? items;
  });
</script>

<template>
  <!-- The scope and uid stay the same across re-renders, so `data-focus-key`
    lets focus find this menu's button again after one (see `focusReturnFor()`). -->
  <ActionMenu
    :actions="items"
    :icon="props.node.props.icon"
    :label="props.node.props.label"
    :data-ui-node="props.node.uid"
    :data-focus-key="focusKey"
  />
</template>
