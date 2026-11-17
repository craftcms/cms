<script setup lang="ts">
  import {onBeforeUnmount, onMounted, ref, shallowRef, watch} from 'vue';
  import {actionClient, t} from '@craftcms/ui';
  import {data as chartData} from '@actions/Dashboard/Widgets/NewUsersController';
  import AreaChart from '@/modules/chart/AreaChart.vue';
  import type {ChartData, ChartScale} from '@/modules/chart/types';
  import {useWidgetColor} from './useWidgetColor';

  const color = useWidgetColor();

  const props = defineProps<{
    data: {dateRange: string; userGroupId: number | null};
  }>();

  const error = ref('');
  const loading = ref(true);
  const chart = shallowRef<{data: ChartData; scale: ChartScale}>();
  let request: AbortController | undefined;

  onBeforeUnmount(() => request?.abort());

  /**
   * Fetches the chart's data and draws it. The dashboard reloads its widgets
   * when elements are saved, such as new users, so it's fetched again then,
   * over the chart already shown.
   */
  async function load(): Promise<void> {
    request?.abort();
    const current = (request = new AbortController());
    const days: Record<string, [number, number]> = {
      d7: [6, 0],
      d30: [30, 0],
      lastweek: [13, 7],
      lastmonth: [60, 30],
    };
    const [start, end] = days[props.data.dateRange] ?? days.d7!;

    try {
      const {data} = await actionClient.post(
        chartData.url(),
        {
          userGroupId: props.data.userGroupId,
          startDate: Math.floor(Date.now() / 1000) - start * 86400,
          endDate: Math.floor(Date.now() / 1000) - end * 86400,
        },
        {signal: current.signal}
      );

      error.value = '';
      chart.value = {data: data.dataTable, scale: data.scale};
    } catch {
      if (!current.signal.aborted) error.value = t('A server error occurred.');
    } finally {
      if (!current.signal.aborted) loading.value = false;
    }
  }

  onMounted(load);
  watch(() => props.data, load);
</script>

<template>
  <craft-card :data-color="color">
    <slot name="header" />
    <div class="body">
      <craft-callout v-if="error" variant="danger" role="alert">{{
        error
      }}</craft-callout>
      <craft-spinner v-if="loading" visible role="status">{{
        t('Loading…')
      }}</craft-spinner>
      <AreaChart
        v-if="chart && !loading && !error"
        :data="chart.data"
        :scale="chart.scale"
        :label="t('New Users')"
      />
    </div>
  </craft-card>
</template>
