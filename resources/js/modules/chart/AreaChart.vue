<script setup lang="ts">
  import {onBeforeUnmount, onMounted, ref, useTemplateRef, watch} from 'vue';
  import {useResizeObserver} from '@vueuse/core';
  import {isRtl} from '@/common/craft/direction';
  import {Area, BaseChart, DataTable, chartsUtils} from './chart';
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
      /**
       * Draws just a thin, smoothly curved line over a faint gradient, edge
       * to edge, with no axis labels, gridlines, or dots. Values still show
       * in tooltips, and in the table for screen readers.
       */
      sparkline?: boolean;
      /**
       * Returns a tooltip's text, given the value's date, formatted as the
       * chart labels it, and its number. Tooltips show the date and the
       * formatted value on two lines without it.
       */
      tooltip?: (date: string, value: number) => string;
    }>(),
    {
      scale: 'day',
      valueFormat: undefined,
      color: undefined,
      sparkline: false,
      tooltip: undefined,
    }
  );

  /**
   * Settings merge into the last draw's, so each shape sets every setting
   * the other changes.
   */
  function shapeSettings(): Record<string, unknown> {
    return props.sparkline
      ? {
          ticks: false,
          plots: false,
          lineWidth: 1.5,
          curve: 'smooth',
          fill: 'gradient',
          fadeEdges: 16,
          // Room for the line at the highest point
          margin: {top: 4, right: 0, bottom: 0, left: 0},
          xAxis: {showAxis: false},
          yAxis: {gridlines: false},
        }
      : {
          ticks: true,
          plots: true,
          lineWidth: Area.defaults.lineWidth,
          curve: Area.defaults.curve,
          fill: Area.defaults.fill,
          fadeEdges: Area.defaults.fadeEdges,
          margin: {...Area.defaults.margin},
          xAxis: {showAxis: true},
          yAxis: {gridlines: true},
        };
  }

  const container = useTemplateRef<HTMLElement>('container');
  let chart: Area | undefined;
  /** The drawn rows, formatted as the chart labels them, for screen readers. */
  const tableRows = ref<Array<[string, string]>>([]);

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

    const dataTable = new DataTable(data);
    chart.draw(dataTable, {
      orientation: isRtl() ? 'rtl' : 'ltr',
      dataScale: props.scale,
      // Settings merge into the last draw's, so an unset color must reset it
      colors: props.color ? [props.color] : BaseChart.defaults.colors,
      ...shapeSettings(),
      // Reset like the colors, since settings merge into the last draw's
      tipText: props.tooltip
        ? (date: Date, value: number) =>
            props.tooltip!(chart!.getXFormatter()(date), value)
        : null,
    });

    const formatX = chart.getXFormatter();
    const formatY = chart.getYFormatter();
    tableRows.value = dataTable.rows.map(([x, y]: [Date, number]) => [
      formatX(x),
      formatY(y),
    ]);
  }

  useResizeObserver(container, () => chart?.resize());
  onMounted(draw);
  // Deep, so rows pushed or changed in place redraw too
  watch(
    () => [
      props.data,
      props.scale,
      props.valueFormat,
      props.color,
      props.sparkline,
      props.tooltip,
    ],
    draw,
    {deep: true}
  );
  onBeforeUnmount(() => chart?.destroy());
</script>

<template>
  <div class="area-chart">
    <div ref="container" class="area-chart-plot" aria-hidden="true"></div>
    <table class="sr-only">
      <caption>
        {{ label }}
      </caption>
      <thead>
        <tr>
          <th v-for="(column, index) in data.columns" :key="index" scope="col">
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="([x, y], index) in tableRows" :key="index">
          <th scope="row">{{ x }}</th>
          <td>{{ y }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style>
  .area-chart {
    position: relative;
    height: 12.5rem;
  }

  .area-chart-plot,
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

  /* Drawn like the control panel's other tooltips (see craft-tooltip). */
  .area-chart .tooltip {
    position: absolute;
    z-index: 1;
    padding-block: calc(4rem / 16);
    padding-inline: calc(8rem / 16);
    border: 1px solid var(--c-color-black-border-loud);
    border-radius: var(--c-radius-sm);
    background-color: var(--c-color-black-fill-loud);
    box-shadow: var(--c-shadow-md);
    color: var(--c-color-black-on-loud);
    font-family: var(--c-font-body);
    font-size: var(--c-text-base);
    font-weight: 400;
    line-height: 1.3;
    white-space: nowrap;
    pointer-events: none;
    user-select: none;
  }
</style>
