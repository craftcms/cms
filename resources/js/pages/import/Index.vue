<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {h, ref} from 'vue';
  import Empty from '@/common/components/Empty.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {router} from '@inertiajs/vue3';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';
  import {
    create,
    destroy,
    duplicate,
    run,
  } from '@actions/Import/ImportController';
  import CpContainer from '@/common/components/CpContainer.vue';

  interface ImportRow {
    uid: string | null;
    name: string;
    handle: string;
    description: string | null;
    stepCount: number;
    stepLabels: string[];
    editUrl: string | null;
  }

  const props = defineProps<{
    readOnly: boolean;
    canSave: boolean;
    canDelete: boolean;
    canTrigger: boolean;
    editableImports: Array<ImportRow>;
    nonEditableImports: Array<ImportRow>;
  }>();

  function stepsCell(row: ImportRow) {
    if (row.stepCount === 0) {
      return t('No steps');
    }

    return h(
      'div',
      row.stepLabels.map((label, i) => h('div', `${i + 1}. ${label}`))
    );
  }

  function runAction(body: Record<string, string | null>): ActionItems[number] {
    return {
      type: 'button',
      label: t('Run'),
      onClick: () => router.post(run().url, body),
    };
  }

  const columnHelper = createCraftColumnHelper<ImportRow>();
  const editableColumns = ref([
    columnHelper.link('name', {
      header: t('Name'),
      props: ({row}) => ({
        href: row.original.editUrl ?? '',
        inertia: false,
      }),
    }),
    columnHelper.handle('handle'),
    columnHelper.accessor('stepCount', {
      header: t('Steps'),
      cell: ({row}) => stepsCell(row.original),
    }),
    columnHelper.actions(({row}) => {
      const actions: ActionItems = [
        {
          type: 'link',
          label: t('Edit'),
          href: row.original.editUrl ?? '',
        },
      ];

      if (props.canTrigger) {
        actions.push(runAction({uid: row.original.uid}));
      }

      if (props.canSave) {
        actions.push({
          type: 'button',
          label: t('Duplicate'),
          onClick: () => router.post(duplicate().url, {uid: row.original.uid}),
        });
      }

      if (props.canDelete) {
        actions.push({
          type: 'button',
          label: t('Delete'),
          variant: 'danger',
          onClick: () => {
            if (
              !window.confirm(
                t('Are you sure you want to delete “{name}”?', {
                  name: row.original.name,
                })
              )
            ) {
              return;
            }
            router.delete(destroy().url, {
              data: {uid: row.original.uid},
            });
          },
        });
      }

      return [h(ActionMenu, {actions})];
    }),
  ]);

  const editableTable = useVueTable<ImportRow>({
    get data() {
      return props.editableImports;
    },
    get columns() {
      return editableColumns.value;
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel<ImportRow>(),
  });

  const nonEditableColumns = ref([
    columnHelper.accessor('name', {
      header: t('Name'),
      cell: ({row, getValue}) =>
        h('div', [h('div', {class: 'font-bold'}, getValue())]),
    }),
    columnHelper.handle('handle'),
    columnHelper.accessor('stepCount', {
      header: t('Steps'),
      cell: ({row}) => stepsCell(row.original),
    }),
    columnHelper.actions(({row}) => {
      if (!props.canTrigger) {
        return [];
      }

      return [
        h(ActionMenu, {
          actions: [runAction({handle: row.original.handle})],
        }),
      ];
    }),
  ]);

  const nonEditableTable = useVueTable<ImportRow>({
    get data() {
      return props.nonEditableImports;
    },
    get columns() {
      return nonEditableColumns.value;
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel<ImportRow>(),
  });
</script>

<template>
  <LayoutSlot v-if="canSave" name="content-actions">
    <CpLink
      variant="accent"
      appearance="button"
      :href="create().url"
      :inertia="false"
    >
      {{ t('New import') }}
    </CpLink>
  </LayoutSlot>

  <CpContainer class="@container">
    <div class="grid gap-6">
      <craft-pane>
        <div>
          <h3>{{ t('Editable Imports') }}</h3>
          <p>
            {{ t('Those imports can be edited in the Control Panel') }}
          </p>
        </div>

        <AdminTable :table="editableTable" :reorderable="false">
          <template #empty-row>
            <Empty :label="t('No imports yet.')" icon="light/upload" />
          </template>
        </AdminTable>
      </craft-pane>

      <craft-pane v-if="nonEditableImports.length">
        <div>
          <h3>{{ t('Non-Editable Imports') }}</h3>
          <p>
            {{ t('Those imports can be edited in the config file.') }}
          </p>
        </div>
        <AdminTable :table="nonEditableTable" :reorderable="false"></AdminTable>
      </craft-pane>
    </div>
  </CpContainer>
</template>
