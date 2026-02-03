<script setup lang="ts">
  import {computed} from 'vue';

  const props = defineProps<{
    variant: 'success' | 'danger' | 'warning' | 'default';
  }>();

  const indicatorVariant = computed(() => {
    if (props.variant === 'default') {
      return 'empty';
    }

    return props.variant;
  });
</script>

<template>
  <craft-callout :variant="variant" size="small" class="items-center" inline>
    <craft-indicator slot="icon" :variant="indicatorVariant"></craft-indicator>
    <span><slot></slot></span>
  </craft-callout>
</template>

<style scoped lang="scss"></style>
