<script setup lang="ts">
  // Leaf module, not the barrel — the barrel registers every `craft-*` element.
  // `craft-action-menu`'s `actions` is a JS property (`attribute: false`), so
  // the element must be defined before Vue patches it.
  import '@craftcms/ui/components/action-menu/action-menu';
  import {computed, inject} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';
  import {FieldActionItems} from './runtime';
  import type {FormNodePayload} from './types';

  type ActionMenuNodeProps = {
    label?: string;
    icon?: string;
    items?: ActionItems;
  };

  // `FormNode` hands every node the same prop set (values, errors, scope, …).
  // This node is stateless, so the extras would otherwise fall through as
  // attributes onto the host element.
  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    node: FormNodePayload<ActionMenuNodeProps>;
  }>();

  // A field's control may say what its menu should read (see FieldActionItems).
  const resolveItems = inject(FieldActionItems, undefined);
  const items = computed(() => {
    const items = props.node.props.items ?? [];

    return resolveItems?.value?.(items) ?? items;
  });
</script>

<template>
  <ActionMenu
    :actions="items"
    :icon="props.node.props.icon"
    :label="props.node.props.label"
    :data-form-node="props.node.uid"
  />
</template>
