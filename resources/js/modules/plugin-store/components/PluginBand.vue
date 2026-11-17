<script setup lang="ts">
  /** A band tinted in the plugin's colors, from its own icon blown up and blurred. */
  const {iconUrl} = defineProps<{iconUrl: string | null}>();
</script>

<template>
  <div class="plugin-band" aria-hidden="true">
    <img v-if="iconUrl" :src="iconUrl" alt="" />
  </div>
</template>

<style scoped>
  .plugin-band {
    position: relative;
    block-size: 2.5rem;
    overflow: hidden;
    background: var(--c-color-neutral-fill-quiet);
  }

  .plugin-band img {
    position: absolute;
    inset: 0;
    inline-size: 100%;
    block-size: 100%;
    object-fit: cover;
    object-position: center 12%;
    filter: blur(1.5rem) saturate(1.6) brightness(1.1);
    opacity: 0.4;
    transform: scale(1.4);
  }
</style>
