<script setup lang="ts">
  import useCraftData from '@/composables/useCraftData';
  import {computed} from 'vue';

  const craftData = useCraftData();
  const system = computed(() => craftData.system);
  const site = computed(() => craftData.site);
  const tag = computed(() => (site.url ? 'a' : 'div'));
</script>

<template>
  <component
    :is="tag"
    class="system-info"
    :href="site.url || ''"
    :target="site.url ? '_blank' : ''"
    tabindex="0"
  >
    <div class="system-info__icon" v-html="system.icon"></div>
    <div class="system-info__name">{{ system.name }}</div>
  </component>
</template>

<style scoped lang="css">
  .system-info {
    display: grid;
    grid-template-columns: calc(32rem / 16) auto;
    gap: var(--c-spacing-md);
    align-items: center;
    color: currentColor;
  }

  .system-info__icon {
    aspect-ratio: 1;
  }

  :deep(svg) {
    fill: currentColor;
    max-width: 100%;
    height: auto;
  }
</style>
