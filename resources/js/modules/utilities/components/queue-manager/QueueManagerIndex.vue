<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {h, ref} from 'vue';
  import {useCraftTable} from '@/modules/admin-table/craftTable';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import CpLink from '@/common/components/CpLink.vue';
  import {type JobInfo, JobStatus} from '@/modules/queue/types';
  import RetryJobButton from '@/modules/utilities/components/queue-manager/RetryJobButton.vue';
  import ReleaseJobButton from '@/modules/utilities/components/queue-manager/ReleaseJobButton.vue';
  import {show} from '@routes/cp/utilities';
  import CpContainer from '@/common/components/CpContainer.vue';

  const props = withDefaults(
    defineProps<{
      jobs: Array<JobInfo>;
      totalJobs?: number;
    }>(),
    {totalJobs: 0}
  );

  const columnHelper = createCraftColumnHelper<JobInfo>();

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

  function isRetryable(job: JobInfo) {
    return job.status.value == JobStatus.Failed;
  }

  const columns = ref(
    columnHelper.columns([
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
        size: 80,
        cell: (info) =>
          h(
            'craft-badge',
            {
              fill: getStatusVariant(info.getValue().value),
            },
            info.getValue().label
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
            isRetryable(row.original)
              ? h(RetryJobButton, {job: row.original})
              : null,
            row.original.status.value !== JobStatus.Done
              ? h(ReleaseJobButton, {job: row.original})
              : null,
          ]);
        },
      }),
    ])
  );

  const jobsTable = useCraftTable({
    get data() {
      return props.jobs;
    },
    get columns() {
      return columns.value;
    },
  });
</script>

<template>
  <template v-if="jobs.length > 0">
    <div>
      <AdminTable :table="jobsTable" :reorderable="false" layout="fixed" />
      <div
        class="flex p-2 bg-slate-100"
        v-text="
          t('{totalJobs, plural, =0{No jobs} =1{# job} other{# jobs}}', {
            totalJobs,
          })
        "
      ></div>
    </div>
  </template>
  <template v-else>
    <craft-empty
      icon="play"
      :label="t('There are no jobs in the queue')"
    ></craft-empty>
  </template>
</template>

<style scoped lang="scss"></style>
