<script setup lang="ts">
  import '@craftcms/ui/components/badge/badge';
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {useCraftTable} from '@/common/table/craftTable';
  import {computed, h, onBeforeUnmount, ref, watch} from 'vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import {router} from '@inertiajs/vue3';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';
  import type {BulkAction} from '@/modules/elements/types/actions';
  import {
    bulkDestroy,
    bulkDuplicate,
    bulkRun,
    create,
    destroy,
    duplicate,
    edit,
    run,
  } from '@actions/Import/ImportPlansController';
  import CpContainer from '@/common/components/CpContainer.vue';
  import ImportPlanRunCell from '@/modules/import/ImportPlanRunCell.vue';
  import {openSlideout} from '@/common/slideouts';

  type ImportPlan = CraftCms.Cms.Import.Data.ImportPlanIndexData;

  /**
   * A plan, or one of its steps under it. A step's row is its plan's with the step added,
   * since a table's sub-rows share their parent's shape.
   */
  type ImportRow = ImportPlan & {
    id: string | null;
    step?: ImportPlan['steps'][number] & {number: number};
    subRows?: ImportRow[];
  };

  const props = defineProps<{
    canSave: boolean;
    canDelete: boolean;
    canTrigger: boolean;
    editableImportPlans: Array<ImportPlan>;
    nonEditableImportPlans: Array<ImportPlan>;
  }>();

  function editUrl(row: ImportRow): string {
    return row.editable ? edit({handle: row.handle}).url : '';
  }

  /** A plan's row, with a row for each of its steps under it. */
  function planRow(plan: ImportPlan): ImportRow {
    return {
      ...plan,
      // the bulk actions bar counts its selection by each row's `id`
      id: plan.uid,
      subRows: plan.steps.map((step, index) => ({
        ...plan,
        id: `${plan.uid ?? plan.handle}:step-${index}`,
        step: {...step, number: index + 1},
      })),
    };
  }

  /**
   * Opens a step's page in a slideout, which saves it into its plan. A modified click opens
   * the page in a new tab instead.
   */
  function onStepLink(url: string, event: MouseEvent): void {
    if (
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey
    ) {
      return;
    }

    event.preventDefault();
    void openSlideout(url, {opener: event.currentTarget as HTMLElement});
  }

  /** A step's row: its number and importer, then where its data comes from. */
  function stepCell(step: NonNullable<ImportRow['step']>) {
    const title = `${step.number}. ${step.label}`;

    return h('div', {class: 'import-step-row'}, [
      h('span', {class: 'import-step-row__label'}, [
        step.url
          ? h(
              'a',
              {
                href: step.url,
                class: 'import-step-row__title',
                onClick: (event: MouseEvent) => onStepLink(step.url!, event),
              },
              title
            )
          : title,
        step.enabled
          ? null
          : h('craft-badge', {fill: 'gray', size: 'small'}, t('Disabled')),
      ]),
      step.source
        ? h('code', {class: 'import-step-row__source'}, step.source)
        : null,
    ]);
  }

  type Column = ReturnType<typeof columnHelper.display>;
  type CellContext = Parameters<Extract<Column['cell'], Function>>[0];

  /** A column's cell as it renders a plan, falling back to its value as TanStack does. */
  function planCell(column: Column, context: CellContext) {
    const cell = column.cell;

    if (typeof cell === 'function') {
      return cell(context);
    }

    return cell ?? context.getValue();
  }

  /** Leaves a plan's column empty on its steps' rows. */
  function planOnly(column: Column): Column {
    return {
      ...column,
      cell: (context) =>
        context.row.original.step ? null : planCell(column, context),
    };
  }

  /** Names a plan, or one of its steps on the step's row. */
  function nameColumn(column: Column): Column {
    return {
      ...column,
      cell: (context) =>
        context.row.original.step
          ? stepCell(context.row.original.step)
          : planCell(column, context),
    };
  }

  /** Sets a step's row apart, as one that can't be selected or acted on by itself. */
  function rowAttributes(row: {original: ImportRow}) {
    return row.original.step ? {class: 'import-plans__step'} : {};
  }

  const stepCountColumn = () =>
    columnHelper.accessor('stepCount', {
      header: t('Steps'),
      meta: {columnClass: 'thin'},
    });

  function runPlan(body: Record<string, string | null>, row: ImportRow): void {
    if (
      !window.confirm(
        t('Are you sure you want to run “{name}”?', {
          name: row.name,
        })
      )
    ) {
      return;
    }

    router.post(run().url, body, {preserveScroll: true});
  }

  const columnHelper = createCraftColumnHelper<ImportRow>();

  /** Runs the plan, or follows its run while it's going. */
  function runColumn(body: (row: ImportRow) => Record<string, string | null>) {
    return columnHelper.display({
      id: 'run',
      header: t('Run'),
      meta: {headerSrOnly: true, columnClass: 'thin'},
      cell: ({row}) =>
        h(ImportPlanRunCell, {
          plan: row.original,
          canRun: props.canTrigger,
          onRun: () => runPlan(body(row.original), row.original),
        }),
    });
  }
  const editableColumns = ref(
    columnHelper.columns([
      nameColumn(
        columnHelper.link('name', {
          header: t('Name'),
          props: ({row}) => ({
            href: editUrl(row.original),
          }),
        }) as Column
      ),
      planOnly(columnHelper.handle('handle') as Column),
      planOnly(stepCountColumn() as Column),
      planOnly(runColumn((row) => ({uid: row.uid}))),
      planOnly(
        columnHelper.actions(
          ({row}) => {
            const actions: ActionItems = [
              {
                type: 'link',
                label: t('Edit'),
                href: editUrl(row.original),
              },
            ];

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
          },
          {meta: {columnClass: 'thin'}}
        ) as Column
      ),
    ])
  );

  const editableRows = computed<ImportRow[]>(() =>
    props.editableImportPlans.map(planRow)
  );

  const editableTable = useCraftTable<ImportRow>({
    get data() {
      return editableRows.value;
    },
    get columns() {
      return editableColumns.value;
    },
    getRowId: (row) => row.id!,
    getSubRows: (row) => row.subRows,
    // a plan's steps are what it does, so they're shown until they're collapsed
    initialState: {expanded: true},
    enableRowSelection: (row) => !row.original.step,
  });

  const selectedUids = computed(() =>
    editableTable.getSelectedRowModel().rows.map((row) => row.id)
  );

  /** Runs a bulk action on the selected plans, once any confirmation's given. */
  function onSelected(url: string, confirm: string | null): void {
    const uids = selectedUids.value;

    if (!uids.length || (confirm && !window.confirm(confirm))) {
      return;
    }

    router.post(
      url,
      {uids},
      {
        preserveScroll: true,
        onSuccess: () => editableTable.resetRowSelection(),
      }
    );
  }

  const bulkActions = computed<BulkAction[]>(() => {
    const count = selectedUids.value.length;
    const actions: BulkAction[] = [];

    if (props.canTrigger) {
      actions.push({
        key: 'run',
        label: t('Run'),
        onClick: () =>
          onSelected(
            bulkRun().url,
            t(
              '{count, plural, =1{Are you sure you want to run the selected import plan?} other{Are you sure you want to run the # selected import plans?}}',
              {count}
            )
          ),
      });
    }

    if (props.canSave) {
      actions.push({
        key: 'duplicate',
        label: t('Duplicate'),
        onClick: () => onSelected(bulkDuplicate().url, null),
      });
    }

    if (props.canDelete) {
      actions.push({
        key: 'delete',
        label: t('Delete'),
        variant: 'danger',
        onClick: () =>
          onSelected(
            bulkDestroy().url,
            t(
              '{count, plural, =1{Are you sure you want to delete the selected import plan?} other{Are you sure you want to delete the # selected import plans?}}',
              {count}
            )
          ),
      });
    }

    return actions;
  });

  const nonEditableColumns = ref(
    columnHelper.columns([
      nameColumn(
        columnHelper.accessor('name', {
          header: t('Name'),
          cell: ({getValue}) =>
            h('div', [h('div', {class: 'font-bold'}, getValue())]),
        }) as Column
      ),
      planOnly(columnHelper.handle('handle') as Column),
      planOnly(stepCountColumn() as Column),
      planOnly(runColumn((row) => ({handle: row.handle}))),
    ])
  );

  const nonEditableRows = computed<ImportRow[]>(() =>
    props.nonEditableImportPlans.map(planRow)
  );

  const nonEditableTable = useCraftTable<ImportRow>({
    get data() {
      return nonEditableRows.value;
    },
    get columns() {
      return nonEditableColumns.value;
    },
    getRowId: (row) => row.id!,
    getSubRows: (row) => row.subRows,
    initialState: {expanded: true},
  });

  // a running plan's row follows it, until the run's finished
  const anyRunning = computed(() =>
    [...props.editableImportPlans, ...props.nonEditableImportPlans].some(
      (plan) => plan.running
    )
  );
  let poll: ReturnType<typeof setInterval> | undefined;

  watch(
    anyRunning,
    (running) => {
      clearInterval(poll);
      poll = running
        ? setInterval(
            () =>
              router.reload({
                only: ['editableImportPlans', 'nonEditableImportPlans'],
              }),
            5000
          )
        : undefined;
    },
    {immediate: true}
  );

  onBeforeUnmount(() => clearInterval(poll));
</script>

<template>
  <LayoutSlot v-if="canSave" name="content-actions">
    <CpButtonLink variant="primary" icon="plus" :href="create()">
      {{ t('New import plan') }}
    </CpButtonLink>
  </LayoutSlot>

  <CpContainer class="@container">
    <div>
      <h2>{{ t('Editable Import Plans') }}</h2>
      <p>
        {{ t('Those import plans can be edited in the control panel.') }}
      </p>
    </div>

    <template #full>
      <AdminTable
        :table="editableTable"
        structure
        :row-attributes="rowAttributes"
        appearance="flush"
        :selectable="bulkActions.length > 0"
        :actions="bulkActions"
      >
        <template #empty-row>
          <craft-empty :label="t('No import plans yet.')" icon="light/upload" />
        </template>
      </AdminTable>

      <template v-if="nonEditableImportPlans.length">
        <div>
          <h2>{{ t('Non-Editable Import Plans') }}</h2>
          <p>
            {{
              t(
                'Those import plans are registered in code and can’t be edited in the control panel.'
              )
            }}
          </p>
        </div>
        <AdminTable
          :table="nonEditableTable"
          structure
          :row-attributes="rowAttributes"
        ></AdminTable>
      </template>
    </template>
  </CpContainer>
</template>

<style scoped>
  /* a step goes with its plan, so only the plan can be selected */
  :deep(.import-plans__step .cp-table-cell--select craft-checkbox) {
    visibility: hidden;
  }

  :deep(.import-step-row) {
    display: grid;
    gap: var(--c-spacing-xs);
  }

  :deep(.import-step-row__label) {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-xs) var(--c-spacing-sm);
  }

  :deep(.import-step-row__source) {
    color: var(--c-text-quiet);
    font-size: 0.85em;
    overflow-wrap: anywhere;
  }

  /* just the buttons: the cells either side of them give them room enough */
  :deep(.cp-table-cell--run) {
    --_cell-spacing-inline: 0;
  }

  :deep(.cp-table-cell--actions) {
    padding-inline-start: var(--c-spacing-md);
  }
</style>
