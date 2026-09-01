<script setup lang="ts">
  import {h} from 'vue';
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {create, destroy, edit} from '@actions/Gql/SchemasController';
  import CpLink from '@/common/components/CpLink.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

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

  const columnHelper = createCraftColumnHelper<SchemaData>();
  const table = useVueTable({
    get columns() {
      return [
        columnHelper.link('name', {
          props: ({row}) => ({
            href: row.original.isPublic
              ? edit({schemaId: 'public'}).url
              : edit({schemaId: row.original.id}).url,
          }),
          header: t('Name'),
        }),
        columnHelper.display({
          id: 'scope',
          header: t('Scope'),
          cell: ({row}) =>
            row.original.scope.length > 2
              ? `${row.original.scope.slice(0, 2).join(', ')} ${t('and {count} more', {count: row.original.scope.length - 2})}`
              : row.original.scope.join(', '),
        }),
        columnHelper.display({
          id: 'public',
          header: t('Public'),
          cell: ({row}) => (row.original.isPublic ? 'Yes' : 'No'),
        }),
        columnHelper.actions(({row}) => [
          row.original.isPublic
            ? null
            : h(DeleteButton, {
                confirm: t(
                  'Are you sure you want to delete the “{name}” schema?',
                  {name: row.original.name}
                ),
                onClick: () =>
                  router
                    .optimistic<{schemas: Array<SchemaData>}>(({schemas}) => ({
                      schemas: schemas.filter(({id}) => id !== row.original.id),
                    }))
                    .delete(destroy({schemaId: row.original.id})),
              }),
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
  <LayoutSlot name="actions">
    <CpLink
      :href="create.url()"
      icon="plus"
      appearance="button"
      variant="accent"
      >{{ t('New schema') }}</CpLink
    >
  </LayoutSlot>
  <craft-pane padding="0" appearance="raised">
    <AdminTable :table="table" />
  </craft-pane>
</template>
