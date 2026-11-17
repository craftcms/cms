<script setup lang="ts">
  import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    watch,
  } from 'vue';
  import {actionClient, t} from '@craftcms/ui';
  import {data as chartData} from '@actions/Dashboard/Widgets/NewUsersController';
  import type {ChartData, ChartScale} from '@/modules/chart/types';
  import SparklineStat from './SparklineStat.vue';
  import {useWidgetColor} from './useWidgetColor';
  import {useWidgetHeader} from './useWidgetHeader';

  const color = useWidgetColor();

  const props = defineProps<{
    data: {
      dateRange: string;
      userGroupId: number | null;
      /** Names the date range, such as “Last 7 days”. */
      dateRangeLabel?: string | null;
      /** The user group's name, lowercased, if the widget shows one. */
      userGroupName?: string | null;
      /** The user group's name for a single user, such as “member”. */
      userGroupNameSingular?: string | null;
    };
  }>();

  const error = ref('');
  const loading = ref(true);
  const chart = shallowRef<{data: ChartData; scale: ChartScale}>();
  let request: AbortController | undefined;

  /** Whether the chart is shown, with the total as the widget's title. */
  const showsChart = computed(
    () => !!chart.value && !loading.value && !error.value
  );

  // The total is the widget's title while it's shown. Until then, or if the
  // chart can't load, the dashboard shows the widget's own.
  const {titleId} = useWidgetHeader({ownTitle: () => showsChart.value});

  /** The new users across the whole range. */
  const total = computed(() =>
    (chart.value?.data.rows ?? []).reduce(
      (count, row) => count + (Number(row[1]) || 0),
      0
    )
  );

  /**
   * The user group's name for a count: singular for one, and as it is
   * otherwise, since group names are usually plural.
   */
  function groupName(count: number): string | null | undefined {
    return count === 1
      ? (props.data.userGroupNameSingular ?? props.data.userGroupName)
      : props.data.userGroupName;
  }

  /** The total as a phrase, such as “264 new members”. */
  const totalLabel = computed(() =>
    props.data.userGroupName
      ? t('{num, number} new {group}', {
          num: total.value,
          group: groupName(total.value),
        })
      : t('{num, number} new {num, plural, =1{user} other{users}}', {
          num: total.value,
        })
  );

  /** A day's tooltip, such as “11/4 · 5 members”. */
  function tooltip(date: string, count: number): string {
    const users = props.data.userGroupName
      ? t('{num, number} {group}', {num: count, group: groupName(count)})
      : t('{num, number} {num, plural, =1{user} other{users}}', {num: count});

    return `${date} · ${users}`;
  }

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
      <SparklineStat
        v-if="showsChart && chart"
        :title="totalLabel"
        :heading-id="titleId"
        :caption="data.dateRangeLabel"
        :data="chart.data"
        :scale="chart.scale"
        :label="t('New Users')"
        :tooltip="tooltip"
      />
    </div>
  </craft-card>
</template>
