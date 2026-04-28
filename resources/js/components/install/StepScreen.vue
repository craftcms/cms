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
  <div class="grid md:grid-cols-2 gap-4 items-center">
    <div class="aspect-[352/455] w-1/2 md:w-3/4 mx-auto">
      <img loading="lazy" :src="illustrationSrc" alt="" width="368" />
    </div>
    <div>
      <h2 class="mb-4">{{ heading }}</h2>
      <div class="grid gap-3 pr-6">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
