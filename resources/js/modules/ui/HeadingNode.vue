<script setup lang="ts">
  import type {UiNodePayload} from './types';

  type HeadingLevel = 1 | 2 | 3 | 4 | 5 | 6;

  defineProps<{
    node: UiNodePayload<{
      content: string;
      description?: string | null;
      width: number;
      level?: HeadingLevel;
    }>;
  }>();
</script>

<template>
  <div
    class="grid gap-1"
    :class="`width-${node.props.width}`"
    :data-ui-node="node.uid"
    data-heading-node
  >
    <component
      :is="`h${node.props.level ?? 2}`"
      :class="`cp-h${node.props.level ?? 2}`"
    >
      {{ node.props.content }}
    </component>
    <p v-if="node.props.description" class="my-0 text-sm">
      {{ node.props.description }}
    </p>
  </div>
</template>

<style scoped>
  /* More space above a heading than below it, so it groups with the fields
     it introduces. Grid items' margins don't collapse, so this adds to the
     field group's row gap. */
  craft-field-group > [data-heading-node]:not(:first-child) {
    margin-block-start: calc(var(--gap, calc(var(--c-spacing) * 6)) / 2);
  }
</style>
