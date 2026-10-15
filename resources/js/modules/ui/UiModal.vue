<script setup lang="ts">
  import {actionClient, t} from '@craftcms/ui';
  import {nextTick, onMounted, shallowRef, useTemplateRef} from 'vue';
  import ModalForm from '@/common/components/ModalForm.vue';
  import {firstFocusableWithin} from '@/common/utils/dom';
  import UiRenderer from './UiRenderer.vue';
  import {visitControls} from './runtime';
  import type {UiPayload, UiValues} from './types';

  export interface UiModalResponse {
    ui: UiPayload;
    title?: string | null;
    submitLabel?: string | null;
  }

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

  const modal = shallowRef<UiModalResponse | null>(null);
  const errors = shallowRef<UiPayload['errors']>([]);
  const loading = shallowRef(false);
  const renderer = useTemplateRef<InstanceType<typeof UiRenderer>>('renderer');
  const body = useTemplateRef<HTMLElement>('body');

  onMounted(async () => {
    try {
      const {data} = await actionClient.get<UiModalResponse>(props.modalUrl, {
        params: props.params,
      });
      errors.value = data.ui.errors ?? [];
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
      const values = {...renderer.value.currentValues(), ...props.params};
      const {data} =
        props.actionMethod === 'delete'
          ? await actionClient.delete<Record<string, unknown>>(
              props.actionUrl,
              {data: values}
            )
          : await actionClient.post<Record<string, unknown>>(
              props.actionUrl,
              values
            );

      if (typeof data?.message === 'string') {
        Craft.cp?.displayNotice?.(data.message);
      }

      emit('submitted', data ?? {});
    } catch (error: any) {
      const responseErrors: Record<string, string | string[]> =
        error?.response?.data?.errors ?? {};
      const controlPaths: string[][] = [];
      visitControls(modal.value?.ui.nodes ?? [], (control) =>
        controlPaths.push(control.path)
      );
      controlPaths.sort((a, b) => b.length - a.length);
      errors.value = Object.entries(responseErrors).map(([path, messages]) => {
        const segments = path.split('.');

        return {
          path:
            controlPaths.find((candidate) =>
              candidate.every((part, index) => segments[index] === part)
            ) ?? segments,
          messages: Array.isArray(messages) ? messages : [messages],
        };
      });
      Craft.cp?.displayError?.(
        error?.response?.data?.message ?? t('A server error occurred.')
      );

      await nextTick();
      const invalid = body.value?.querySelector<HTMLElement>(
        '[aria-invalid="true"]'
      );
      if (invalid) {
        firstFocusableWithin(invalid)?.focus();
      }
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
    <craft-field-group v-if="modal" ref="body">
      <UiRenderer ref="renderer" :payload="modal.ui" :errors="errors" />
    </craft-field-group>
  </ModalForm>
</template>
