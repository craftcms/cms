<script setup lang="ts">
  import useCraftData from '@/common/composables/useCraftData';
  import {computed} from 'vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';

  const craftData = useCraftData();
  const system = computed(() => craftData.system);
  const site = computed(() => craftData.site);
  const tag = computed(() => (site.value?.url ? 'a' : 'div'));
</script>

<template>
  <component
    :is="tag"
    class="system-info"
    :href="site?.url"
    :target="site?.url ? '_blank' : null"
  >
    <div class="system-info__icon">
      <DynamicHtmlRenderer
        v-if="system.icon"
        :html="system.icon"
      ></DynamicHtmlRenderer>
    </div>
    <div class="system-info__name">{{ system.name }}</div>
  </component>
</template>

<style scoped lang="css">
  .system-info {
    display: grid;
    grid-template-columns: calc(24rem / 16) auto;
    gap: var(--c-spacing-md);
    align-items: center;
    color: currentColor;
  }

  .system-info__icon {
    display: flex;
    aspect-ratio: 1;
  }

  :deep(svg) {
    fill: currentColor;
    max-width: 100%;
    height: auto;
  }
</style>
