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
  <div class="cp:grid cp:md:grid-cols-2 cp:gap-4 cp:items-center">
    <div class="aspect-[352/455] cp:w-1/2 cp:md:w-3/4 cp:mx-auto">
      <img loading="lazy" :src="illustrationSrc" alt="" width="368" />
    </div>
    <div>
      <h2 class="cp:mb-4">{{ heading }}</h2>
      <div class="cp:grid cp:gap-3 cp:md:pr-6">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
