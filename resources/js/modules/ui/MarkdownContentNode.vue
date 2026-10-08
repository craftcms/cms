<script setup lang="ts">
  import type {UiNodePayload} from './types';

  const props = defineProps<{
    node: UiNodePayload<{
      html: string;
      displayInPane: boolean;
      width: number;
    }>;
  }>();
</script>

<template>
  <div
    class="markdown"
    :class="[
      node.props.displayInPane ? 'pane' : undefined,
      `width-${node.props.width}`,
    ]"
    :data-ui-node="node.uid"
    v-html="node.props.html"
  ></div>
</template>
