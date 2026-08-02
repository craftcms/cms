<script setup lang="ts">
  import {onMounted, useTemplateRef} from 'vue';
  import {useEventListener} from '@vueuse/core';
  import type CraftEditableTable from '@/modules/editable-table/editable-table.ce';
  import type {EditableTableColumns} from '@/modules/editable-table/types';
  import '@/modules/editable-table';

  defineOptions({inheritAttrs: false});

  type RowValue = Record<string, unknown>;
  type Value = RowValue[] | Record<string, RowValue>;
  type Column = {
    key: string;
    label: string;
    type: string;
    width?: string | number;
    options?: Array<{label: string; value: unknown}>;
  };

  const props = withDefaults(
    defineProps<{
      name?: string;
      modelValue?: Value;
      tableHtml: string;
      sourceName?: string;
      columns?: Column[];
      definesColumns?: boolean;
      columnsFrom?: string;
    }>(),
    {name: '', columns: () => [], definesColumns: false}
  );
  const emit = defineEmits<{'update:modelValue': [value: Value]}>();
  const container = useTemplateRef<HTMLElement>('container');
  const columnsEvent = 'craft:editable-table-columns-changed';

  function table(): CraftEditableTable | null {
    return (
      container.value?.querySelector<CraftEditableTable>(
        'craft-editable-table'
      ) ?? null
    );
  }

  function updateValue(): void {
    const value = table()?.serialize();

    if (!value) {
      return;
    }

    emit('update:modelValue', value);

    publishColumns(value);
  }

  function publishColumns(value: Value): void {
    if (props.definesColumns) {
      window.dispatchEvent(
        new CustomEvent(columnsEvent, {
          detail: {
            scope: container.value?.closest('[data-form-root]'),
            name: props.sourceName ?? props.name,
            columns: publishedColumns(value),
          },
        })
      );
    }
  }

  function publishedColumns(value: Value): EditableTableColumns {
    return Object.fromEntries(
      Object.entries(value).map(([key, row]) => {
        const type = text(row.type);

        return [
          key,
          {
            heading: text(row.heading) || text(row.handle) || key,
            type:
              type === 'heading' || type === 'singleline' ? 'singleline' : type,
            ...(text(row.width) ? {width: text(row.width)} : {}),
            ...(Array.isArray(row.options) ? {options: row.options} : {}),
            ...(type === 'heading' ? {class: 'heading'} : {}),
          },
        ];
      })
    );
  }

  function text(value: unknown): string {
    if (typeof value === 'string' || typeof value === 'number') {
      return String(value);
    }

    return '';
  }

  function receiveColumns(event: CustomEvent): void {
    const detail = event.detail as {
      scope: Element | null;
      name: string;
      columns: EditableTableColumns;
    };

    if (
      !props.columnsFrom ||
      detail.name !== props.columnsFrom ||
      detail.scope !== container.value?.closest('[data-form-root]')
    ) {
      return;
    }

    table()?.setColumns(detail.columns);
    updateValue();
  }

  useEventListener(
    container,
    ['input', 'change', 'addRow', 'deleteRow', 'sortChange'],
    updateValue
  );
  useEventListener(window, columnsEvent, receiveColumns as EventListener);

  onMounted(() => {
    table()?.setName(props.name);

    if (props.definesColumns) {
      const value = props.modelValue ?? table()?.serialize();

      if (value) {
        publishColumns(value);
      }
    }
  });
</script>

<template>
  <div ref="container" v-bind="$attrs" role="group" v-html="tableHtml"></div>
</template>
