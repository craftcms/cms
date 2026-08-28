<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import {h} from 'vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {useEditableTable} from '@/modules/admin-table/composables/useEditableTable';

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
    onChange: (data) => {
      if (!Array.isArray(data)) {
        throw new Error('Preview targets must remain an ordered list.');
      }
      emit('update:modelValue', data);
    },
    columns: ({columnHelper}) => [
      columnHelper.text('label', {
        header: t('Label'),
        disabled: () => props.disabled,
      }),
      columnHelper.text('urlFormat', {
        header: t('URL Format'),
        class: 'cp:font-mono cp:text-xs',
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
              class: 'cp:flex cp:justify-end cp:gap-2',
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
  <craft-pane padding="0" appearance="raised">
    <AdminTable :table="table" :reorderable="false" />
  </craft-pane>
  <div
    v-if="!disabled"
    class="cp:border cp:border-dashed cp:border-neutral-quiet cp:rounded-bl-md cp:rounded-br-md cp:border-t-0 cp:p-1 cp:pt-2 cp:-mt-1"
  >
    <craft-button
      type="button"
      size="small"
      @click="addPreviewTarget"
      class="cp:w-full"
      variant="plain"
    >
      {{ t('Add a target') }}
    </craft-button>
  </div>
</template>

<style scoped lang="scss"></style>
