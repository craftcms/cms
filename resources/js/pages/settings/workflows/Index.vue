<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {Link, router} from '@inertiajs/vue3';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {computed, h} from 'vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import Empty from '@/common/components/Empty.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {create, destroy, edit} from '@actions/Settings/WorkflowsController';

  interface Workflow {
    id: number;
    name: string;
    stages: number;
  }

  const props = defineProps<{
    workflows: Workflow[];
    readOnly: boolean;
  }>();
  const columnHelper = createCraftColumnHelper<Workflow>();
  const columns = computed(() => [
    columnHelper.link('name', {
      header: t('Name'),
      props: ({row}) => ({href: edit({workflow: row.original.id}).url}),
    }),
    columnHelper.accessor('stages', {header: t('Stages')}),
    columnHelper.actions(({row}) => [
      h(DeleteButton, {
        confirm: t('Are you sure you want to delete “{name}”?', {
          name: row.original.name,
        }),
        onClick: () => router.delete(destroy({workflow: row.original.id})),
      }),
    ]),
  ]);
  const table = useVueTable<Workflow>({
    get data() {
      return props.workflows;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get columnVisibility() {
        return {actions: !props.readOnly};
      },
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel<Workflow>(),
  });
</script>

<template>
  <LayoutSlot v-if="!readOnly" name="actions">
    <Link as="craft-button" :href="create().url" variant="primary" icon="plus">
      {{ t('New workflow') }}
    </Link>
  </LayoutSlot>

  <craft-pane padding="0" appearance="raised">
    <AdminTable :table="table" :reorderable="false">
      <template #empty-row>
        <Empty
          icon="light/clipboard-list-check"
          :label="t('No approval workflows exist yet.')"
        />
      </template>
    </AdminTable>
  </craft-pane>
</template>
