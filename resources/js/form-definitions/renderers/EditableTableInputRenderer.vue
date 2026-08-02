<script setup lang="ts">
  import {computed, inject, useAttrs} from 'vue';
  import type {
    EditableTableColumn,
    EditableTableRow,
    EditableTableValue,
  } from '@craftcms/ui';
  import '@craftcms/ui/components/editable-table/editable-table';
  import {editableTableCoordinationScope} from '../editable-table-columns';
  import type {FormElementBinding, JsonValue} from '../types';

  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: EditableTableValue];
  }>();
  const coordinationScope = inject(editableTableCoordinationScope);

  if (!coordinationScope) {
    throw new Error('Editable Table renderer requires a Form Definition.');
  }

  const attrs = useAttrs();
  const hostProperties = computed(() => ({
    ...props.attributes,
    ...attrs,
    coordinationScope,
    sourceName: props.binding?.name,
  }));
  const columns = computed<EditableTableColumn[]>(() =>
    Array.isArray(props.config.columns)
      ? (props.config.columns as EditableTableColumn[])
      : []
  );
  const defaultRow = computed<EditableTableRow>(() => {
    const value = props.config.defaultRow;

    return value && typeof value === 'object' && !Array.isArray(value)
      ? (value as EditableTableRow)
      : {};
  });
  const value = computed<EditableTableValue>(() => {
    const value = props.binding?.value;

    if (props.config.keyed === true) {
      return value && typeof value === 'object' && !Array.isArray(value)
        ? (value as Record<string, EditableTableRow>)
        : {};
    }

    return Array.isArray(value) ? (value as EditableTableRow[]) : [];
  });
  const addRowLabel = computed(() =>
    typeof props.config.addRowLabel === 'string'
      ? props.config.addRowLabel
      : undefined
  );
  const columnsFrom = computed(() =>
    typeof props.config.columnsFrom === 'string'
      ? props.config.columnsFrom
      : undefined
  );

  function updateValue(event: Event): void {
    emit(
      'update:value',
      (event.target as HTMLElementTagNameMap['craft-editable-table']).value
    );
  }
</script>

<template>
  <craft-editable-table
    v-bind="hostProperties"
    :data-editable-table="binding?.name"
    .value="value"
    .columns="columns"
    .defaultRow="defaultRow"
    :add-row-label="addRowLabel"
    :keyed="config.keyed === true"
    :include-row-id="config.includeRowId === true"
    :defines-columns="config.definesColumns === true"
    :columns-from="columnsFrom"
    :readonly="binding?.readOnly ?? false"
    @input="updateValue"
  ></craft-editable-table>
</template>
