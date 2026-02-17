<script setup lang="ts">
  import {computed, ref, watch} from 'vue';
  import {t} from '@craftcms/cp';
  import ModalForm from '@/components/ModalForm.vue';
  import {useActionClient} from '@/composables/useFetch';
  import {useAnnouncer} from '@/composables/useAnnouncer';

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
    (e: 'save', data: {subject: string; body: string}): void;
  }>();

  const {announce} = useAnnouncer();

  const currentLanguage = ref(props.initialLanguage);
  const subject = ref(props.message.subject);
  const body = ref(props.message.body);
  const isSaving = ref(false);
  const isLoadingMessage = ref(false);

  // Fetch message data for a specific language
  const {
    execute: fetchMessage,
    data: fetchedMessage,
    state: fetchState,
  } = useActionClient<{message: SystemMessageData}>(
    'system-messages/get-message-modal',
    {
      immediate: false,
      onSuccess: (data) => {
        if (data.message) {
          subject.value = data.message.subject;
          body.value = data.message.body;
        }
        isLoadingMessage.value = false;
      },
      onError: () => {
        isLoadingMessage.value = false;
      },
    }
  );

  // Save message
  const {execute: saveMessage, state: saveState} = useActionClient(
    'system-messages/save-message',
    {
      immediate: false,
      onSuccess: () => {
        announce(t('Message saved.'));
        emit('save', {
          subject: subject.value,
          body: body.value,
        });
        isSaving.value = false;
      },
      onError: () => {
        announce(t("Couldn't save message."));
        isSaving.value = false;
      },
    }
  );

  // Watch for language changes to fetch new message data
  watch(currentLanguage, async (newLanguage, oldLanguage) => {
    if (newLanguage !== oldLanguage && props.isActive) {
      isLoadingMessage.value = true;
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
      subject.value = newMessage.subject;
      body.value = newMessage.body;
      currentLanguage.value = props.initialLanguage;
    }
  );

  async function handleSubmit() {
    if (!subject.value.trim() || !body.value.trim()) {
      return;
    }

    isSaving.value = true;
    await saveMessage({
      key: props.message.key,
      language: currentLanguage.value,
      subject: subject.value,
      body: body.value,
    });
  }

  function handleLanguageChange(event: Event) {
    const target = event.target as HTMLSelectElement;
    currentLanguage.value = target.value;
  }

  const isLoading = computed(() => isSaving.value || isLoadingMessage.value);
</script>

<template>
  <ModalForm
    :is-active="isActive"
    :title="t('Edit Message')"
    :loading="isLoading"
    @close="emit('close')"
    @submit="handleSubmit"
    width="4xl"
  >
    <div class="grid gap-3">
      <craft-select :label="t('Locale')" v-if="isMultiSite">
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
      <craft-input
        :label="t('Subject')"
        v-model="subject"
        maxlength="1000"
        required
        :disabled="isLoadingMessage"
      >
      </craft-input>
      <craft-textarea
        :label="t('Body')"
        v-model="body"
        required
        :disabled="isLoadingMessage"
        max-rows="25"
      >
      </craft-textarea>
    </div>
  </ModalForm>
</template>

<style scoped lang="scss"></style>
