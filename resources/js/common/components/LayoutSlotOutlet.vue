<script setup lang="ts">
  import {computed} from 'vue';
  import {hasLayoutSlot} from '@/common/composables/layoutSlots';

  const props = defineProps<{name: string}>();

  const filled = computed(() => hasLayoutSlot(props.name));
</script>

<template>
  <div
    :data-layout-slot="name"
    :style="{display: filled ? 'contents' : 'none'}"
  ></div>
  <slot v-if="!filled"></slot>
</template>
