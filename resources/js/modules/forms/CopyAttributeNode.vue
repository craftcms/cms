<script setup lang="ts">
  // Leaf module, not the barrel — the barrel registers every `craft-*` element.
  import '@craftcms/ui/components/copy-attribute/copy-attribute';
  import type {FormNodePayload} from './types';

  // See ActionMenuNode.vue — this node is stateless, so `FormNode`'s shared
  // prop set shouldn't fall through onto the host element.
  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    node: FormNodePayload<{value: string}>;
  }>();
</script>

<template>
  <craft-copy-attribute
    :value="props.node.props.value"
    :data-form-node="props.node.uid"
  />
</template>
