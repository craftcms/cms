<script setup lang="ts">
  import {actionClient, t} from '@craftcms/ui';
  import {onMounted, shallowRef, useTemplateRef} from 'vue';
  import ModalForm from '@/common/components/ModalForm.vue';
  import FormRenderer from './FormRenderer.vue';
  import type {FormPayload, FormValues} from './types';

  export interface FormModalResponse {
    form: FormPayload;
    title?: string | null;
    submitLabel?: string | null;
  }

  const props = withDefaults(
    defineProps<{
      modalUrl: string;
      actionUrl: string;
      params?: FormValues;
      /** Used when the server doesn't send a title. */
      title?: string;
      /** Used when the server doesn't send a submit label. */
      submitLabel?: string;
      width?: string;
    }>(),
    {
      params: () => ({}),
      title: undefined,
      submitLabel: () => t('Save'),
      width: 'md',
    }
  );

  const emit = defineEmits<{
    (event: 'close'): void;
    (event: 'submitted', data: Record<string, unknown>): void;
  }>();

  const modal = shallowRef<FormModalResponse | null>(null);
  const errors = shallowRef<FormPayload['errors']>([]);
  const loading = shallowRef(false);
  const renderer =
    useTemplateRef<InstanceType<typeof FormRenderer>>('renderer');

  onMounted(async () => {
    try {
      const {data} = await actionClient.get<FormModalResponse>(props.modalUrl, {
        params: props.params,
      });
      errors.value = data.form.errors ?? [];
      modal.value = data;
    } catch (error: any) {
      Craft.cp?.displayError?.(
        error?.response?.data?.message ?? t('A server error occurred.')
      );
      emit('close');
    }
  });

  async function submit(): Promise<void> {
    if (!renderer.value || loading.value || !renderer.value.canSubmit()) {
      return;
    }

    loading.value = true;
    errors.value = [];

    try {
      const {data} = await actionClient.post<Record<string, unknown>>(
        props.actionUrl,
        {...renderer.value.currentValues(), ...props.params}
      );

      if (typeof data?.message === 'string') {
        Craft.cp?.displayNotice?.(data.message);
      }

      emit('submitted', data ?? {});
    } catch (error: any) {
      const responseErrors: Record<string, string | string[]> =
        error?.response?.data?.errors ?? {};
      errors.value = Object.entries(responseErrors).map(([path, messages]) => ({
        path: path.split('.'),
        messages: Array.isArray(messages) ? messages : [messages],
      }));
      Craft.cp?.displayError?.(
        error?.response?.data?.message ?? t('A server error occurred.')
      );
    } finally {
      loading.value = false;
    }
  }
</script>

<template>
  <ModalForm
    :is-active="modal !== null"
    :title="modal?.title ?? title"
    :submit-label="modal?.submitLabel ?? submitLabel"
    :loading="loading"
    :width="width"
    @close="emit('close')"
    @submit="submit"
  >
    <FormRenderer
      v-if="modal"
      ref="renderer"
      :payload="modal.form"
      :errors="errors"
    />
  </ModalForm>
</template>
