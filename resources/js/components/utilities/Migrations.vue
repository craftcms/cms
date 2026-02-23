<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts';
  import migrationsController from '@actions/Utilities/MigrationsController';
  import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
  } from '@tanstack/vue-table';
  import {computed, ref} from 'vue';
  import AdminTable from '@/components/common/AdminTable/AdminTable.vue';
  import Empty from '@/components/common/Empty/Empty.vue';
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
    newMigrations: Array<Migration>;
    migrationHistory: Array<Migration>;
  }>();

  const allMigrations = computed(() => {
    return [
      ...props.newMigrations.map((item) => ({
        name: item,
        status: t('New'),
        batch: '',
      })),
      ...props.migrationHistory.map((item) => ({
        name: item.migration,
        status: t('Applied'),
        batch: item.batch,
      })),
    ];
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
        <craft-button type="submit" variant="primary">
          {{ t('Apply new migrations') }}
        </craft-button>
      </Form>
    </template>

    <AdminTable :table="migrationsTable" :reorderable="false" />
  </template>

  <!--  <table class="data fullwidth">-->
  <!--    <thead>-->
  <!--      <tr>-->
  <!--        <th>{{ 'Name' | t('app') }}</th>-->
  <!--        <th>{{ 'Status' | t('app') }}</th>-->
  <!--        <th>{{ 'Batch' | t('app') }}</th>-->
  <!--      </tr>-->
  <!--    </thead>-->
  <!--    <tbody>-->
  <!--      {% for newMigration in newMigrations %}-->
  <!--      <tr>-->
  <!--        <td>{{ newMigration }}</td>-->
  <!--        <td>{{ 'New' | t('app') }}</td>-->
  <!--        <td></td>-->
  <!--      </tr>-->
  <!--      {% endfor %} {% for migration in migrationHistory %}-->
  <!--      <tr>-->
  <!--        <td>{{ migration.migration }}</td>-->
  <!--        <td>{{ 'Applied' | t('app') }}</td>-->
  <!--        <td>{{ migration.batch }}</td>-->
  <!--      </tr>-->
  <!--      {% endfor %}-->
  <!--    </tbody>-->
  <!--  </table>-->
  <!--  {% endif %}-->
</template>

<style scoped lang="scss"></style>
