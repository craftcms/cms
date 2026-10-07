<script setup lang="ts">
  import {actionClient, t} from '@craftcms/ui';
  import {onMounted, shallowRef, useTemplateRef} from 'vue';
  import ModalForm from '@/common/components/ModalForm.vue';
  import UiRenderer from './UiRenderer.vue';
  import type {UiPayload, UiValues} from './types';

  export interface UiModalResponse {
    form: UiPayload;
    title?: string | null;
    submitLabel?: string | null;
  }

  const props = withDefaults(
    defineProps<{
      modalUrl: string;
      actionUrl: string;
      params?: UiValues;
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

  const modal = shallowRef<UiModalResponse | null>(null);
  const errors = shallowRef<UiPayload['errors']>([]);
  const loading = shallowRef(false);
  const renderer = useTemplateRef<InstanceType<typeof UiRenderer>>('renderer');

  onMounted(async () => {
    try {
      const {data} = await actionClient.get<UiModalResponse>(props.modalUrl, {
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
    <UiRenderer
      v-if="modal"
      ref="renderer"
      :payload="modal.form"
      :errors="errors"
    />
  </ModalForm>
</template>
