<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {ref} from 'vue';
  import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
  import type {ScreenPageProps} from '@/common/composables/screen';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {openSlideoutWith} from '@/common/slideouts';
  import type {BaseOption} from '@/common/types';
  import type {SystemMessageData} from '@/modules/utilities/types/utilities';
  import SystemMessageEditor from './SystemMessageEditor.vue';

  const props = defineProps<{
    messages: Array<Omit<SystemMessageData, 'language'>>;
    locales: Array<BaseOption>;
    primaryLanguage: string;
  }>();

  useAppLayout({contentMaxWidth: true});

  const localMessages = ref([...props.messages]);

  function openEditor(
    message: Omit<SystemMessageData, 'language'>,
    event: MouseEvent
  ) {
    // SAFETY: The slideout host renders this imported Vue SFC exactly like its
    // Inertia page components; it does not require an Inertia page module.
    const editor = SystemMessageEditor as InertiaPageComponent;
    // SAFETY: The triggers came from the server as JSON, so they are plain data
    // even though their type allows a `renderOption` callback.
    const editedMessage = {
      ...message,
      language: props.primaryLanguage,
    } as unknown as ScreenPageProps;

    openSlideoutWith(
      editor,
      {
        message: editedMessage,
        locales: props.locales.map(({label, value}) => ({label, value})),
      },
      {
        opener: event.currentTarget as HTMLElement,
        onSaved: ({data}) => {
          // The list shows the primary language's copy.
          if (data?.language !== props.primaryLanguage) {
            return;
          }

          const saved = localMessages.value.find((m) => m.key === message.key);

          if (saved) {
            saved.subject = String(data.subject);
            saved.body = String(data.body);
          }
        },
      }
    );
  }
</script>

<template>
  <div id="messages" class="grid gap-6">
    <section v-for="message in localMessages" :key="message.key">
      <h2 class="text-lg mb-2">{{ message.heading }}</h2>
      <craft-card>
        <span slot="label" class="font-medium">{{ message.subject }}</span>

        <craft-button
          slot="actions"
          type="button"
          icon
          size="small"
          @click="openEditor(message, $event)"
        >
          <craft-icon
            name="pencil"
            :label="
              t('Edit “{name}”', {name: message.heading.replace(/:$/, '')})
            "
          ></craft-icon>
        </craft-button>

        <div
          class="system-message-body font-mono text-xs"
          v-text="message.body"
        ></div>
      </craft-card>
    </section>
  </div>
</template>

<style scoped>
  .system-message-body {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
  }
</style>
