<script setup lang="ts">
  import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import {useEventListener, useMutationObserver} from '@vueuse/core';
  import {EditableTable} from '../editable-table';
  import type {EditableTableColumns} from '../editable-table/types';
  import type {FormControlPayload} from './types';
  import {inputName} from './runtime';

  type TableControlProps = {
    columns: EditableTableColumns;
    allowAdd?: boolean;
    allowDelete?: boolean;
    allowReorder?: boolean;
    minRows?: number;
    maxRows?: number;
  };
  type TableRow = Record<string, unknown>;

  const props = defineProps<{
    control: FormControlPayload<TableControlProps>;
    value: TableRow[];
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (
      event: 'update:value',
      value: TableRow[],
      kind: 'discrete' | 'typing'
    ): void;
  }>();
  const host = ref<HTMLElement>();
  const table = ref<HTMLTableElement>();
  const tableBody = computed(() => table.value?.tBodies[0]);
  const id = `form-table-${crypto.randomUUID()}`;
  let rows = props.value;
  let instance: EditableTable | undefined;

  useEventListener(host, 'input', () => emitRows('typing'));
  useEventListener(host, 'change', () => emitRows('discrete'));
  const {takeRecords} = useMutationObserver(
    tableBody,
    () => emitRows('discrete'),
    {
      childList: true,
    }
  );

  onMounted(renderTable);
  onBeforeUnmount(() => instance?.destroy());

  watch(
    () => props.value,
    (current) => {
      if (sameRows(current, rows)) {
        return;
      }

      rows = current;
      renderTable();
    },
    {deep: true}
  );
  watch([() => props.control.props, () => props.editable], renderTable, {
    deep: true,
  });

  function renderTable(): void {
    if (!table.value || !tableBody.value) {
      return;
    }

    instance?.destroy();
    instance = undefined;
    const tableElement = table.value!;
    const bodyElement = tableElement.tBodies[0]!;
    const name = inputName(props.control.path);
    bodyElement.replaceChildren();
    rows.forEach((row, index) => {
      EditableTable.createRow(
        String(index),
        props.control.props.columns,
        name,
        row,
        props.editable && props.control.props.allowReorder,
        props.editable && props.control.props.allowDelete,
        !props.editable
      ).appendTo(bodyElement);
    });

    if (!props.editable) {
      const disabledElements = host.value?.querySelectorAll<
        HTMLElement & {disabled: boolean; name: string}
      >('[name], input, select, textarea, button');
      disabledElements?.forEach((element) => {
        element.disabled = true;
        element.name = '';
      });
      return;
    }

    instance = new EditableTable(id, name, props.control.props.columns, {
      allowAdd: props.control.props.allowAdd,
      allowDelete: props.control.props.allowDelete,
      allowReorder: props.control.props.allowReorder,
      minRows: props.control.props.minRows ?? null,
      maxRows: props.control.props.maxRows ?? null,
    });
    takeRecords();

    if (bodyElement.children.length !== rows.length) {
      emitRows('discrete');
    }
  }

  function emitRows(kind: 'discrete' | 'typing'): void {
    if (!props.editable) {
      return;
    }

    const columns = Object.keys(props.control.props.columns);
    const data = new FormData(host.value!.closest('form')!);
    const currentRows = [
      ...(table.value?.querySelectorAll<HTMLTableRowElement>('tbody > tr') ??
        []),
    ].map((row) =>
      Object.fromEntries(
        columns.map((column, index) => [
          column,
          cellValue(row, row.cells[index]!, column, data),
        ])
      )
    );
    if (sameRows(currentRows, rows)) {
      return;
    }

    rows = currentRows;
    emit('update:value', currentRows, kind);
  }

  function cellValue(
    row: HTMLTableRowElement,
    cell: HTMLTableCellElement,
    column: string,
    data: FormData
  ): unknown {
    const rowId = row.dataset.id!;
    const name = `${inputName(props.control.path)}[${rowId}][${column}]`;
    const entries = [...data.entries()].filter(
      ([key]) => key === name || key.startsWith(`${name}[`)
    );

    if (
      ['checkbox', 'lightswitch'].includes(
        props.control.props.columns[column]?.type ?? ''
      )
    ) {
      return entries.some(([key]) => key === name);
    }

    const exact = entries.filter(([key]) => key === name);
    if (exact.length) {
      return String(exact.at(-1)![1]);
    }

    if (entries.length) {
      return Object.fromEntries(
        entries.map(([key, value]) => [
          key.slice(name.length + 1, -1),
          String(value),
        ])
      );
    }

    return rows[Number(rowId)]?.[column] ?? '';
  }

  function sameRows(left: TableRow[], right: TableRow[]): boolean {
    return JSON.stringify(left) === JSON.stringify(right);
  }
</script>

<template>
  <div ref="host" slot="input" :inert="!editable">
    <span role="status" class="sr-only" data-status-message />
    <input v-if="editable" type="hidden" :name="inputName(control.path)" />
    <table :id="id" ref="table" class="editable cp-table cp-table--editable">
      <thead>
        <tr>
          <th
            v-for="(column, key) in control.props.columns"
            :key="key"
            scope="col"
          >
            {{ column.heading ?? column.label }}
          </th>
          <th
            v-if="
              editable &&
              (control.props.allowDelete || control.props.allowReorder)
            "
            :colspan="
              control.props.allowDelete && control.props.allowReorder ? 2 : 1
            "
            scope="colgroup"
          />
        </tr>
      </thead>
      <tbody v-once />
    </table>
    <div v-if="editable && control.props.allowAdd">
      <craft-button type="button" command="--add-row">
        {{ t('Add a row') }}
      </craft-button>
    </div>
  </div>
</template>
