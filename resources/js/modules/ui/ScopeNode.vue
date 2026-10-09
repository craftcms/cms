<script setup lang="ts">
  import UiNodeList from './UiNodeList.vue';
  import type {UiChange, UiNodePayload, UiPayload} from './types';

  defineProps<{
    node: UiNodePayload;
    values: UiPayload['values'];
    errors: UiPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: UiChange): void;
  }>();
</script>

<template>
  <UiNodeList
    :nodes="node.children ?? []"
    :values="values"
    :errors="errors"
    :touched-paths="touchedPaths"
    :scope="scope"
    :refreshable="refreshable"
    @change="emit('change', $event)"
  />
</template>
