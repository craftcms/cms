<script setup lang="ts">
  /** A place the page rendering the UI fills from its UiRenderer slot of the same name. */
  import {computed} from 'vue';
  import {useUiRenderer} from './renderer/context';
  import type {UiNodePayload} from './types';

  const props = defineProps<{node: UiNodePayload}>();

  const renderer = useUiRenderer();
  const slot = computed(
    () => renderer.overrides[String(props.node.props.name)]
  );
</script>

<template>
  <div v-if="slot" class="ui-slot" :data-ui-node="node.uid">
    <component :is="slot" />
  </div>
</template>
