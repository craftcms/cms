<script setup lang="ts">
  import type {FormNodePayload} from './types';

  defineProps<{
    node: FormNodePayload<{content: string; width: number}>;
  }>();
</script>

<template>
  <h2 :class="`width-${node.props.width}`" :data-form-node="node.uid">
    {{ node.props.content }}
  </h2>
</template>
