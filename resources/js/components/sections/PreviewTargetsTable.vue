<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {h} from 'vue';
  import Pane from '@/components/Pane.vue';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {TableSpacing} from '@/types';
  import DeleteButton from '@/components/AdminTable/DeleteButton.vue';
  import {useEditableTable} from '@/composables/useEditableTable';

  type PreviewTarget = {label: string; urlFormat: string; refresh: boolean};

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<PreviewTarget>): void;
  }>();
  const props = withDefaults(
    defineProps<{
      modelValue: Array<PreviewTarget>;
      name?: string;
      disabled?: boolean;
    }>(),
    {name: 'previewTargets', disabled: false}
  );

  const {table} = useEditableTable<PreviewTarget>({
    data: () => props.modelValue,
    name: props.name,
    onChange: (data) => emit('update:modelValue', data as Array<PreviewTarget>),
    columns: ({columnHelper}) => [
      columnHelper.text('label', {
        header: t('Label'),
        disabled: () => props.disabled,
      }),
      columnHelper.text('urlFormat', {
        header: t('URL Format'),
        class: 'font-mono text-xs',
        disabled: () => props.disabled,
      }),
      columnHelper.lightswitch('refresh', {
        header: t('Auto-Refresh'),
        disabled: () => props.disabled,
      }),
      columnHelper.display({
        id: 'actions',
        header: t('Actions'),
        meta: {
          headerSrOnly: true,
        },
        cell: ({row}) =>
          h(
            'div',
            {
              class: 'flex justify-end gap-2',
            },
            [
              h(DeleteButton, {
                disabled: props.disabled,
                onClick: () => {
                  const newValue = [...props.modelValue];
                  newValue.splice(row.index, 1);
                  emit('update:modelValue', newValue);
                },
              }),
            ]
          ),
      }),
    ],
  });

  function addPreviewTarget() {
    emit('update:modelValue', [
      ...props.modelValue,
      {
        label: '',
        urlFormat: '',
        refresh: true,
      },
    ]);
  }
</script>

<template>
  <Pane :padding="0" appearance="raised">
    <AdminTable
      :table="table"
      :spacing="TableSpacing.Relaxed"
      :reorderable="false"
    />
  </Pane>
  <div
    v-if="!disabled"
    class="border border-dashed border-border-quiet rounded-bl-md rounded-br-md border-t-0 p-1 pt-2 -mt-1"
  >
    <craft-button
      type="button"
      size="small"
      @click="addPreviewTarget"
      class="w-full"
      appearance="plain"
    >
      {{ t('Add a target') }}
    </craft-button>
  </div>
</template>

<style scoped lang="scss"></style>
