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
  <craft-pane
    v-if="node.props.displayInPane"
    appearance="outline-fill"
    class="markdown prose"
    :class="`width-${node.props.width}`"
    :data-ui-node="node.uid"
    v-html="node.props.html"
  ></craft-pane>
  <div
    v-else
    class="markdown prose"
    :class="`width-${node.props.width}`"
    :data-ui-node="node.uid"
    v-html="node.props.html"
  ></div>
</template>
