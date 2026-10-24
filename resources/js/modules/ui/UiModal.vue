<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {onMounted, useTemplateRef} from 'vue';
  import ModalForm from '@/common/components/ModalForm.vue';
  import UiRenderer from './UiRenderer.vue';
  import {useUiForm, type UiFormResponse} from './useUiForm';
  import type {UiValues} from './types';

  export type UiModalResponse = UiFormResponse;

  const props = withDefaults(
    defineProps<{
      modalUrl: string;
      actionUrl: string;
      actionMethod?: 'post' | 'delete';
      params?: UiValues;
      /** Used when the server doesn't send a title. */
      title?: string;
      /** Used when the server doesn't send a submit label. */
      submitLabel?: string;
      width?: string;
    }>(),
    {
      params: () => ({}),
      actionMethod: 'post',
      title: undefined,
      submitLabel: () => t('Save'),
      width: 'md',
    }
  );

  const emit = defineEmits<{
    (event: 'close'): void;
    (event: 'submitted', data: Record<string, unknown>): void;
  }>();

  const {
    form: modal,
    errors,
    loading,
    load,
    submit: submitForm,
  } = useUiForm({
    modalUrl: () => props.modalUrl,
    actionUrl: () => props.actionUrl,
    actionMethod: () => props.actionMethod,
    params: () => props.params,
  });
  const renderer = useTemplateRef<InstanceType<typeof UiRenderer>>('renderer');
  const body = useTemplateRef<HTMLElement>('body');

  onMounted(async () => {
    if (!(await load())) {
      emit('close');
    }
  });

  async function submit(): Promise<void> {
    const data = await submitForm(renderer.value, body.value);

    if (data === null) {
      return;
    }

    if (typeof data.message === 'string') {
      Craft.cp?.displayNotice?.(data.message);
    }

    emit('submitted', data);
  }
</script>

<template>
  <ModalForm
    :is-active="modal !== null"
    :title="modal?.title ?? title"
    :submit-label="modal?.submitLabel ?? submitLabel"
    :loading="loading"
    :submit-disabled="modal?.submitDisabled"
    :width="width"
    @close="emit('close')"
    @submit="submit"
  >
    <craft-field-group v-if="modal" ref="body">
      <UiRenderer ref="renderer" :payload="modal.ui" :errors="errors" />
    </craft-field-group>
  </ModalForm>
</template>
