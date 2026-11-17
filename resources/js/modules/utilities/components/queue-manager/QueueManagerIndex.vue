<script setup lang="ts">
  import {actionClient} from '@craftcms/ui';
  import {t} from '@craftcms/ui/utilities/translate';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import type {
    AdminTablePage,
    AdminTableRequest,
  } from '@/modules/admin-table/types';
  import {h, useTemplateRef} from 'vue';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import CpLink from '@/common/components/CpLink.vue';
  import type {PaginationData} from '@/common/types';
  import {type JobInfo, JobStatus} from '@/modules/queue/types';
  import {jobStatusFill} from '@/modules/queue/status-fill';
  import RetryJobButton from '@/modules/utilities/components/queue-manager/RetryJobButton.vue';
  import ReleaseJobButton from '@/modules/utilities/components/queue-manager/ReleaseJobButton.vue';
  import {show} from '@routes/cp/utilities';
  import {jobs} from '@actions/QueueController';

  const table = useTemplateRef('table');

  /** Reloads the current page of jobs, quietly when it's a live update. */
  function refresh(options?: {quiet?: boolean}): Promise<boolean> {
    return table.value?.refresh(options) ?? Promise.resolve(true);
  }

  defineExpose({refresh});

  async function loadRows(
    request: AdminTableRequest,
    signal: AbortSignal
  ): Promise<AdminTablePage<JobInfo>> {
    const {data} = await actionClient.post<{
      data: JobInfo[];
      pagination: PaginationData;
    }>(jobs.url(), {page: request.page, per_page: request.perPage}, {signal});
    return {rows: data.data, pagination: data.pagination};
  }

  const columnHelper = createCraftColumnHelper<JobInfo>();

  function isRetryable(job: JobInfo) {
    return job.status.value == JobStatus.Failed;
  }

  const columns = columnHelper.columns([
    columnHelper.accessor('description', {
      header: () => t('Name'),
      enableSorting: false,
      cell: ({row, getValue}) =>
        h(
          CpLink,
          {href: show.url({id: 'queue-manager', extra: row.original.uid})},
          () => getValue()
        ),
    }),
    columnHelper.accessor('status', {
      header: () => t('Status'),
      enableSorting: false,
      size: 80,
      cell: (info) =>
        h(
          'craft-badge',
          {
            fill: jobStatusFill(info.getValue().value),
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
            ? h(RetryJobButton, {job: row.original, onDone: () => refresh()})
            : null,
          row.original.status.value !== JobStatus.Done
            ? h(ReleaseJobButton, {job: row.original, onDone: () => refresh()})
            : null,
        ]);
      },
    }),
  ]);
</script>

<template>
  <AdminTable
    ref="table"
    :columns="columns"
    :load-rows="loadRows"
    storage-key="adminTable.queueManager"
    layout="fixed"
    enable-adjust-page-size
  >
    <template #empty-row>
      <craft-empty
        icon="play"
        :label="t('There are no jobs in the queue')"
      ></craft-empty>
    </template>
  </AdminTable>
</template>

<style scoped lang="scss"></style>
