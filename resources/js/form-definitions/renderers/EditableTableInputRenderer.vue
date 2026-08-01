<script setup lang="ts">
  import {computed, inject, onMounted, ref, toRaw, watch} from 'vue';
  import {useEventListener} from '@vueuse/core';
  import {toHandle} from '@craftcms/ui/utilities/string';
  import {t} from '@craftcms/ui/utilities/translate';
  import type CraftCheckbox from '@craftcms/ui/components/checkbox/checkbox';
  import type {FormElementBinding, JsonValue} from '../types';
  import {
    editableTableColumnsChangedEvent,
    editableTableColumnsEventTarget,
  } from '../editable-table-columns';
  import OptionRowsRenderer from './OptionRowsRenderer.vue';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/checkbox/checkbox';
  import '@craftcms/ui/components/input/input';
  import '@craftcms/ui/components/input-color/input-color';
  import '@craftcms/ui/components/reorder-button/reorder-button';
  import '@craftcms/ui/components/select/select';
  import '@craftcms/ui/components/switch/switch';

  type Option = {
    label: string;
    value: JsonValue;
    default?: boolean;
  };

  type Column = {
    key: string;
    label: string;
    type: string;
    width?: string | number;
    code?: boolean;
    autoPopulate?: string;
    nestedOptions?: boolean;
    options?: Option[];
    class?: string;
  };

  type RowValue = Record<string, unknown>;
  type Row = {key: string; value: RowValue};

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: RowValue[] | Record<string, RowValue>];
  }>();

  const configuredColumns = computed<Column[]>(() =>
    Array.isArray(props.config.columns)
      ? (props.config.columns as Column[])
      : []
  );
  const receivedColumns = ref<Column[]>();
  const columns = computed(
    () => receivedColumns.value ?? configuredColumns.value
  );
  const keyed = computed(() => props.config.keyed === true);
  const includeRowId = computed(() => props.config.includeRowId === true);
  const readOnly = computed(() => props.binding?.readOnly ?? false);
  const rowKeys = new WeakMap<RowValue, string>();
  let nextRowKey = 0;

  type ColumnsChangedDetail = {name: string; columns: Column[]};
  const injectedColumnsEventTarget = inject(editableTableColumnsEventTarget);

  if (!injectedColumnsEventTarget) {
    throw new Error('Editable Table renderer requires a Form Definition.');
  }

  const columnsEventTarget: EventTarget = injectedColumnsEventTarget;

  useEventListener(
    columnsEventTarget,
    editableTableColumnsChangedEvent,
    receiveColumns as EventListener
  );

  onMounted(publishColumns);

  const rows = computed<Row[]>(() => {
    const value = props.binding?.value;

    if (keyed.value && value && typeof value === 'object') {
      return Object.entries(value as Record<string, RowValue>).map(
        ([key, row]) => ({
          key,
          value: row,
        })
      );
    }

    if (!Array.isArray(value)) {
      return [];
    }

    return (value as RowValue[]).map((row) => ({
      key: rowIdentity(row),
      value: row,
    }));
  });

  watch(rows, publishColumns, {deep: true});

  function publishColumns(): void {
    if (props.config.definesColumns !== true || !props.binding?.name) {
      return;
    }

    columnsEventTarget.dispatchEvent(
      new CustomEvent<ColumnsChangedDetail>(editableTableColumnsChangedEvent, {
        detail: {
          name: props.binding.name,
          columns: rows.value.map(columnFromRow),
        },
      })
    );
  }

  function receiveColumns(event: CustomEvent<ColumnsChangedDetail>): void {
    if (event.detail.name !== props.config.columnsFrom) {
      return;
    }

    receivedColumns.value = event.detail.columns;
    syncRowsToColumns(event.detail.columns);
  }

  function columnFromRow(row: Row): Column {
    const configuredType = textValue(row.value.type);
    const type =
      configuredType === 'heading' || configuredType === 'singleline'
        ? 'text'
        : configuredType;
    const width = textValue(row.value.width);
    const options = Array.isArray(row.value.options)
      ? (row.value.options as Option[])
      : undefined;

    return {
      key: row.key,
      label:
        textValue(row.value.heading) || textValue(row.value.handle) || row.key,
      type,
      ...(width ? {width} : {}),
      ...(options ? {options} : {}),
      ...(configuredType === 'heading' ? {class: 'heading'} : {}),
    };
  }

  function syncRowsToColumns(nextColumns: Column[]): void {
    if (keyed.value || rows.value.length === 0) {
      return;
    }

    if (nextColumns.length === 0) {
      emit('update:value', []);

      return;
    }

    emitRows(
      rows.value.map((row) => ({
        key: row.key,
        value: {
          ...(includeRowId.value
            ? {rowId: textValue(row.value.rowId) || row.key}
            : {}),
          ...Object.fromEntries(
            nextColumns.map((column) => [
              column.key,
              Object.hasOwn(row.value, column.key)
                ? row.value[column.key]
                : defaultValue(column),
            ])
          ),
        },
      }))
    );
  }

  function rowIdentity(row: RowValue): string {
    if (typeof row.rowId === 'string' && row.rowId !== '') {
      return row.rowId;
    }

    const rawRow = toRaw(row);

    if (!rowKeys.has(rawRow)) {
      rowKeys.set(rawRow, `row-${nextRowKey++}`);
    }

    return rowKeys.get(rawRow)!;
  }

  function textValue(value: unknown): string {
    if (typeof value === 'string' || typeof value === 'number') {
      return String(value);
    }

    if (value && typeof value === 'object' && 'value' in value) {
      return textValue(value.value);
    }

    return '';
  }

  function inputName(
    index: number,
    row: Row,
    property: string
  ): string | undefined {
    const name = props.attributes.name;

    if (typeof name !== 'string') {
      return undefined;
    }

    const rowKey = keyed.value ? row.key : index;

    return `${name}[${rowKey}][${property}]`;
  }

  function cellStyle(column: Column): Record<string, string> | undefined {
    if (column.width === undefined) {
      return undefined;
    }

    const width =
      typeof column.width === 'number' ? `${column.width}px` : column.width;

    return {width};
  }

  function inputType(type: string): string {
    return ['date', 'email', 'number', 'time', 'url'].includes(type)
      ? type
      : 'text';
  }

  function selectedValue(event: Event, column: Column): JsonValue {
    const selected = (event.target as HTMLSelectElement).value;
    const option = column.options?.find(
      ({value}) => String(value ?? '') === selected
    );

    return option?.value ?? null;
  }

  function inputValue(event: Event): string {
    const target = event.currentTarget as
      | HTMLTextAreaElement
      | HTMLElementTagNameMap['craft-input'];

    return target.value;
  }

  function checkedValue(event: Event): boolean {
    return (event.currentTarget as CraftCheckbox).checked;
  }

  function switchValue(event: Event): boolean {
    return (event.currentTarget as HTMLElementTagNameMap['craft-switch'])
      .checked;
  }

  function emitRows(nextRows: Row[]): void {
    if (keyed.value) {
      emit(
        'update:value',
        Object.fromEntries(nextRows.map(({key, value}) => [key, value]))
      );

      return;
    }

    emit(
      'update:value',
      nextRows.map(({value}) => value)
    );
  }

  function updateRow(index: number, changes: RowValue): void {
    emitRows(
      rows.value.map((row, rowIndex) => {
        if (rowIndex !== index) {
          return row;
        }

        const value = {...row.value, ...changes};

        if (includeRowId.value && !value.rowId) {
          value.rowId = row.key;
        }

        rowKeys.set(toRaw(value), row.key);

        return {key: row.key, value};
      })
    );
  }

  function updateCell(index: number, column: Column, value: unknown): void {
    const row = rows.value[index]!;
    const changes: RowValue = {[column.key]: value};

    if (column.autoPopulate) {
      const currentSource = textValue(row.value[column.key]);
      const currentTarget = textValue(row.value[column.autoPopulate]);

      if (
        currentTarget === '' ||
        currentTarget === generatedValue(currentSource)
      ) {
        changes[column.autoPopulate] = generatedValue(textValue(value));
      }
    }

    updateRow(index, changes);
  }

  function generatedValue(value: string): string {
    return toHandle(value, {
      allowNonAlphaStart: true,
      handleCasing: window.Cp.$config?.get('handleCasing', 'camel') ?? 'camel',
    });
  }

  function nestedOptions(row: Row): RowValue[] {
    return Array.isArray(row.value.options)
      ? (row.value.options as RowValue[])
      : [];
  }

  function hasNestedOptions(row: Row): boolean {
    return columns.value.some(
      (column) =>
        column.nestedOptions === true &&
        textValue(row.value[column.key]) === 'select'
    );
  }

  function nestedOptionsAttributes(
    index: number,
    row: Row
  ): Record<string, JsonValue> {
    return {name: inputName(index, row, 'options') ?? ''};
  }

  function nestedOptionsBinding(row: Row): FormElementBinding {
    return {
      name: 'options',
      value: nestedOptions(row),
      readOnly: readOnly.value,
    };
  }

  function defaultRow(): RowValue {
    const row = Object.fromEntries(
      columns.value.map((column) => [column.key, defaultValue(column)])
    );
    const configured = props.config.defaultRow;

    if (configured && typeof configured === 'object') {
      Object.assign(row, configured);
    }

    if (includeRowId.value) {
      row.rowId = crypto.randomUUID();
    }

    return row;
  }

  function defaultValue(column: Column): JsonValue {
    if (column.type === 'checkbox' || column.type === 'lightswitch') {
      return false;
    }

    if (column.type === 'select') {
      return column.options?.find((option) => option.default)?.value ?? '';
    }

    return '';
  }

  function addRow(): void {
    const value = defaultRow();
    const key = keyed.value ? nextKey() : rowIdentity(value);

    emitRows([...rows.value, {key, value}]);
  }

  function nextKey(): string {
    let index = 1;

    while (rows.value.some(({key}) => key === `new${index}`)) {
      index++;
    }

    return `new${index}`;
  }

  function deleteRow(index: number): void {
    emitRows(rows.value.filter((_, rowIndex) => rowIndex !== index));
  }

  function reorderRow(index: number, event: Event): void {
    const direction = (event as CustomEvent<{direction: 'up' | 'down'}>).detail
      .direction;
    const destination = direction === 'up' ? index - 1 : index + 1;

    if (destination < 0 || destination >= rows.value.length) {
      return;
    }

    const nextRows = [...rows.value];
    [nextRows[index], nextRows[destination]] = [
      nextRows[destination]!,
      nextRows[index]!,
    ];
    emitRows(nextRows);
  }

  function position(index: number): 'first' | 'middle' | 'last' {
    if (index === 0) {
      return 'first';
    }

    return index === rows.value.length - 1 ? 'last' : 'middle';
  }
</script>

<template>
  <div
    :id="typeof attributes.id === 'string' ? attributes.id : undefined"
    :data-editable-table="binding?.name"
    role="group"
    :aria-labelledby="
      typeof attributes['aria-labelledby'] === 'string'
        ? attributes['aria-labelledby']
        : undefined
    "
    :aria-describedby="
      typeof attributes['aria-describedby'] === 'string'
        ? attributes['aria-describedby']
        : undefined
    "
  >
    <table>
      <thead>
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            scope="col"
            :style="cellStyle(column)"
          >
            {{ column.label }}
          </th>
          <th scope="col">
            <span class="visually-hidden">{{ t('Actions') }}</span>
          </th>
        </tr>
      </thead>
      <tbody>
        <template v-for="(row, index) in rows" :key="row.key">
          <tr data-editable-table-row :data-row-key="row.key">
            <td
              v-for="column in columns"
              :key="column.key"
              :style="cellStyle(column)"
            >
              <craft-select
                v-if="column.type === 'select'"
                :disabled="readOnly"
                :data-table-cell="`${row.key}:${column.key}`"
              >
                <select
                  slot="input"
                  :name="inputName(index, row, column.key)"
                  :value="textValue(row.value[column.key])"
                  :disabled="readOnly"
                  :aria-label="column.label"
                  @change="
                    updateCell(index, column, selectedValue($event, column))
                  "
                >
                  <option
                    v-for="option in column.options ?? []"
                    :key="String(option.value)"
                    :value="String(option.value ?? '')"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </craft-select>
              <craft-checkbox
                v-else-if="column.type === 'checkbox'"
                :name="inputName(index, row, column.key)"
                :label="column.label"
                label-sr-only
                .checked="Boolean(row.value[column.key])"
                .disabled="readOnly"
                :data-table-cell="`${row.key}:${column.key}`"
                @change="updateCell(index, column, checkedValue($event))"
              ></craft-checkbox>
              <craft-switch
                v-else-if="column.type === 'lightswitch'"
                :name="inputName(index, row, column.key)"
                :label="column.label"
                label-sr-only
                :checked="Boolean(row.value[column.key])"
                :disabled="readOnly"
                :data-table-cell="`${row.key}:${column.key}`"
                @change="updateCell(index, column, switchValue($event))"
              ></craft-switch>
              <textarea
                v-else-if="column.type === 'multiline'"
                :name="inputName(index, row, column.key)"
                :value="textValue(row.value[column.key])"
                :readonly="readOnly"
                :aria-label="column.label"
                :data-table-cell="`${row.key}:${column.key}`"
                @input="updateCell(index, column, inputValue($event))"
              ></textarea>
              <craft-input-color
                v-else-if="column.type === 'color'"
                :name="inputName(index, row, column.key)"
                :value="textValue(row.value[column.key])"
                :readonly="readOnly"
                :disabled="readOnly"
                :label="column.label"
                label-sr-only
                :data-table-cell="`${row.key}:${column.key}`"
                @input="updateCell(index, column, inputValue($event))"
              ></craft-input-color>
              <craft-input
                v-else
                :name="inputName(index, row, column.key)"
                :type="inputType(column.type)"
                :value="textValue(row.value[column.key])"
                :readonly="readOnly"
                :class="[column.class, {code: column.code}]"
                :label="column.label"
                label-sr-only
                :data-table-cell="`${row.key}:${column.key}`"
                @input="updateCell(index, column, inputValue($event))"
              ></craft-input>
            </td>
            <td>
              <div class="actions">
                <craft-reorder-button
                  :position="position(index)"
                  :disabled="readOnly"
                  @reorder="reorderRow(index, $event)"
                ></craft-reorder-button>
                <craft-button
                  type="button"
                  size="small"
                  variant="plain"
                  :disabled="readOnly"
                  data-delete-row
                  @click="deleteRow(index)"
                  >{{ t('Delete') }}</craft-button
                >
              </div>
            </td>
          </tr>
          <tr v-if="hasNestedOptions(row)" data-table-nested-options>
            <td :colspan="columns.length + 1">
              <OptionRowsRenderer
                :config="{}"
                :attributes="nestedOptionsAttributes(index, row)"
                :binding="nestedOptionsBinding(row)"
                @update:value="updateRow(index, {options: $event})"
              />
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    <input
      v-for="(row, index) in includeRowId ? rows : []"
      :key="`${row.key}:rowId`"
      type="hidden"
      :name="inputName(index, row, 'rowId')"
      :value="textValue(row.value.rowId) || row.key"
    />
    <craft-button
      type="button"
      size="small"
      :disabled="readOnly || columns.length === 0"
      data-add-row
      @click="addRow"
      >{{
        typeof config.addRowLabel === 'string'
          ? config.addRowLabel
          : t('Add a row')
      }}</craft-button
    >
  </div>
</template>

<style scoped>
  table {
    width: 100%;
    border-collapse: collapse;
    margin-block-end: var(--c-spacing-sm);
  }

  th,
  td {
    padding: var(--c-spacing-xs);
    text-align: start;
    vertical-align: top;
  }

  textarea {
    width: 100%;
  }

  .actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }
</style>
