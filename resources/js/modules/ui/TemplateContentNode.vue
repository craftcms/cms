<script setup lang="ts">
  import type {UiNodePayload} from './types';

  defineProps<{
    node: UiNodePayload<{
      html: string;
      width: number;
    }>;
  }>();
</script>

<template>
  <div
    inert
    :class="`width-${node.props.width}`"
    :data-ui-node="node.uid"
    v-html="node.props.html"
  ></div>
</template>
