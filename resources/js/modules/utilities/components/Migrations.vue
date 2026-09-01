<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import migrationsController from '@actions/Utilities/MigrationsController';
  import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
  } from '@tanstack/vue-table';
  import {computed, ref} from 'vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import Empty from '@/common/components/Empty.vue';
  import {Form} from '@inertiajs/vue3';
  interface Migration {
    id?: number;
    name: string;
    track?: null | string;
    migration?: string;
    batch?: number;
    status?: string;
  }

  const props = defineProps<{
    newMigrations: Array<string>;
    migrationHistory: Array<Migration>;
  }>();

  const allMigrations = computed<Migration[]>(() => {
    return [
      ...props.newMigrations.map((item) => ({
        name: item,
        status: t('New'),
        batch: undefined,
      })),
      ...props.migrationHistory.map((item) => ({
        name: item.migration ?? item.name,
        status: t('Applied'),
        batch: item.batch,
      })),
    ] satisfies Migration[];
  });

  const columnHelper = createColumnHelper<Migration>();
  const columns = ref([
    columnHelper.accessor('name', {
      header: t('Name'),
      cell: (info) => info.getValue(),
    }),
    columnHelper.accessor('status', {
      header: t('Status'),
      cell: (info) => info.getValue(),
    }),
    columnHelper.accessor('batch', {
      header: t('Batch'),
      cell: (info) => info.getValue(),
    }),
  ]);

  const migrationsTable = useVueTable({
    get columns() {
      return columns.value;
    },

    get data() {
      return allMigrations.value;
    },
    getCoreRowModel: getCoreRowModel<Migration>(),
  });
</script>

<template>
  <template v-if="!newMigrations">
    <Empty :label="t('No pending content migrations.')" />
  </template>
  <template v-if="allMigrations.length">
    <template v-if="newMigrations.length">
      <Form :action="migrationsController()" method="post">
        <craft-button type="submit" variant="accent">
          {{ t('Apply new migrations') }}
        </craft-button>
      </Form>
    </template>

    <AdminTable :table="migrationsTable" :reorderable="false" />
  </template>
</template>

<style scoped lang="scss"></style>
