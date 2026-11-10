<script setup lang="ts">
  import {nextTick, onBeforeUnmount, onMounted, ref, watch} from 'vue';
  import {useResizeObserver} from '@vueuse/core';
  import {actionClient, t} from '@craftcms/ui';
  import {data as chartData} from '@actions/Dashboard/Widgets/NewUsersController';
  import {Area, DataTable} from '@/modules/chart/chart';

  const props = defineProps<{
    data: {dateRange: string; userGroupId: number | null};
  }>();

  const container = ref<HTMLElement>();
  const error = ref('');
  const loading = ref(true);
  let chart: Area | undefined;
  let request: AbortController | undefined;

  useResizeObserver(container, () => chart?.resize());

  onBeforeUnmount(() => {
    request?.abort();
    chart?.destroy();
  });

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
      loading.value = false;
      await nextTick();
      if (current.signal.aborted) return;

      chart ??= new Area(container.value!, {
        yAxis: {
          formatter: (chart: any) => (value: number) =>
            chart.formatLocale.format(
              Number.isInteger(value) ? ',.0f' : ',.1f'
            )(value),
        },
      });
      chart.draw(new DataTable(data.dataTable), {
        orientation: data.orientation,
        dataScale: data.scale,
        formats: data.formats,
      });
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
  <craft-card data-color="gray">
    <slot name="header" />
    <div class="body">
      <craft-callout v-if="error" variant="danger" role="alert">{{
        error
      }}</craft-callout>
      <craft-spinner v-if="loading" visible role="status">{{
        t('Loading…')
      }}</craft-spinner>
      <div
        v-show="!loading && !error"
        ref="container"
        class="chart"
        :aria-label="t('New Users')"
      ></div>
    </div>
  </craft-card>
</template>
