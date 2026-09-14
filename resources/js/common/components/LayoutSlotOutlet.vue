<script setup lang="ts">
  import {computed} from 'vue';
  import {useLayoutSlotRegistry} from '@/common/composables/layoutSlots';

  const props = defineProps<{name: string}>();

  const registry = useLayoutSlotRegistry();
  const filled = computed(() => registry.has(props.name));
</script>

<template>
  <div
    :data-layout-scope="registry.scope"
    :data-layout-slot="name"
    :style="{display: filled ? 'contents' : 'none'}"
  ></div>
  <slot v-if="!filled"></slot>
</template>
