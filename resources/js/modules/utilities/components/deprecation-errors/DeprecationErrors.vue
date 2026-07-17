<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {h, ref} from 'vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteLogButton from '@/modules/utilities/components/deprecation-errors/DeleteLogButton.vue';
  import StackTraceButton from '@/modules/utilities/components/deprecation-errors/StackTraceButton.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import Empty from '@/common/components/Empty.vue';

  export interface LogData {
    id: number;
    origin: string;
    message: string;
    lastOccurrence: string;
  }

  const props = defineProps<{
    logs: Array<LogData>;
  }>();

  const columnHelper = createCraftColumnHelper<LogData>();
  const columns = ref([
    columnHelper.accessor('message', {
      header: t('Message'),
      cell: (info) => h('span', {innerHTML: info.getValue()}),
      meta: {
        trackSize: '3fr',
        wrap: true,
      },
    }),
    columnHelper.accessor('origin', {
      header: t('Origin'),
      cell: (info) => h('code', {innerHTML: info.getValue()}),
      meta: {
        trackSize: '2fr',
        wrap: true,
      },
    }),
    columnHelper.date('lastOccurrence'),
    columnHelper.display({
      id: 'stackTrace',
      header: t('Stack Trace'),
      meta: {
        trackSize: '120px',
      },
      cell: ({row}) => h(StackTraceButton, {logId: row.original.id}),
    }),
    columnHelper.actions(({row}) => [
      h(DeleteLogButton, {logId: row.original.id}),
    ]),
  ]);

  const table = useVueTable({
    get columns() {
      return columns.value;
    },
    get data() {
      return props.logs;
    },
    getCoreRowModel: getCoreRowModel<LogData>(),
    enableSorting: false,
  });
</script>

<template>
  <AdminTable
    layout="auto"
    :table="table"
    :from="1"
    :to="logs.length"
    :total="logs.length"
    :reorderable="false"
  >
    <template #empty-row>
      <Empty :label="t('No deprecation warnings to report!')" />
    </template>
  </AdminTable>
</template>

<style scoped lang="scss"></style>
