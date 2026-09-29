<script setup lang="ts">
  /**
   * Confirms a Table node row deletion with a server-built Form, for deletions
   * that need more than a yes/no — where to move a deleted record's data, say.
   * The Form is loaded from `modalUrl`, and its values are posted to
   * `deleteUrl` alongside the row's `id`.
   */
  import {actionClient, t} from '@craftcms/ui';
  import {onMounted, ref, useTemplateRef} from 'vue';
  import ModalForm from '@/common/components/ModalForm.vue';
  import FormRenderer from './FormRenderer.vue';
  import type {FormPayload} from './types';

  interface DeleteModalResponse {
    form: FormPayload;
    title?: string | null;
    submitLabel?: string | null;
  }

  const props = defineProps<{
    modalUrl: string;
    deleteUrl: string;
    rowId: string | number;
  }>();

  const emit = defineEmits<{
    (event: 'close'): void;
    (event: 'deleted'): void;
  }>();

  const modal = ref<DeleteModalResponse | null>(null);
  const errors = ref<FormPayload['errors']>([]);
  const loading = ref(false);
  const renderer =
    useTemplateRef<InstanceType<typeof FormRenderer>>('renderer');

  onMounted(async () => {
    try {
      const {data} = await actionClient.get<DeleteModalResponse>(
        props.modalUrl,
        {params: {id: props.rowId}}
      );
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
      const {data} = await actionClient.post<{message?: string}>(
        props.deleteUrl,
        {...renderer.value.currentValues(), id: props.rowId}
      );

      if (data?.message) {
        Craft.cp?.displayNotice?.(data.message);
      }

      emit('deleted');
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
    :title="modal?.title ?? t('Delete')"
    :submit-label="modal?.submitLabel ?? t('Delete')"
    :loading="loading"
    width="sm"
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
