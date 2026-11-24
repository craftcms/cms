<script setup lang="ts">
  import {computed} from 'vue';
  import {emphasisParts} from '@/common/utils/emphasis';

  /**
   * Shows a message with its `*Name*` markers as `<strong>`, as text nodes
   * rather than HTML, so a message can't inject markup.
   */
  const props = defineProps<{
    text: string;
  }>();

  const parts = computed(() => emphasisParts(props.text));
</script>

<template>
  <template v-for="(part, index) in parts" :key="index">
    <strong v-if="part.strong">{{ part.text }}</strong>
    <template v-else>{{ part.text }}</template>
  </template>
</template>
