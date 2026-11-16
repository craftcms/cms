<script setup lang="ts">
  import useCraftData from '@/common/composables/useCraftData';
  import {computed} from 'vue';

  const {iconOnly = false} = defineProps<{iconOnly?: boolean}>();

  const {system, site} = useCraftData();
  const tag = computed(() => (site.value?.url ? 'a' : 'div'));
</script>

<template>
  <component
    :is="tag"
    class="system-info"
    :class="{'system-info--icon-only': iconOnly}"
    :href="site?.url"
    :target="site?.url ? '_blank' : null"
  >
    <!-- Not compiled as a Vue template, which would drop an SVG's <style> -->
    <div
      class="system-info__icon"
      aria-hidden="true"
      v-html="system.icon"
    ></div>
    <div class="system-info__name" :class="{'sr-only': iconOnly}">
      {{ system.name }}
    </div>
  </component>
</template>

<style scoped lang="css">
  .system-info {
    --system-info-icon-size: var(--cp-rail-item-size);
    display: grid;
    grid-template-columns: var(--system-info-icon-size) minmax(0, 1fr);
    gap: var(--c-spacing-md);
    align-items: center;
    color: currentColor;
    text-decoration: none;
    border-radius: var(--c-radius-md);
  }

  /* Same icon track as the expanded state, so the logo doesn't change size
     when the sidebar collapses — just the name's column goes away. */
  .system-info--icon-only {
    grid-template-columns: var(--system-info-icon-size);
    justify-content: center;
  }

  .system-info__icon {
    display: flex;
    aspect-ratio: 1;
    border-radius: var(--c-radius-md);
    overflow: hidden;
  }

  .system-info__name {
    font-weight: var(--font-weight-semibold);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  :deep(:is(svg, img)) {
    max-width: 100%;
    height: auto;
  }
</style>
