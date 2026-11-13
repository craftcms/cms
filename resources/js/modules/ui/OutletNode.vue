<script setup lang="ts">
  import {onBeforeUnmount, onMounted, ref} from 'vue';
  import {registerOutlet} from './outlets';
  import type {UiNodePayload} from './types';

  /** A named place in the form that something rendered elsewhere moves to. */
  const props = defineProps<{node: UiNodePayload<{name: string}>}>();
  const element = ref<HTMLElement>();
  let release: (() => void) | undefined;

  onMounted(() => {
    release = registerOutlet(props.node.props.name, element.value!);
  });
  onBeforeUnmount(() => release?.());
</script>

<template>
  <div
    ref="element"
    :data-ui-node="node.uid"
    :data-ui-outlet="node.props.name"
  ></div>
</template>
