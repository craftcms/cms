<script setup lang="ts">
  import {computed, ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import {useHttp} from '@inertiajs/vue3';
  import {useEventListener} from '@vueuse/core';
  import ModalForm from '@/common/components/ModalForm.vue';
  import SystemMessagesController, {
    store,
  } from '@actions/Utilities/SystemMessagesController';
  import type {SelectOption} from '@/common/types';
  import {useFlashMessages} from '@/common/composables/useFlashMessages';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftTextarea from '@craftcms/ui/vue/CraftTextarea.vue';

  interface SystemMessageData {
    key: string;
    heading: string;
    subject: string;
    body: string;
    language: string;
  }

  const props = defineProps<{
    isActive: boolean;
    message: SystemMessageData;
    locales: Array<SelectOption>;
    isMultiSite: boolean;
    initialLanguage: string;
  }>();

  const emit = defineEmits<{
    (e: 'close'): void;
    (
      e: 'save',
      data: Pick<SystemMessageData, 'subject' | 'body' | 'language'>
    ): void;
  }>();

  const {flash} = useFlashMessages();
  const feedback = ref<{
    icon?: string;
    variant: string;
    message: string;
  } | null>(null);

  const messageForm = useHttp<{language: string}, {message: SystemMessageData}>(
    {
      language: props.initialLanguage,
    }
  );

  const isLoadingMessage = computed(() => messageForm.processing);

  // Form for saving message
  const form = useHttp<
    Pick<SystemMessageData, 'key' | 'subject' | 'body' | 'language'>,
    Pick<SystemMessageData, 'subject' | 'body' | 'language'>
  >({
    key: props.message.key,
    language: props.initialLanguage,
    subject: props.message.subject,
    body: props.message.body,
  });

  // Fetch message data for a specific language
  function fetchMessage() {
    messageForm.get(
      SystemMessagesController.show({key: props.message.key}).url,
      {
        onSuccess: ({message}) => {
          if (message) {
            form.language = message.language;
            form.subject = message.subject;
            form.body = message.body;
          }
        },
      }
    );
  }

  // Reset form when modal opens with new message
  watch(
    () => props.message,
    (newMessage) => {
      form.key = newMessage.key;
      form.subject = newMessage.subject;
      form.body = newMessage.body;
      form.language = props.initialLanguage;
    }
  );

  function save({closeOnSuccess = true}: {closeOnSuccess?: boolean} = {}) {
    if (!form.subject.trim() || !form.body.trim()) {
      return;
    }

    feedback.value = null;
    form.post(store().url, {
      onHttpException: () => {
        feedback.value = {
          icon: 'triangle-exclamation',
          message: t('Failed to save message.'),
          variant: 'danger',
        };
      },
      onSuccess: (data) => {
        emit('save', {
          subject: data.subject,
          body: data.body,
          language: data.language,
        });

        if (closeOnSuccess) {
          flash('success', t('Message saved.'), {duration: -1});
          emit('close');
        } else {
          feedback.value = {
            icon: 'circle-check',
            message: t('Message saved'),
            variant: 'success',
          };
        }
      },
      onError: () => {
        feedback.value = {
          icon: 'triangle-exclamation',
          message: t('Failed to save'),
          variant: 'danger',
        };
      },
    });
  }

  function handleSubmit() {
    save();
  }

  // Handle Cmd/Ctrl + Enter to submit
  useEventListener('keydown', (event) => {
    if (!props.isActive) {
      return;
    }
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
      event.preventDefault();
      handleSubmit();
    }

    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save({closeOnSuccess: false});
    }
  });
</script>

<template>
  <ModalForm
    :is-active="isActive"
    :title="t('Edit Message')"
    :loading="form.processing"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <template #header-actions>
      <div class="flex items-center gap-2">
        <craft-spinner :visible="messageForm.processing" style="--size: 1rem" />
        <Select
          v-model="messageForm.language"
          :options="locales"
          @change="fetchMessage"
        />
      </div>
    </template>
    <div class="grid gap-3 w-4xl">
      <CraftInput
        :label="t('Subject')"
        :help-text="t('Evaluated as a twig template, then parsed as markdown.')"
        v-model="form.subject"
        class="w-full"
        maxlength="1000"
        required
        :disabled="isLoadingMessage"
      />
      <CraftTextarea
        :label="t('Body')"
        :help-text="t('Evaluated as a twig template, then parsed as markdown.')"
        v-model="form.body"
        class="w-full"
        monospace
        required
        :disabled="messageForm.processing"
        max-rows="25"
      />
    </div>

    <template #feedback v-if="feedback">
      <craft-callout
        :variant="feedback.variant"
        :icon="feedback.icon"
        appearance="plain"
        inline
        class="p-0"
        >{{ feedback.message }}</craft-callout
      >
    </template>
  </ModalForm>
</template>

<style scoped lang="scss"></style>
