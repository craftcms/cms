<script setup lang="ts">
  import {computed, useAttrs} from 'vue';
  import type {
    KeyedTableColumn,
    KeyedTableRow,
    KeyedTableValue,
  } from '@craftcms/ui';
  import '@craftcms/ui/components/keyed-table/keyed-table';
  import type {FormElementBinding, JsonValue} from '../types';

  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: KeyedTableValue];
  }>();
  const attrs = useAttrs();
  const hostAttributes = computed(() => ({...props.attributes, ...attrs}));
  const columns = computed<KeyedTableColumn[]>(() =>
    Array.isArray(props.config.columns)
      ? (props.config.columns as KeyedTableColumn[])
      : []
  );
  const rows = computed<KeyedTableRow[]>(() =>
    Array.isArray(props.config.rows)
      ? (props.config.rows as KeyedTableRow[])
      : []
  );
  const value = computed<KeyedTableValue>(() => {
    const value = props.binding?.value;

    return value && typeof value === 'object' && !Array.isArray(value)
      ? (value as KeyedTableValue)
      : {};
  });

  function updateValue(event: Event): void {
    emit(
      'update:value',
      (event.target as HTMLElementTagNameMap['craft-keyed-table']).value
    );
  }
</script>

<template>
  <craft-keyed-table
    v-bind="hostAttributes"
    .value="value"
    .columns="columns"
    .rows="rows"
    :readonly="binding?.readOnly ?? false"
    @input="updateValue"
  ></craft-keyed-table>
</template>
