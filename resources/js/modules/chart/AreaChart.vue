<script setup lang="ts">
  import {onBeforeUnmount, onMounted, useTemplateRef, watch} from 'vue';
  import {useResizeObserver} from '@vueuse/core';
  import {isRtl} from '@/common/craft/direction';
  import {Area, DataTable, chartsUtils} from './chart';
  import type {ChartData, ChartScale} from './types';

  const props = withDefaults(
    defineProps<{
      data: ChartData;
      /** Names the chart for screen readers. */
      label: string;
      scale?: ChartScale;
      /** A d3-format specifier for the values, such as `',.0f'`. */
      valueFormat?: string;
      color?: string;
    }>(),
    {scale: 'day', valueFormat: undefined, color: undefined}
  );

  const container = useTemplateRef<HTMLElement>('container');
  let chart: Area | undefined;

  function valueFormatter(area: Area): (value: number) => string {
    if (props.valueFormat) {
      return area.formatLocale.format(props.valueFormat);
    }

    const type = area.dataTable.columns[1]?.type;
    if (type !== 'number') {
      return chartsUtils.getNumberFormatter(
        area.formatLocale,
        type,
        area.settings
      );
    }

    // Counts read better without decimals, but halfway ticks can need one
    return (value) =>
      area.formatLocale.format(Number.isInteger(value) ? ',.0f' : ',.1f')(
        value
      );
  }

  function draw(): void {
    if (!container.value) return;

    chart ??= new Area(container.value, {
      yAxis: {formatter: valueFormatter},
    });

    // DataTable parses rows in place
    const data = {
      columns: props.data.columns,
      rows: props.data.rows.map((row) => [...row]),
    };

    chart.draw(new DataTable(data), {
      orientation: isRtl() ? 'rtl' : 'ltr',
      dataScale: props.scale,
      ...(props.color ? {colors: [props.color]} : {}),
    });
  }

  useResizeObserver(container, () => chart?.resize());
  onMounted(draw);
  watch(() => [props.data, props.scale, props.valueFormat, props.color], draw);
  onBeforeUnmount(() => chart?.destroy());
</script>

<template>
  <div ref="container" class="area-chart" role="img" :aria-label="label"></div>
</template>

<style>
  .area-chart {
    position: relative;
    height: 12.5rem;
  }

  .area-chart .cp-chart {
    position: relative;
    height: 100%;
  }

  .area-chart svg {
    display: block;
    width: 100%;
    height: 100%;
  }

  .area-chart :is(.axis path, .axis line) {
    fill: none;
    stroke: var(--c-color-neutral-border-quiet);
    shape-rendering: crispedges;
  }

  .area-chart :is(.ticks-axis path, .ticks-axis line) {
    fill: none;
    stroke: none;
  }

  .area-chart .ticks-axis .tick text {
    fill: var(--c-text-default);
    font-size: 11px;
  }

  .area-chart .grid-line .tick line {
    stroke: var(--c-color-neutral-border-quiet);
    shape-rendering: crispedges;
  }

  .area-chart .grid-line path {
    stroke-width: 0;
  }

  .area-chart .tooltip {
    position: absolute;
    z-index: 1;
    padding-block: 7px;
    padding-inline: 10px;
    border-radius: var(--radius-sm);
    background: var(--c-surface-overlay);
    box-shadow: var(--shadow-md);
    pointer-events: none;
    user-select: none;
  }
</style>
