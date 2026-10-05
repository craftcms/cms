<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {useCraftTable} from '@/modules/admin-table/craftTable';
  import {computed, h, ref, watch} from 'vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import {router} from '@inertiajs/vue3';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';
  import {
    create,
    destroy,
    duplicate,
    reorder,
    run,
  } from '@actions/Import/ImportPlansController';
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
    canSave: boolean;
    canDelete: boolean;
    canTrigger: boolean;
    editableImportPlans: Array<ImportRow>;
    nonEditableImportPlans: Array<ImportRow>;
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
  const editableColumns = ref(
    columnHelper.columns([
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
            onClick: () =>
              router.post(duplicate().url, {uid: row.original.uid}),
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
    ])
  );

  const planUids = ref<string[]>([]);

  watch(
    () => props.editableImportPlans,
    (plans) => {
      planUids.value = plans.map((plan) => plan.uid!);
    },
    {immediate: true}
  );

  /** The editable import plans, in the order the user has put them in. */
  const editableRows = computed(() =>
    planUids.value
      .map((uid) => props.editableImportPlans.find((plan) => plan.uid === uid))
      .filter((plan): plan is ImportRow => plan !== undefined)
  );

  function handleReorder(startIndex: number, finishIndex: number): void {
    const previousUids = planUids.value;
    const nextUids = [...previousUids];
    const [uid] = nextUids.splice(startIndex, 1);

    if (uid === undefined) {
      return;
    }

    nextUids.splice(finishIndex, 0, uid);
    planUids.value = nextUids;

    router.post(
      reorder(),
      {uids: nextUids},
      {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
          planUids.value = previousUids;
        },
      }
    );
  }

  const editableTable = useCraftTable<ImportRow>({
    get data() {
      return editableRows.value;
    },
    get columns() {
      return editableColumns.value;
    },
    getRowId: (row) => row.uid!,
  });

  const nonEditableColumns = ref(
    columnHelper.columns([
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
    ])
  );

  const nonEditableTable = useCraftTable<ImportRow>({
    get data() {
      return props.nonEditableImportPlans;
    },
    get columns() {
      return nonEditableColumns.value;
    },
  });
</script>

<template>
  <LayoutSlot v-if="canSave" name="content-actions">
    <CpButtonLink
      variant="primary"
      icon="plus"
      :href="create().url"
      :inertia="false"
    >
      {{ t('New import plan') }}
    </CpButtonLink>
  </LayoutSlot>

  <CpContainer class="@container">
    <div class="grid gap-6">
      <craft-pane>
        <div>
          <h3>{{ t('Editable Import Plans') }}</h3>
          <p>
            {{ t('Those import plans can be edited in the control panel.') }}
          </p>
        </div>

        <AdminTable
          :table="editableTable"
          :reorderable="canSave"
          @reorder="handleReorder"
        >
          <template #empty-row>
            <craft-empty
              :label="t('No import plans yet.')"
              icon="light/upload"
            />
          </template>
        </AdminTable>
      </craft-pane>

      <craft-pane v-if="nonEditableImportPlans.length">
        <div>
          <h3>{{ t('Non-Editable Import Plans') }}</h3>
          <p>
            {{ t('Those import plans can be edited in the config file.') }}
          </p>
        </div>
        <AdminTable :table="nonEditableTable" :reorderable="false"></AdminTable>
      </craft-pane>
    </div>
  </CpContainer>
</template>
