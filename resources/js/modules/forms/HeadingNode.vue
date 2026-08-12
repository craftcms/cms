<script setup lang="ts">
  import type {FormNodePayload} from './types';

  type HeadingLevel = 1 | 2 | 3 | 4 | 5 | 6;

  defineProps<{
    node: FormNodePayload<{
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
    :data-form-node="node.uid"
  >
    <component :is="`h${node.props.level ?? 2}`" class="my-0">
      {{ node.props.content }}
    </component>
    <p
      v-if="node.props.description"
      class="my-0 text-sm text-neutral-text-quiet"
    >
      {{ node.props.description }}
    </p>
  </div>
</template>
