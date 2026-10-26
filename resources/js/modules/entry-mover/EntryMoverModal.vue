<script setup lang="ts">
  /**
   * Picks the section to move entries to, from those that accept every
   * selected entry's type. The Vue counterpart of the legacy `EntryMover`.
   */
  import {actionClient, isHttpError, t} from '@craftcms/ui';
  import {shallowRef, watch} from 'vue';
  import {move, showModal} from '@actions/Entries/MoveEntryToSectionController';
  import ModalForm from '@/common/components/ModalForm.vue';
  import {useMessages} from '@/modules/messages/useMessages';
  import type {EntryMoveRequest} from './useEntryMover';

  interface MoveTargetSection {
    id: number;
    label: string;
  }

  const props = defineProps<{
    request: EntryMoveRequest | null;
  }>();

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'moved'): void;
  }>();

  const messages = useMessages();
  const sections = shallowRef<MoveTargetSection[] | null>(null);
  const sectionId = shallowRef<string | null>(null);
  const moving = shallowRef(false);
  let loading: AbortController | null = null;

  function errorMessage(error: unknown): string {
    return isHttpError<{message?: string}>(error)
      ? (error.response?.data?.message ?? t('A server error occurred.'))
      : t('A server error occurred.');
  }

  watch(
    () => props.request,
    async (request) => {
      loading?.abort();
      sections.value = null;
      sectionId.value = null;

      if (!request) return;

      const controller = new AbortController();
      loading = controller;

      try {
        const {data} = await actionClient.post<{
          sections?: MoveTargetSection[];
        }>(showModal.url(), request, {signal: controller.signal});
        sections.value = data.sections ?? [];
      } catch (error) {
        if (controller.signal.aborted) return;
        messages.error(errorMessage(error));
        emit('close');
      }
    },
    {immediate: true}
  );

  async function submit(): Promise<void> {
    if (!props.request || !sectionId.value || moving.value) return;

    moving.value = true;

    try {
      const {data} = await actionClient.post<{message?: string}>(move.url(), {
        entryIds: props.request.entryIds,
        sectionId: Number(sectionId.value),
      });
      messages.success(data.message ?? t('Done'));
      emit('moved');
    } catch (error) {
      messages.error(errorMessage(error));
    } finally {
      moving.value = false;
    }
  }
</script>

<template>
  <ModalForm
    :is-active="request !== null"
    :title="t('Move to')"
    :submit-label="t('Move')"
    :submit-disabled="!sectionId"
    :loading="moving"
    @close="emit('close')"
    @submit="submit"
  >
    <div v-if="sections === null" class="flex justify-center p-lg">
      <craft-spinner :label="t('Loading')"></craft-spinner>
    </div>
    <craft-empty
      v-else-if="!sections.length"
      :label="
        t(
          'Couldn’t find any sections that all selected elements could be moved to.'
        )
      "
    ></craft-empty>
    <craft-radio-group
      v-else
      :label="t('Section')"
      .modelValue="sectionId"
      @model-value-changed="sectionId = $event.target.modelValue || null"
    >
      <craft-radio
        v-for="section in sections"
        :key="section.id"
        :label="section.label"
        .choiceValue="String(section.id)"
        .checked="sectionId === String(section.id)"
      ></craft-radio>
    </craft-radio-group>
  </ModalForm>
</template>
