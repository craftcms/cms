<script setup lang="ts">
  import {computed, useTemplateRef, watch} from 'vue';
  import {useLayoutSlotRegistry} from '@/common/composables/layoutSlots';

  const props = defineProps<{name: string}>();

  const registry = useLayoutSlotRegistry();
  const filled = computed(() => registry.has(props.name));

  // Tell slots when the target they teleport into is a new element.
  const target = useTemplateRef<HTMLElement>('target');
  watch(
    target,
    (element, previous) => {
      if (element && element !== previous) {
        registry.targetMounted(props.name);
      }
    },
    {flush: 'sync'}
  );
</script>

<template>
  <div
    ref="target"
    :data-layout-scope="registry.scope"
    :data-layout-slot="name"
    :style="{display: filled ? 'contents' : 'none'}"
  ></div>
  <slot v-if="!filled"></slot>
</template>
