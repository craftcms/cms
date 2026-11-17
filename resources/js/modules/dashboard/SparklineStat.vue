<script setup lang="ts">
  import {computed} from 'vue';
  import AreaChart from '@/modules/chart/AreaChart.vue';
  import type {ChartData, ChartScale} from '@/modules/chart/types';
  import {isTintedColor, useWidgetColor} from './useWidgetColor';

  /**
   * A figure with a sparkline of it over time, filling a dashboard widget's
   * body below the figure: a title such as “264 new members”, a quieter
   * caption such as “Last 30 days”, and an `AreaChart` drawn as a sparkline,
   * edge to edge.
   *
   * The title can stand in for the widget's own, styled as other widgets'
   * titles are: use `useWidgetHeader({ownTitle: true})`, and pass the
   * `titleId` it returns as `headingId`, which makes the title the widget's
   * heading. It stays clear of the header's buttons.
   */
  defineProps<{
    title: string;
    /** Draws the title as the widget's heading, with this ID. */
    headingId?: string | null;
    caption?: string | null;
    data: ChartData;
    scale?: ChartScale;
    /** Names the chart for screen readers. */
    label: string;
    /** Returns a value's tooltip text. See `AreaChart`. */
    tooltip?: (date: string, value: number) => string;
  }>();

  const widgetColor = useWidgetColor();

  /**
   * A tinted widget's chart takes the widget's color, as its text does.
   * Neutral widgets keep the chart's own blue.
   */
  const chartColor = computed(() =>
    isTintedColor(widgetColor.value) ? 'var(--c-color-fill-loud)' : undefined
  );
</script>

<template>
  <div class="sparkline-stat">
    <div class="sparkline-stat__figure">
      <component
        :is="headingId ? 'h2' : 'p'"
        :id="headingId ?? undefined"
        class="sparkline-stat__title cp-h3"
        >{{ title }}</component
      >
      <p v-if="caption" class="sparkline-stat__caption">{{ caption }}</p>
    </div>
    <AreaChart
      :data="data"
      :scale="scale"
      :label="label"
      :color="chartColor"
      :tooltip="tooltip"
      class="sparkline-stat__chart"
      sparkline
    />
  </div>
</template>

<style scoped>
  .sparkline-stat {
    /* The card body's padding (see card.styles.ts), which the chart reaches
       past to the card's edges */
    --_card-padding: clamp(
      var(--c-spacing-lg),
      2cqi + var(--c-spacing-md),
      calc(var(--c-spacing) * 6)
    );
  }

  .sparkline-stat__figure {
    padding-block-end: calc(var(--c-spacing) * 3);
  }

  /* Clear of a header button beside it, such as the settings button. */
  .sparkline-stat__title {
    margin: 0;
    padding-inline-end: calc(var(--c-size-control-sm) + var(--c-spacing-sm));
  }

  .sparkline-stat__caption {
    margin: 0;
    font-size: var(--c-text-sm);
    line-height: 1.3;
    color: var(--c-text-quiet);
  }

  /* As tall as a third of the card's width, so wider widgets get taller
     charts rather than flatter ones. */
  .sparkline-stat__chart {
    height: max(calc(128rem / 16), 33cqi);
    margin-inline: calc(var(--_card-padding) * -1);
    margin-block-end: calc(var(--_card-padding) * -1);
  }
</style>
