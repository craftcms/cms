<script setup lang="ts">
  withDefaults(
    defineProps<{
      illustrationSrc?: string;
      heading?: string;
    }>(),
    {illustrationSrc: '', heading: ''}
  );
</script>

<template>
  <div class="tw:grid tw:md:grid-cols-2 tw:gap-4 tw:items-center">
    <div class="tw:aspect-[352/455] tw:w-1/2 tw:md:w-3/4 tw:mx-auto">
      <img loading="lazy" :src="illustrationSrc" alt="" width="368" />
    </div>
    <div>
      <h2 class="tw:mb-4">{{ heading }}</h2>
      <div class="tw:grid tw:gap-3 tw:pr-6">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
