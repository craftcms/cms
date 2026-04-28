<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
  } from '@tanstack/vue-table';
  import {h, ref} from 'vue';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import DeleteLogButton from '@/components/utilities/DeprecationErrors/DeleteLogButton.vue';
  import StackTraceButton from '@/components/utilities/DeprecationErrors/StackTraceButton.vue';

  interface LogData {
    id: number;
    origin: string;
    message: string;
    lastOccurrence: string;
  }

  const props = defineProps<{
    logs: Array<LogData>;
  }>();

  const columnHelper = createColumnHelper<LogData>();
  const columns = ref([
    columnHelper.accessor('message', {
      header: t('Message'),
      cell: (info) => h('span', {innerHTML: info.getValue()}),
      meta: {
        wrap: true,
      },
    }),
    columnHelper.accessor('origin', {
      header: t('Origin'),
      cell: (info) => h('code', {innerHTML: info.getValue()}),
      meta: {
        wrap: true,
      },
    }),
    columnHelper.accessor('lastOccurrence', {
      header: t('Last Occurrence'),
      cell: (info) =>
        new Date(info.getValue()).toLocaleString('en-US', {
          month: 'short',
          day: 'numeric',
          year: 'numeric',
          hour: 'numeric',
          minute: '2-digit',
          second: '2-digit',
          timeZoneName: 'short',
        }),
    }),
    columnHelper.display({
      id: 'stackTrace',
      header: t('Stack Trace'),
      cell: ({row}) => h(StackTraceButton, {logId: row.original.id}),
    }),
    columnHelper.display({
      id: 'actions',
      cell: ({row}) =>
        h('div', {class: 'flex justify-end'}, [
          h(DeleteLogButton, {logId: row.original.id}),
        ]),
    }),
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
  <template v-if="!logs.length">
    <p>{{ t('No deprecation warnings to report!') }}</p>
  </template>
  <template v-else>
    <AdminTable
      spacing="relaxed"
      layout="auto"
      :table="table"
      :from="1"
      :to="logs.length"
      :total="logs.length"
      :reorderable="false"
    ></AdminTable>
  </template>
</template>

<style scoped lang="scss"></style>
