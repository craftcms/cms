<script setup lang="ts">
  import {nextTick, onBeforeUnmount, onMounted, ref} from 'vue';
  import {useResizeObserver} from '@vueuse/core';
  import {actionClient, t} from '@craftcms/ui';
  import {data as chartData} from '@actions/Dashboard/Widgets/NewUsersController';
  import {jq} from '@/common/utils/jquery';
  import {Area, DataTable} from '@/modules/chart/chart';

  const props = defineProps<{
    data: {dateRange: string; userGroupId: number | null};
  }>();

  const container = ref<HTMLElement>();
  const error = ref('');
  const loading = ref(true);
  let chart: Area | undefined;
  const abort = new AbortController();

  useResizeObserver(container, () => chart?.resize());

  onBeforeUnmount(() => {
    abort.abort();
    chart?.destroy();
  });

  onMounted(async () => {
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
        {signal: abort.signal}
      );

      loading.value = false;
      await nextTick();
      if (abort.signal.aborted) return;

      chart = new Area(jq()!(container.value!), {
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
      if (!abort.signal.aborted) error.value = t('A server error occurred.');
    } finally {
      loading.value = false;
    }
  });
</script>

<template>
  <craft-pane appearance="raised" padding="lg">
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
  </craft-pane>
</template>
