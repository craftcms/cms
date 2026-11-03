<script setup lang="ts">
  const {url, size = 'md'} = defineProps<{
    url: string | null;
    size?: 'md' | 'lg';
  }>();
</script>

<template>
  <div class="plugin-icon" :class="`plugin-icon--${size}`">
    <img v-if="url" :src="url" alt="" />
    <craft-icon v-else name="plug"></craft-icon>
  </div>
</template>

<style scoped>
  .plugin-icon {
    --size: 4rem;

    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    inline-size: var(--size);
    block-size: var(--size);
    border-radius: var(--c-radius-md);
    color: var(--c-text-quiet);
    font-size: calc(var(--size) / 2.5);
  }

  .plugin-icon--lg {
    --size: 6rem;
  }

  .plugin-icon:not(:has(img)) {
    background: var(--c-color-neutral-fill-quiet);
  }

  .plugin-icon img {
    inline-size: 100%;
    block-size: 100%;
  }
</style>
