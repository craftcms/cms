<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {ref} from 'vue';
  import SystemMessageEditModal from './SystemMessageEditModal.vue';
  import type {SelectOption} from '@/common/types';
  import type {SystemMessageData} from '@/modules/utilities/types/utilities';

  const props = defineProps<{
    messages: Array<SystemMessageData>;
    locales: Array<SelectOption>;
    isMultiSite: boolean;
    primaryLanguage: string;
  }>();

  const localMessages = ref<Array<SystemMessageData>>([...props.messages]);
  const isModalOpen = ref(false);
  const editingMessage = ref<SystemMessageData | null>(null);
  function openEditModal(message: SystemMessageData) {
    editingMessage.value = message;
    isModalOpen.value = true;
  }

  function closeModal() {
    isModalOpen.value = false;
    editingMessage.value = null;
  }

  function handleSave(data: {subject: string; body: string; language: string}) {
    // Only update the list if saving in the primary language
    if (editingMessage.value && data.language === props.primaryLanguage) {
      const index = localMessages.value.findIndex(
        (m) => m.key === editingMessage.value?.key
      );
      const message = localMessages.value[index];
      if (message) {
        message.subject = data.subject;
        message.body = data.body;
      }
    }
  }

  function formatBody(body: string): string {
    return body.replace(/\n/g, '<br>');
  }
</script>

<template>
  <div id="messages" class="cp:p-4">
    <div v-for="message in localMessages" :key="message.key" class="cp:mb-6">
      <h2 class="cp:text-lg cp:mb-2">{{ message.heading }}</h2>
      <craft-pane appearance="outline">
        <div slot="title" class="cp:font-medium">
          {{ message.subject }}
        </div>

        <craft-button
          slot="header-actions"
          type="button"
          icon
          size="small"
          @click="openEditModal(message)"
        >
          <craft-icon name="pencil" :label="t('Edit message')"></craft-icon>
        </craft-button>

        <div
          class="cp:font-mono cp:text-xs"
          v-html="formatBody(message.body)"
        ></div>
      </craft-pane>
    </div>
  </div>

  <SystemMessageEditModal
    v-if="editingMessage"
    :is-active="isModalOpen"
    :message="editingMessage"
    :locales="locales"
    :is-multi-site="isMultiSite"
    :initial-language="primaryLanguage"
    @close="closeModal"
    @save="handleSave"
  />
</template>

<style scoped lang="scss">
  .message {
    padding: 1rem;
  }
</style>
