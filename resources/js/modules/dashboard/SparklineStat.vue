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
   * The title is drawn large enough to stand in for the widget's own, which
   * a widget can hide with `useWidgetHeader({titleHidden: true})`. Give the
   * widget's card `--widget-title-line-size: calc(var(--c-text-xl) * 1.2)`,
   * this title's line height, so the header's buttons center on it. The
   * title stays clear of them.
   */
  defineProps<{
    title: string;
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
    <p class="sparkline-stat__figure">
      <span class="sparkline-stat__title cp-h1">{{ title }}</span
      ><template v-if="caption"
        ><span class="sr-only">, </span
        ><span class="sparkline-stat__caption">{{ caption }}</span></template
      >
    </p>
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
    display: grid;
    justify-items: start;
    margin: 0;
    padding-block-end: calc(var(--c-spacing) * 3);
  }

  /* Clear of a header button beside it, such as the settings button. */
  .sparkline-stat__title {
    line-height: 1.2;
    padding-inline-end: calc(var(--c-size-control-sm) + var(--c-spacing-sm));
  }

  .sparkline-stat__caption {
    font-size: var(--c-text-sm);
    line-height: 1.3;
    color: var(--c-text-quiet);
  }

  .sparkline-stat__chart {
    height: calc(128rem / 16);
    margin-inline: calc(var(--_card-padding) * -1);
    margin-block-end: calc(var(--_card-padding) * -1);
  }
</style>
