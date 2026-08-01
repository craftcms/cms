<script setup lang="ts">
  import {computed} from 'vue';
  import type {FormElementBinding, JsonValue} from '../types';
  import '@craftcms/ui/components/input/input';

  type Column = {
    key: string;
    label: string;
    placeholder?: string;
    code?: boolean;
  };
  type Row = {key: string; label: string};

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: Record<string, unknown>];
  }>();

  const columns = computed<Column[]>(() =>
    Array.isArray(props.config.columns)
      ? (props.config.columns as Column[])
      : []
  );
  const rows = computed<Row[]>(() =>
    Array.isArray(props.config.rows) ? (props.config.rows as Row[]) : []
  );
  const values = computed<Record<string, unknown>>(() =>
    props.binding?.value && typeof props.binding.value === 'object'
      ? (props.binding.value as Record<string, unknown>)
      : {}
  );

  function cellValue(row: Row, column: Column): string {
    const rowValue = values.value[row.key];

    if (!rowValue || typeof rowValue !== 'object') {
      return '';
    }

    return String((rowValue as Record<string, unknown>)[column.key] ?? '');
  }

  function inputName(row: Row, column: Column): string | undefined {
    const name = props.attributes.name;

    return typeof name === 'string'
      ? `${name}[${row.key}][${column.key}]`
      : undefined;
  }

  function update(row: Row, column: Column, event: Event): void {
    const currentRow = values.value[row.key];
    const rowValue =
      currentRow && typeof currentRow === 'object'
        ? (currentRow as Record<string, unknown>)
        : {};

    emit('update:value', {
      ...values.value,
      [row.key]: {
        ...rowValue,
        [column.key]: (event.target as HTMLElementTagNameMap['craft-input'])
          .value,
      },
    });
  }
</script>

<template>
  <div
    :id="typeof attributes.id === 'string' ? attributes.id : undefined"
    class="keyed-table"
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
          <th scope="col"></th>
          <th v-for="column in columns" :key="column.key" scope="col">
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.key" data-keyed-table-row>
          <th scope="row">{{ row.label }}</th>
          <td v-for="column in columns" :key="column.key">
            <craft-input
              :data-keyed-table-cell="`${row.key}:${column.key}`"
              :name="inputName(row, column)"
              :value="cellValue(row, column)"
              :placeholder="column.placeholder"
              :class="{code: column.code}"
              :disabled="binding?.readOnly"
              @input="update(row, column, $event)"
            ></craft-input>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
  .keyed-table {
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  th,
  td {
    padding: var(--c-spacing-xs);
    text-align: start;
  }
</style>
