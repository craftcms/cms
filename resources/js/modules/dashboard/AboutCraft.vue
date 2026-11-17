<script setup lang="ts">
  import AboutCraft from '@/common/components/AboutCraft.vue';
  import type {AboutCraftInfo} from '@/common/components/about-craft';
  import type {DashboardWidget} from './types';
  import {useWidgetColor} from './useWidgetColor';
  import {useWidgetHeader} from './useWidgetHeader';

  defineProps<{data: AboutCraftInfo; widget: DashboardWidget}>();

  const color = useWidgetColor();

  // The Craft CMS mark and name lead the card, so the title is only heard.
  const {titleId} = useWidgetHeader({ownTitle: true});
</script>

<template>
  <craft-card :data-color="color">
    <slot name="header" />
    <div class="body">
      <h2 :id="titleId" class="sr-only">{{ widget.title }}</h2>
      <AboutCraft :info="data" class="about-craft-widget" />
    </div>
  </craft-card>
</template>

<style scoped>
  .about-craft-widget {
    /* The card body's padding (see card.styles.ts), which the divider reaches
       past to the card's edges */
    --about-craft-bleed: clamp(
      var(--c-spacing-lg),
      2cqi + var(--c-spacing-md),
      calc(var(--c-spacing) * 6)
    );
  }
</style>
