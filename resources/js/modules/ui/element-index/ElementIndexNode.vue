<script setup lang="ts">
  import type {NestedContentIndexData} from '../nested-elements/nested-elements';
  import type {UiNodePayload} from '../types';
  import EmbeddedElementIndex from './EmbeddedElementIndex.vue';

  type ElementIndexNodeProps = {
    token: string;
    elementType: string;
    storageKey?: string | null;
    initial: NestedContentIndexData;
  };

  defineProps<{
    node: UiNodePayload<ElementIndexNodeProps>;
  }>();
</script>

<template>
  <EmbeddedElementIndex
    :token="node.props.token"
    :initial="node.props.initial"
    :element-type="node.props.elementType"
    :storage-key="
      node.props.storageKey ??
      [node.props.elementType, node.uid].filter(Boolean).join('.')
    "
  />
</template>
