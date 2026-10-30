<script setup lang="ts">
   /**
   * A block that stands in for content still loading. Size it with classes or
   * styles; it's a line of text by default. Hidden from assistive tech, since
   * the region it fills says it's busy instead.
   */
</script>

<template>
  <span class="placeholder-shape" aria-hidden="true"></span>
</template>

<style scoped>
  :where(.placeholder-shape) {
    display: block;
    inline-size: 100%;
    block-size: 0.75rem;
    /* Forced colors drop the fill but draw a transparent border. */
    border: 1px solid transparent;
    border-radius: var(--c-radius-sm);
    background-color: var(--c-color-neutral-fill-quiet);
  }

  @media (prefers-reduced-motion: no-preference) {
    .placeholder-shape {
      animation: placeholder-shape-pulse 1.5s ease-in-out infinite;
    }
  }

  @keyframes placeholder-shape-pulse {
    50% {
      opacity: 0.5;
    }
  }
</style>
