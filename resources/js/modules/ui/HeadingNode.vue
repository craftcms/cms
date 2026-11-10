<script setup lang="ts">
  import type {UiNodePayload} from './types';

  type HeadingLevel = 1 | 2 | 3 | 4 | 5 | 6;

  /** Every level sits below the page title, which is `text-xl`. */
  const sizes: Record<HeadingLevel, string> = {
    1: 'text-xl',
    2: 'text-lg',
    3: 'text-base',
    4: 'text-sm',
    5: 'text-sm',
    6: 'text-xs',
  };

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
  >
    <component
      :is="`h${node.props.level ?? 2}`"
      class="my-0"
      :class="sizes[node.props.level ?? 2]"
    >
      {{ node.props.content }}
    </component>
    <p v-if="node.props.description" class="my-0 text-sm">
      {{ node.props.description }}
    </p>
  </div>
</template>
