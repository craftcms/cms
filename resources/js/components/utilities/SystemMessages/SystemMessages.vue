<script setup lang="ts">
  import {ref} from 'vue';
  import SystemMessageEditModal from './SystemMessageEditModal.vue';
  import Pane from '@/components/Pane.vue';

  interface SystemMessageData {
    key: string;
    heading: string;
    subject: string;
    body: string;
  }

  interface LocaleOption {
    id: string;
    displayName: string;
  }

  const props = defineProps<{
    messages: Array<SystemMessageData>;
    locales: Array<LocaleOption>;
    isMultiSite: boolean;
    primaryLanguage: string;
  }>();

  console.log(props);

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

  function handleSave(data: {subject: string; body: string}) {
    if (editingMessage.value) {
      const index = localMessages.value.findIndex(
        (m) => m.key === editingMessage.value?.key
      );
      if (index !== -1) {
        localMessages.value[index] = {
          ...localMessages.value[index],
          subject: data.subject,
          body: data.body,
        };
      }
    }
    closeModal();
  }

  function formatBody(body: string): string {
    return body.replace(/\n/g, '<br>');
  }
</script>

<template>
  <div id="messages" class="p-4">
    <div v-for="message in localMessages" :key="message.key" class="mb-6">
      <h2 class="text-lg mb-2">{{ message.heading }}</h2>
      <Pane appearance="outline" variant="code">
        <template #title>
          <div class="font-medium">
            {{ message.subject }}
          </div>
        </template>

        <template #header-actions>
          <craft-button
            type="button"
            icon
            size="small"
            @click="openEditModal(message)"
          >
            <craft-icon name="pencil"></craft-icon>
          </craft-button>
        </template>

        <div class="font-mono text-xs" v-html="formatBody(message.body)"></div>
      </Pane>
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
