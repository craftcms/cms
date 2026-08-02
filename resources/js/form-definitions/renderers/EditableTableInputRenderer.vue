<script setup lang="ts">
  import {computed, inject, useAttrs} from 'vue';
  import type {
    EditableTableColumn,
    EditableTableRow,
    EditableTableValue,
  } from '@craftcms/ui';
  import '@craftcms/ui/components/editable-table/editable-table';
  import {editableTableCoordinationScope} from '../editable-table-columns';

  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    sourceName?: string;
    columns?: EditableTableColumn[];
    defaultRow?: EditableTableRow;
    keyed?: boolean;
    addRowLabel?: string;
    includeRowId?: boolean;
    definesColumns?: boolean;
    columnsFrom?: string;
    modelValue?: unknown;
    readonly?: boolean;
  }>();

  const emit = defineEmits<{
    'update:modelValue': [value: EditableTableValue];
  }>();
  const coordinationScope = inject(editableTableCoordinationScope);

  if (!coordinationScope) {
    throw new Error('Editable Table renderer requires a Form Definition.');
  }

  const attrs = useAttrs();
  const value = computed<EditableTableValue>(() => {
    const value = props.modelValue;

    if (props.keyed === true) {
      return value && typeof value === 'object' && !Array.isArray(value)
        ? (value as Record<string, EditableTableRow>)
        : {};
    }

    return Array.isArray(value) ? (value as EditableTableRow[]) : [];
  });

  function updateValue(event: Event): void {
    emit(
      'update:modelValue',
      (event.target as HTMLElementTagNameMap['craft-editable-table']).value
    );
  }
</script>

<template>
  <craft-editable-table
    v-bind="{...attrs, coordinationScope, sourceName}"
    :data-editable-table="sourceName"
    .value="value"
    .columns="columns ?? []"
    .defaultRow="defaultRow ?? {}"
    .addRowLabel="addRowLabel"
    .keyed="keyed === true"
    .includeRowId="includeRowId === true"
    .definesColumns="definesColumns === true"
    .columnsFrom="columnsFrom"
    :readonly="readonly ?? false"
    @input="updateValue"
  ></craft-editable-table>
</template>
