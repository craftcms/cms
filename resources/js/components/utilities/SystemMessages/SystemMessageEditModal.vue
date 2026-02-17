<script setup lang="ts">
  import {ref, watch} from 'vue';
  import {t} from '@craftcms/cp';
  import {useForm} from '@inertiajs/vue3';
  import {useEventListener} from '@vueuse/core';
  import ModalForm from '@/components/ModalForm.vue';
  import {useActionClient} from '@/composables/useFetch';
  import {useAnnouncer} from '@/composables/useAnnouncer';
  import {store} from '@actions/Utilities/SystemMessagesController';

  interface SystemMessageData {
    key: string;
    heading: string;
    subject: string;
    body: string;
  }

  interface LocaleOption {
    value: string;
    label: string;
  }

  const props = defineProps<{
    isActive: boolean;
    message: SystemMessageData;
    locales: Array<LocaleOption>;
    isMultiSite: boolean;
    initialLanguage: string;
  }>();

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', data: {subject: string; body: string; language: string}): void;
  }>();

  const {announce} = useAnnouncer();

  const currentLanguage = ref(props.initialLanguage);

  // Form for saving message
  const form = useForm({
    key: props.message.key,
    language: props.initialLanguage,
    subject: props.message.subject,
    body: props.message.body,
  });

  // Fetch message data for a specific language
  const {execute: fetchMessage, isLoading: isLoadingMessage} = useActionClient<{
    message: SystemMessageData;
  }>('system-messages/get-message-modal', {
    immediate: false,
    onSuccess: (data) => {
      if (data.message) {
        form.subject = data.message.subject;
        form.body = data.message.body;
      }
    },
  });

  // Watch for language changes to fetch new message data
  watch(currentLanguage, async (newLanguage, oldLanguage) => {
    if (newLanguage !== oldLanguage && props.isActive) {
      form.language = newLanguage;
      await fetchMessage({
        key: props.message.key,
        language: newLanguage,
      });
    }
  });

  // Reset form when modal opens with new message
  watch(
    () => props.message,
    (newMessage) => {
      form.key = newMessage.key;
      form.subject = newMessage.subject;
      form.body = newMessage.body;
      form.language = props.initialLanguage;
      currentLanguage.value = props.initialLanguage;
    }
  );

  function handleSubmit() {
    if (!form.subject.trim() || !form.body.trim()) {
      return;
    }

    form.post(store().url, {
      preserveScroll: true,
      onSuccess: () => {
        announce(t('Message saved.'));
        emit('save', {
          subject: form.subject,
          body: form.body,
          language: form.language,
        });
      },
      onError: () => {
        announce(t("Couldn't save message."));
      },
    });
  }

  function handleLanguageChange(event: Event) {
    const target = event.target as HTMLSelectElement;
    currentLanguage.value = target.value;
  }

  // Handle Cmd/Ctrl + Enter to submit
  useEventListener('keydown', (event) => {
    if (
      props.isActive &&
      (event.metaKey || event.ctrlKey) &&
      event.key === 'Enter'
    ) {
      event.preventDefault();
      handleSubmit();
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
    width="4xl"
  >
    <template #header-actions>
      <div class="flex items-center gap-2">
        <craft-spinner :visible="isLoadingMessage" style="--size: 1rem" />
        <craft-select :label="t('Locale')" v-if="isMultiSite" label-sr-only>
          <select
            :value="currentLanguage"
            @change="handleLanguageChange"
            slot="input"
          >
            <option
              v-for="locale in locales"
              :key="locale.value"
              :value="locale.value"
              :selected="currentLanguage === locale.value"
            >
              {{ locale.label }}
            </option>
          </select>
        </craft-select>
      </div>
    </template>
    <div class="grid gap-3">
      <craft-input
        :label="t('Subject')"
        :help-text="t('Evaluated as a twig template, then parsed as markdown.')"
        v-model="form.subject"
        maxlength="1000"
        required
        :disabled="isLoadingMessage"
      >
      </craft-input>
      <craft-textarea
        :label="t('Body')"
        :help-text="t('Evaluated as a twig template, then parsed as markdown.')"
        v-model="form.body"
        monospace
        required
        :disabled="isLoadingMessage"
        max-rows="25"
      >
      </craft-textarea>
    </div>
  </ModalForm>
</template>

<style scoped lang="scss"></style>
