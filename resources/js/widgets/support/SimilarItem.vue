<script setup lang="ts">
  defineProps<{
    link: string;
    status: 'success' | 'error' | 'neutral';
  }>();
</script>

<template>
  <a
    :href="link"
    target="_blank"
    class="tw:flex tw:gap-2 tw:items-start tw:p-2 tw:hover:bg-gray-200"
  >
    <span
      :class="{
        'tw:w-2.5 tw:h-2.5 tw:inline-block tw:rounded-full tw:border tw:mt-1.5': true,
        'tw:shrink-0': true,
        'tw:bg-green-500 tw:border-green-700': status === 'success',
        'tw:bg-red-500 tw:border-red-700': status === 'error',
        'tw:border-gray-700': status === 'neutral',
      }"
    ></span>
    <slot></slot>
  </a>
</template>

<style scoped lang="scss"></style>
