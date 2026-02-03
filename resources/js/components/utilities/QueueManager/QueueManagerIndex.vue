<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import Pane from '@/components/Pane.vue';
  import {h, inject, onMounted, ref} from 'vue';
  import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
  } from '@tanstack/vue-table';
  import CpLink from '@/components/CpLink.vue';
  import Badge from '@/components/Badge.vue';
  import {Queue} from '@/types/keys';
  import {type JobInfo, JobStatus} from '@craftcms/cp/src/types/index.js';
  import RetryJobButton from '@/components/utilities/QueueManager/RetryJobButton.vue';
  import ReleaseJobButton from '@/components/utilities/QueueManager/ReleaseJobButton.vue';
  import {show} from '@routes/cp/utilities';

  const queue = inject(Queue);
  const props = withDefaults(
    defineProps<{
      initialData: Array<JobInfo>;
      totalJobs?: number;
      hasReservedJobs?: boolean;
      hasWaitingJobs?: boolean;
    }>(),
    {totalJobs: 0}
  );

  const jobs = ref<Array<JobInfo>>(props.initialData ?? []);
  const totalJobs = ref(props.totalJobs);
  const columnHelper = createColumnHelper<JobInfo>();

  function getStatusVariant(value: number) {
    if (value === 2 || value === 3) {
      return 'success';
    }

    if (value === 4) {
      return 'danger';
    }

    if (value === 5) {
      return 'warning';
    }

    return 'default';
  }

  const columns = ref([
    columnHelper.accessor('description', {
      header: () => t('Name'),
      cell: ({row, getValue}) =>
        h(
          CpLink,
          {href: show.url({id: 'queue-manager', extra: row.original.uid})},
          () => getValue()
        ),
    }),
    columnHelper.accessor('status', {
      header: () => t('Status'),
      size: 50,
      cell: (info) =>
        h(
          Badge,
          {
            variant: getStatusVariant(info.getValue().value),
          },
          () => info.getValue().label
        ),
    }),
    columnHelper.display({
      id: 'progress',
      header: () => t('Progress'),
      cell: ({row}) =>
        row.original.progress > 0
          ? `${row.original.progress}% ${row.original.progressLabel ? `(${row.original.progressLabel})` : ''}`
          : '',
    }),
    columnHelper.display({
      id: 'actions',
      cell: ({row}) => {
        return h('div', {class: 'flex justify-end gap-2'}, [
          ...(row.original.status.value === JobStatus.Failed
            ? [
                h(RetryJobButton, {job: row.original}),
                h(ReleaseJobButton, {job: row.original}),
              ]
            : []),
        ]);
      },
    }),
  ]);

  const jobsTable = useVueTable({
    get data() {
      return jobs.value;
    },
    get columns() {
      return columns.value;
    },
    getCoreRowModel: getCoreRowModel<JobInfo>(),
  });

  onMounted(async () => {
    queue?.addEventListener('job-update', ({detail}) => {
      jobs.value = detail.jobInfo;
      totalJobs.value = detail.totalJobs;
    });
    if (props.hasReservedJobs) {
      queue?.startTracking(true);
    } else if (props.hasWaitingJobs) {
      await queue?.runQueue();
    }
  });
</script>

<template>
  <template v-if="jobs.length > 0">
    <Pane :padding="0">
      <AdminTable :table="jobsTable" :reorderable="false" layout="fixed" />
      <template #footer>
        <div
          class="flex p-2 bg-slate-100"
          v-text="
            t('{totalJobs, plural, =0{No jobs} =1{# job} other{# jobs}}', {
              totalJobs,
            })
          "
        ></div>
      </template>
    </Pane>
  </template>
  <template v-else>
    <div class="py-20">
      <div
        class="w-[60ch] mx-auto text-center grid gap-3 justify-items-center text-gray-500"
      >
        <craft-icon
          name="play"
          style="font-size: calc(48rem / 16)"
        ></craft-icon>
        <p>{{ t('There are no jobs in the queue') }}</p>
      </div>
    </div>
  </template>
</template>

<style scoped lang="scss"></style>
