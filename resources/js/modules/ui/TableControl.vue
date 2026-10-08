<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/info-icon/info-icon';
  import '@craftcms/ui/components/reorder-button/reorder-button';
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, inject, nextTick, onMounted, ref} from 'vue';
  import {UiControlStructure, inputName} from './runtime';
  import type {
    UiChange,
    UiChangeKind,
    UiControlPayload,
    UiNodePayload,
    UiPayload,
    UiValue,
  } from './types';
  import TableCell from './table/TableCell.vue';
  import {rowFields} from './table/rowUis';
  import {firstFocusableWithin} from '@/common/utils/dom';
  import {useReorderableRows} from '@/common/composables/useReorderableRows';
  import DropIndicator from '@/common/components/DropIndicator.vue';
  import {useAutopopulation} from './table/useAutopopulation';
  import {useTableRows} from './table/useTableRows';
  import {useTableBehavior} from './table/useTableBehavior';
  import type {TableControlProps, TableRow, TableValue} from './table/types';

  const props = defineProps<{
    control: UiControlPayload<TableControlProps>;
    value: TableValue;
    editable: boolean;
    values?: UiPayload['values'];
    errors?: UiPayload['errors'];
    touchedPaths?: Set<string>;
    uiScope?: string[];
    uiRefreshable?: boolean;
  }>();
  const emit = defineEmits<{
    (
      event: 'update:value',
      value: TableValue,
      kind: UiChangeKind,
      change?: UiChange
    ): void;
  }>();
  defineSlots<{
    'row-actions'(props: {
      row: TableRow;
      index: number;
      rowKey: string;
    }): unknown;
  }>();
  const host = ref<HTMLElement>();
  const status = ref('');
  const structure = inject(UiControlStructure, undefined);
  const {rows, uis, keyed, externalValue, createRow, rowUi} =
    useTableRows(props);
  const {pending, errorsCleared, clearErrors} = useTableBehavior(
    () => props.control.path,
    () => keyed.value
  );
  const autopopulation = useAutopopulation();
  const columns = computed(() => Object.entries(props.control.props.columns));
  const rowIdField = computed(() =>
    typeof props.control.props.includeRowId === 'string'
      ? props.control.props.includeRowId
      : 'rowId'
  );
  const autopopulations = computed(() =>
    columns.value.flatMap(([key, column]) => {
      if (!column.autopopulate) return [];
      return [
        key === 'heading' && column.autopopulate === 'handle'
          ? {source: key, target: column.autopopulate}
          : {source: column.autopopulate, target: key},
      ];
    })
  );
  const visibleColumns = computed(() =>
    columns.value.filter(([, column]) => column.type !== 'hidden')
  );
  const canAdd = computed(
    () =>
      props.editable &&
      !pending.value &&
      !props.control.props.staticRows &&
      props.control.props.allowAdd &&
      (props.control.props.maxRows == null ||
        rows.value.length < props.control.props.maxRows)
  );
  const canDelete = computed(
    () =>
      props.editable &&
      !pending.value &&
      !props.control.props.staticRows &&
      props.control.props.allowDelete &&
      rows.value.length > (props.control.props.minRows ?? 0)
  );
  const canReorder = computed(
    () =>
      props.editable &&
      !pending.value &&
      !props.control.props.staticRows &&
      props.control.props.allowReorder
  );
  const {setRowRef, setHandleRef, getDragState, getDropState} =
    useReorderableRows({
      getRowIds: () => rows.value.map((row) => row.id),
      onReorder: moveRow,
      enabled: () => Boolean(canReorder.value),
    });

  function closestEdge(id: string) {
    const state = getDropState(id);
    return state.type === 'is-over' ? state.closestEdge : null;
  }
  const effectiveValues = computed(() => props.values ?? {});
  const effectiveTouched = computed(
    () => props.touchedPaths ?? new Set<string>()
  );
  const renderedRows = computed(() =>
    rows.value.map((row, index) => ({
      row,
      fields: rowFields(rowUi(index)?.nodes ?? []),
    }))
  );

  onMounted(() => {
    structure?.(props.control, uis.value);
    if (!props.editable) return;

    let added = false;
    while (rows.value.length < (props.control.props.minRows ?? 0)) {
      appendRow();
      added = true;
    }
    if (added) commit('discrete');
  });

  function appendRow(): TableRow {
    const value = {...props.control.props.defaultValues};
    for (const [key, column] of columns.value) {
      if (!(key in value))
        value[key] =
          column.value ??
          (['checkbox', 'lightswitch'].includes(column.type) ? false : '');
      if (column.prefixSelect && !(column.prefixSelect.key in value)) {
        value[column.prefixSelect.key] =
          column.prefixSelect.options[0]?.value ?? '';
      }
    }
    const row = createRow(value);
    row.ui = props.control.props.rowTemplate;
    const {includeRowId} = props.control.props;
    if (includeRowId) {
      row.value[rowIdField.value] = row.id;
    }
    rows.value.push(row);
    return row;
  }

  function rowKey(index: number): string {
    return keyed.value ? rows.value[index]!.key : String(index);
  }

  function commit(kind: UiChangeKind, path = props.control.path): void {
    const value = externalValue();
    structure?.(props.control, uis.value);
    emit('update:value', value, kind, {
      kind,
      path,
      scope: props.uiScope,
      refreshable: Boolean(props.uiRefreshable),
    });
  }

  function fieldFor(
    fields: UiNodePayload[],
    column: string
  ): UiNodePayload | undefined {
    return fields.find((field) => field.control?.path.at(-1) === column);
  }

  function disabledCell(row: TableRow, key: string): boolean {
    return columns.value.some(
      ([source, column]) =>
        column.toggle?.some((target) =>
          target === key
            ? !row.value[source]
            : target === `!${key}` && Boolean(row.value[source])
        ) ?? false
    );
  }

  function updateCell(
    index: number,
    key: string,
    value: UiValue,
    kind: UiChangeKind = 'discrete'
  ): void {
    const row = rows.value[index];
    if (
      !props.editable ||
      !row ||
      row.value[key] === value ||
      disabledCell(row, key)
    )
      return;

    autopopulation.update(row.id, row.value, key, value, autopopulations.value);
    if (props.control.props.columns[key]?.radioMode && value) {
      for (const other of rows.value) {
        if (other.id !== row.id) other.value[key] = false;
      }
    }
    commit(kind, [...props.control.path, rowKey(index), key]);
  }

  async function addRow(): Promise<void> {
    if (!canAdd.value) return;

    clearErrors();
    const row = appendRow();
    commit('discrete');
    status.value = t('Row added.');
    await nextTick();
    await focusRow(row.id);
  }

  async function deleteRow(index: number): Promise<void> {
    if (!canDelete.value) return;

    clearErrors();
    autopopulation.forget(rows.value[index]!.id);
    rows.value.splice(index, 1);
    commit('discrete');
    status.value = t('Row deleted.');
    await nextTick();
    const row = rows.value[Math.min(index, rows.value.length - 1)];
    if (row) await focusRow(row.id);
    else host.value?.querySelector<HTMLElement>('[data-add-row]')?.focus();
  }

  async function reorder(
    index: number,
    event: CustomEvent<{direction: 'up' | 'down'}>
  ): Promise<void> {
    const next = event.detail.direction === 'up' ? index - 1 : index + 1;
    if (!moveRow(index, next)) return;

    const invoker = event.currentTarget as HTMLElement;
    await nextTick();
    firstFocusableWithin(invoker)?.focus();
  }

  function moveRow(index: number, next: number): boolean {
    if (!canReorder.value || next < 0 || next >= rows.value.length)
      return false;

    clearErrors();
    const [row] = rows.value.splice(index, 1);
    rows.value.splice(next, 0, row!);
    commit('discrete');
    status.value = t('Row moved to position {position}.', {position: next + 1});
    return true;
  }

  function position(index: number): 'only' | 'first' | 'last' | 'middle' {
    if (rows.value.length === 1) return 'only';
    if (index === 0) return 'first';
    if (index === rows.value.length - 1) return 'last';
    return 'middle';
  }

  async function focusRow(id: string, column?: string): Promise<void> {
    const row = host.value?.querySelector<HTMLElement>(`[data-row-id="${id}"]`);
    const cell = column
      ? [...(row?.querySelectorAll<HTMLElement>('[data-column]') ?? [])].find(
          (cell) => cell.dataset.column === column
        )
      : row;
    if (!cell) return;

    const field = cell.querySelector('craft-field');
    if (field && 'updateComplete' in field) await field.updateComplete;
    const control = field?.querySelector('[slot="input"]');
    if (control && 'updateComplete' in control) await control.updateComplete;

    firstFocusableWithin(cell)?.focus();
  }

  function compatible(
    key: string,
    feature: 'tsvPaste' | 'enterNavigation'
  ): boolean {
    const column = props.control.props.columns[key];
    return (
      column?.[feature] === true ||
      (column?.[feature] === undefined &&
        [
          'singleline',
          'heading',
          'multiline',
          'number',
          'email',
          'url',
        ].includes(column?.type ?? ''))
    );
  }

  async function navigate(
    index: number,
    key: string,
    event: KeyboardEvent
  ): Promise<void> {
    if (
      !props.editable ||
      event.defaultPrevented ||
      event.key !== 'Enter' ||
      event.altKey ||
      event.isComposing ||
      !compatible(key, 'enterNavigation') ||
      disabledCell(rows.value[index]!, key) ||
      props.control.props.columns[key]?.static
    )
      return;

    if (
      props.control.props.columns[key]?.type === 'multiline' &&
      !event.ctrlKey &&
      !event.metaKey
    )
      return;

    const next = index + (event.shiftKey ? -1 : 1);
    event.preventDefault();
    event.stopPropagation();
    if (next < 0) return;
    if (next === rows.value.length) {
      if (!canAdd.value) return;
      appendRow();
      commit('discrete');
    }
    await nextTick();
    await focusRow(rows.value[next]!.id, key);
  }

  async function paste(
    index: number,
    key: string,
    event: ClipboardEvent
  ): Promise<void> {
    if (
      !props.editable ||
      pending.value ||
      event.defaultPrevented ||
      !compatible(key, 'tsvPaste') ||
      disabledCell(rows.value[index]!, key) ||
      props.control.props.columns[key]?.static
    )
      return;

    const text = event.clipboardData?.getData('text/plain');
    if (!text || (!text.includes('\t') && !text.includes('\n'))) return;

    const values = text
      .replace(/\r\n?/g, '\n')
      .replace(/\n$/, '')
      .split('\n')
      .map((line) => line.split('\t'));
    const start = visibleColumns.value.findIndex(([column]) => column === key);
    event.preventDefault();
    clearErrors();
    for (const [offset, cells] of values.entries()) {
      if (!rows.value[index + offset]) {
        if (!canAdd.value) break;
        appendRow();
      }
      const row = rows.value[index + offset]!;
      for (const [cellIndex, value] of cells.entries()) {
        const column = visibleColumns.value[start + cellIndex]?.[0];
        const field = fieldFor(
          renderedRows.value[index + offset]!.fields,
          column ?? ''
        );
        if (
          column &&
          compatible(column, 'tsvPaste') &&
          !disabledCell(row, column) &&
          !props.control.props.columns[column]?.static &&
          field?.control?.mode === 'editable'
        ) {
          autopopulation.update(
            row.id,
            row.value,
            column,
            value,
            autopopulations.value
          );
        }
      }
    }
    commit('discrete');
    await nextTick();
    await focusRow(
      rows.value[Math.min(index + values.length - 1, rows.value.length - 1)]!
        .id,
      key
    );
  }
</script>

<template>
  <div ref="host" slot="input" class="min-w-0">
    <span role="status" class="sr-only">{{ status }}</span>
    <input
      v-if="editable"
      type="hidden"
      :name="inputName(control.path)"
      value=""
    />
    <div class="overflow-x-auto">
      <table
        class="editable cp-table cp-table--editable cp-table--ruled w-full"
      >
        <thead>
          <tr>
            <th
              v-for="[key, column] in columns"
              :key="key"
              scope="col"
              :hidden="column.type === 'hidden'"
              :style="{
                width:
                  typeof column.width === 'number'
                    ? `${column.width}%`
                    : column.width,
              }"
            >
              <span v-if="column.headingHtml" v-html="column.headingHtml" />
              <template v-else>{{
                column.heading ?? column.label ?? key
              }}</template>
              <span v-if="column.required">({{ t('Required') }})</span>
              <craft-info-icon v-if="column.infoHtml" .disabled="!editable">
                <span v-html="column.infoHtml" />
              </craft-info-icon>
            </th>
            <th
              v-if="
                $slots['row-actions'] ||
                (editable &&
                  (control.props.allowReorder || control.props.allowDelete))
              "
              scope="col"
            >
              <span class="sr-only">{{ t('Row actions') }}</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="({row, fields}, index) in renderedRows"
            :key="row.id"
            :ref="
              (element) =>
                setRowRef(element as HTMLTableRowElement | null, row.id)
            "
            :class="{
              'row--dragging': getDragState(row.id).type === 'is-dragging',
            }"
            :data-row-id="row.id"
          >
            <component
              :is="column.type === 'heading' ? 'th' : 'td'"
              v-for="[key, column] in columns"
              :key="key"
              :scope="column.type === 'heading' ? 'row' : undefined"
              :data-column="key"
              :hidden="column.type === 'hidden'"
              :class="[
                column.class,
                {
                  error:
                    !errorsCleared &&
                    control.props.errors?.[rowKey(index)]?.[key],
                  disabled: disabledCell(row, key),
                },
              ]"
              @keydown="navigate(index, key, $event)"
              @paste="paste(index, key, $event)"
            >
              <DropIndicator :edge="closestEdge(row.id)" contained />
              <input
                v-if="
                  editable &&
                  control.props.includeRowId &&
                  !control.props.columns[rowIdField] &&
                  key === columns[0]?.[0]
                "
                type="hidden"
                :name="inputName([...control.path, rowKey(index), rowIdField])"
                :value="row.value[rowIdField] ?? row.key"
              />
              <TableCell
                v-if="fieldFor(fields, key)"
                :node="fieldFor(fields, key)!"
                :invalid="
                  Boolean(
                    !errorsCleared &&
                    control.props.errors?.[rowKey(index)]?.[key]
                  )
                "
                :value="row.value[key]"
                :label="
                  t('{heading}, row {row}', {
                    heading: column.heading ?? column.label ?? key,
                    row: index + 1,
                  })
                "
                :editable="
                  editable && !disabledCell(row, key) && !column.static
                "
                :values="effectiveValues"
                :errors="errors ?? []"
                :touched-paths="effectiveTouched"
                :ui-scope="uiScope ?? []"
                :ui-refreshable="Boolean(uiRefreshable)"
                @update:value="
                  (value, kind) => updateCell(index, key, value, kind)
                "
                @change="commit($event.kind, $event.path)"
              />
              <span
                v-else-if="column.type === 'html' || column.html"
                v-html="String(row.value[key] ?? '')"
              />
              <span v-else>{{ row.value[key] }}</span>
              <TableCell
                v-if="
                  column.prefixSelect &&
                  fieldFor(fields, column.prefixSelect.key)
                "
                :node="fieldFor(fields, column.prefixSelect.key)!"
                :value="row.value[column.prefixSelect.key]"
                :label="column.prefixSelect.label"
                :editable="
                  editable && !disabledCell(row, key) && !column.static
                "
                :values="effectiveValues"
                :errors="errors ?? []"
                :touched-paths="effectiveTouched"
                :ui-scope="uiScope ?? []"
                :ui-refreshable="Boolean(uiRefreshable)"
                @update:value="
                  (value, kind) =>
                    updateCell(index, column.prefixSelect!.key, value, kind)
                "
              />
            </component>
            <td
              v-if="
                $slots['row-actions'] ||
                (editable &&
                  (control.props.allowReorder || control.props.allowDelete))
              "
            >
              <DropIndicator :edge="closestEdge(row.id)" contained />
              <div class="flex items-center justify-end gap-2">
                <slot
                  name="row-actions"
                  :row="row"
                  :index="index"
                  :row-key="rowKey(index)"
                />
                <craft-reorder-button
                  v-if="
                    editable &&
                    control.props.allowReorder &&
                    !control.props.staticRows
                  "
                  :label="t('Reorder row {row}', {row: index + 1})"
                  :position="position(index)"
                  :ref="
                    (element: unknown) =>
                      setHandleRef(element as HTMLElement | null, row.id)
                  "
                  .disabled="!canReorder"
                  @craft-reorder="reorder(index, $event)"
                />
                <craft-button
                  v-if="editable && control.props.allowDelete"
                  type="button"
                  icon="trash"
                  variant="plain"
                  .disabled="!canDelete"
                  :aria-label="t('Delete row {row}', {row: index + 1})"
                  @click="deleteRow(index)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <craft-button
      v-if="editable && !control.props.staticRows && control.props.allowAdd"
      data-add-row
      type="button"
      icon="plus"
      variant="dashed"
      class="w-full"
      .disabled="!canAdd"
      @click="addRow"
    >
      {{ control.props.addRowLabel ?? t('Add a row') }}
    </craft-button>
  </div>
</template>

<style scoped>
  :deep(craft-checkbox) {
    --c-checkbox-size: 24px;
  }

  .row--dragging {
    opacity: 0.4;
  }
</style>
