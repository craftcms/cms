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
    class="cp:grid cp:gap-1"
    :class="`width-${node.props.width}`"
    :data-form-node="node.uid"
  >
    <component :is="`h${node.props.level ?? 2}`" class="cp:my-0">
      {{ node.props.content }}
    </component>
    <p
      v-if="node.props.description"
      class="cp:my-0 cp:text-sm cp:text-on-neutral-quiet"
    >
      {{ node.props.description }}
    </p>
  </div>
</template>
