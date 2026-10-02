<script setup lang="ts">
  import type {FormNodePayload} from './types';

  const props = defineProps<{
    node: FormNodePayload<{
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
    :data-form-node="node.uid"
    v-html="node.props.html"
  ></div>
</template>
