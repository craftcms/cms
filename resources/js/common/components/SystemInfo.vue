<script setup lang="ts">
  import useCraftData from '@/common/composables/useCraftData';
  import {computed} from 'vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';

  const {iconOnly = false} = defineProps<{iconOnly?: boolean}>();

  const craftData = useCraftData();
  const system = computed(() => craftData.system);
  const site = computed(() => craftData.site);
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
    <div class="system-info__icon">
      <DynamicHtmlRenderer
        v-if="system.icon"
        :html="system.icon"
      ></DynamicHtmlRenderer>
    </div>
    <div class="system-info__name" :class="{'cp:sr-only': iconOnly}">
      {{ system.name }}
    </div>
  </component>
</template>

<style scoped lang="css">
  .system-info {
    --system-info-icon-size: calc(
      var(--c-size-touch-target) - var(--c-spacing-sm)
    );
    display: grid;
    grid-template-columns: var(--system-info-icon-size) auto;
    gap: var(--c-spacing-md);
    align-items: center;
    color: currentColor;
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
  }

  :deep(svg) {
    fill: currentColor;
    max-width: 100%;
    height: auto;
  }
</style>
