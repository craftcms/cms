<script setup lang="ts">
  /**
   * A modal holding a server-built Form. The Form is loaded from `modalUrl`,
   * and its values are posted to `actionUrl`, each with `params` alongside —
   * the record the modal is about, say. Validation errors land on the controls
   * they name.
   */
  import {actionClient, t} from '@craftcms/ui';
  import {onMounted, ref, useTemplateRef} from 'vue';
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

  const modal = ref<FormModalResponse | null>(null);
  const errors = ref<FormPayload['errors']>([]);
  const loading = ref(false);
  const renderer =
    useTemplateRef<InstanceType<typeof FormRenderer>>('renderer');

  onMounted(async () => {
    try {
      const {data} = await actionClient.get<FormModalResponse>(props.modalUrl, {
        params: props.params,
      });
      modal.value = data;
    } catch (error: any) {
      Craft.cp?.displayError?.(error?.response?.data?.message);
      emit('close');
    }
  });

  async function submit(): Promise<void> {
    if (!renderer.value || loading.value) {
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
      const responseErrors: Record<string, string[]> =
        error?.response?.data?.errors ?? {};
      errors.value = Object.entries(responseErrors).map(([path, messages]) => ({
        path: path.split('.'),
        messages,
      }));
      Craft.cp?.displayError?.(error?.response?.data?.message);
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
