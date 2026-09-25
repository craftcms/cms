<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import {useTable} from '@tanstack/vue-table';
  import {craftTableFeatures} from '@/modules/admin-table/tableFeatures';
  import {h, ref} from 'vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteLogButton from '@/modules/utilities/components/deprecation-errors/DeleteLogButton.vue';
  import StackTraceButton from '@/modules/utilities/components/deprecation-errors/StackTraceButton.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import CpContainer from '@/common/components/CpContainer.vue';

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
  const columns = ref(
    columnHelper.columns([
      columnHelper.accessor('message', {
        header: t('Message'),
        cell: (info) => h('div', {innerHTML: info.getValue()}),
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
      columnHelper.date('lastOccurrence'),
      columnHelper.display({
        id: 'stackTrace',
        header: t('Stack Trace'),
        cell: ({row}) => h(StackTraceButton, {logId: row.original.id}),
      }),
      columnHelper.actions(({row}) => [
        h(DeleteLogButton, {logId: row.original.id}),
      ]),
    ])
  );

  const table = useTable({
    features: craftTableFeatures,
    get columns() {
      return columns.value;
    },
    get data() {
      return props.logs;
    },
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
      <craft-empty
        :label="t('No deprecation warnings to report!')"
      ></craft-empty>
    </template>
  </AdminTable>
</template>

<style scoped lang="scss"></style>
