<script setup lang="ts">
  import type {FormNodePayload} from './types';

  type HeadingLevel = 1 | 2 | 3 | 4 | 5 | 6;

  defineProps<{
    node: FormNodePayload<{
      content: string;
      width: number;
      level?: HeadingLevel;
    }>;
  }>();
</script>

<template>
  <component
    :is="`h${node.props.level ?? 2}`"
    :class="`width-${node.props.width}`"
    :data-form-node="node.uid"
  >
    {{ node.props.content }}
  </component>
</template>
