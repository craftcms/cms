<script setup lang="ts">
  import type {UiNodePayload} from './types';

  defineProps<{node: UiNodePayload}>();
</script>

<template>
  <div class="line-break" :data-ui-node="node.uid"></div>
</template>
