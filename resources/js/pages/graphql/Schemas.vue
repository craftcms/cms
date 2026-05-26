<script setup lang="ts">
  import {h} from 'vue';
  import {t} from '@craftcms/cp';
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import Pane from '@/common/components/Pane.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {
    create,
    destroy,
    edit,
    editPublic,
  } from '@actions/Gql/SchemasController';
  import CpLink from '@/common/components/CpLink.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';

  interface SchemaData {
    id: number;
    scope: Array<any>;
    isPublic: boolean;
    uid: string;
    name: string;
  }

  const props = defineProps<{
    schemas: Array<SchemaData>;
    readOnly: boolean;
  }>();

  function deleteSchema(schema: SchemaData) {
    if (
      confirm(
        t('Are you sure you want to delete the “{name}” schema?', {
          name: schema.name,
        })
      )
    ) {
      router.delete(destroy(schema.id));
    }
  }

  const columnHelper = createCraftColumnHelper<SchemaData>();
  const table = useVueTable({
    get columns() {
      return [
        columnHelper.link('name', {
          props: ({row}) => ({
            href: row.original.isPublic
              ? editPublic()
              : edit(row.original.id).url,
            inertia: false,
          }),
          header: t('Name'),
        }),
        columnHelper.display({
          id: 'scope',
          header: t('Scope'),
          cell: ({row}) => row.original.scope.join(', '),
        }),
        columnHelper.display({
          id: 'public',
          header: t('Public'),
          cell: ({row}) => (row.original.isPublic ? 'Yes' : 'No'),
        }),
        columnHelper.actions(({row}) => [
          row.original.isPublic
            ? null
            : h(DeleteButton, {onClick: () => deleteSchema(row.original)}),
        ]),
      ];
    },
    get data() {
      return props.schemas;
    },
    state: {
      get columnVisibility() {
        return {
          name: true,
          public: true,
          actions: !props.readOnly,
        };
      },
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel<SchemaData>(),
  });
</script>

<template>
  <AppLayout>
    <template #actions>
      <CpLink
        :href="create.url()"
        icon="plus"
        :inertia="false"
        appearance="button"
        variant="primary"
      >{{ t('New schema') }}</CpLink
      >
    </template>
    <Pane :padding="0" appearance="raised">
      <AdminTable :table="table" />
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
