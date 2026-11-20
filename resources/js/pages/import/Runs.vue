<script setup lang="ts">
  /**
   * The import plans' runs, newest first, or one plan's: how each went, when it was
   * started and by whom, how long it took, and what it did with its items.
   */
  import {t} from '@craftcms/ui';
  import CraftSelectRich from '@craftcms/ui/vue/CraftSelectRich.vue';
  import {router} from '@inertiajs/vue3';
  import {useIntervalFn} from '@vueuse/core';
  import {computed, h, watch} from 'vue';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import type {AdminTableStatusOption} from '@/modules/admin-table/types';
  import RunStatusLabel from '@/modules/import/run/RunStatusLabel.vue';
  import {
    outcomeLabels,
    runCounts,
    runDuration,
    runStartedBy,
    statusLabels,
  } from '@/modules/import/run/run-format';
  import type {
    ItemOutcome,
    RunState,
    RunStatus,
  } from '@/modules/import/run/run-state';

  type ListedRun = RunState & {
    plan: {name: string; url: string | null};
  };

  const props = defineProps<{
    runs: ListedRun[];
    /** Each plan's name and handle, for narrowing the list to one. */
    plans: Array<{label: string; value: string}>;
    /** The handle of the plan the list is narrowed to. */
    plan: string | null;
  }>();

  const planOptions = computed(() => [
    {label: t('All plans'), value: ''},
    ...props.plans,
  ]);

  function filterByPlan(handle: string): void {
    router.get(window.location.pathname, handle ? {plan: handle} : {}, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }

  const statusFills: Record<RunStatus, string> = {
    pending: 'neutral',
    running: 'info',
    successful: 'success',
    failed: 'danger',
  };

  const statusFilterOptions: AdminTableStatusOption[] = [
    {value: '', label: t('All statuses')},
    ...(Object.keys(statusFills) as RunStatus[]).map((status) => ({
      value: status,
      label: statusLabels[status](),
      fill: statusFills[status],
    })),
  ];

  const unfinished = computed(() =>
    props.runs.some((run) => ['pending', 'running'].includes(run.status))
  );

  const poll = useIntervalFn(() => router.reload({only: ['runs']}), 2000, {
    immediate: false,
  });
  watch(unfinished, (value) => (value ? poll.resume() : poll.pause()), {
    immediate: true,
  });

  /** The outcomes a run came to, in order, leaving out those no item did. */
  function outcomes(run: RunState): Array<[ItemOutcome, number]> {
    return (
      Object.entries(runCounts(run)) as Array<[ItemOutcome, number]>
    ).filter(([, count]) => count > 0);
  }

  function itemCount(run: RunState): number {
    return Object.values(runCounts(run)).reduce((sum, n) => sum + n, 0);
  }

  /** How long it took, in milliseconds, for sorting; one that hasn't started sorts first. */
  function elapsed(run: RunState): number {
    if (!run.dateStarted) {
      return -1;
    }

    const end = run.dateFinished ? Date.parse(run.dateFinished) : Date.now();

    return end - Date.parse(run.dateStarted);
  }

  const columnHelper = createCraftColumnHelper<ListedRun>();
  const columns = [
    columnHelper.accessor((run) => run.plan.name, {
      id: 'plan',
      header: t('Plan'),
      enableSorting: true,
      meta: {cellTag: 'th'},
      cell: ({row}) =>
        h('a', {href: row.original.url, class: 'font-bold'}, [
          row.original.plan.name,
        ]),
    }),
    columnHelper.accessor((run) => statusLabels[run.status](), {
      id: 'status',
      header: t('Status'),
      enableSorting: true,
      cell: ({row}) =>
        h(RunStatusLabel, {
          status: row.original.status,
          progress: row.original.progress,
        }),
    }),
    columnHelper.accessor((run) => Date.parse(run.dateCreated), {
      id: 'started',
      header: t('Started'),
      enableSorting: true,
      cell: ({row}) =>
        h('span', {title: row.original.started.title}, [
          row.original.started.label,
        ]),
    }),
    columnHelper.accessor((run) => runStartedBy(run), {
      id: 'startedBy',
      header: t('Started by'),
      enableSorting: true,
    }),
    columnHelper.accessor((run) => elapsed(run), {
      id: 'duration',
      header: t('Duration'),
      enableSorting: true,
      cell: ({row}) => runDuration(row.original) ?? '—',
    }),
    columnHelper.accessor((run) => itemCount(run), {
      id: 'items',
      header: t('Items'),
      enableSorting: true,
      cell: ({row}) => {
        const counts = outcomes(row.original);

        return counts.length
          ? counts.map(([outcome, count]) =>
              h(
                'span',
                {
                  class: [
                    'import-runs__outcome',
                    {'import-runs__outcome--failed': outcome === 'failed'},
                  ],
                },
                `${outcomeLabels[outcome]()}: ${count}`
              )
            )
          : '—';
      },
    }),
  ];
</script>

<template>
  <div class="@container">
    <AdminTable
      appearance="flush"
      :rows="runs"
      :columns="columns"
      storage-key="import-runs"
      :status-filter-options="statusFilterOptions"
      :get-row-status="(run: ListedRun) => run.status"
      sortable
      :show-footer="false"
    >
      <template #filters>
        <CraftSelectRich
          :model-value="plan ?? ''"
          :options="planOptions"
          :label="t('Plan')"
          label-sr-only
          @update:model-value="filterByPlan(String($event ?? ''))"
        />
      </template>
      <template #empty-row>
        <craft-empty
          :label="
            plan
              ? t('This import plan hasn’t been run yet.')
              : t('No import plans have been run yet.')
          "
        ></craft-empty>
      </template>
    </AdminTable>
  </div>
</template>

<style scoped>
  :deep(.import-runs__outcome--failed) {
    color: var(--c-color-danger-on-quiet);
  }

  :deep(.import-runs__outcome + .import-runs__outcome)::before {
    content: ' · ';
    color: var(--c-text-quiet);
  }
</style>
