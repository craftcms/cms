<script setup lang="ts">
  import type {UiNodePayload} from './types';

  defineProps<{node: UiNodePayload}>();
</script>

<template>
  <hr :data-ui-node="node.uid" />
</template>
