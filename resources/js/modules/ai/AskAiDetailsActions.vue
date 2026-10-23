<script setup lang="ts">
  /** The “Ask AI” panel's header actions: starting a new conversation. */
  import {t} from '@craftcms/ui';
  import {inject} from 'vue';
  import {AskAiEditorKey} from './ask-ai';

  // The panel's own props come through as well; none of them belong here.
  defineOptions({inheritAttrs: false});

  const editor = inject(AskAiEditorKey, null);
</script>

<template>
  <craft-button
    v-if="editor?.conversation.value.length"
    type="button"
    icon="plus"
    variant="plain"
    size="small"
    @click="editor.conversation.value = []"
  >
    {{ t('New conversation') }}
  </craft-button>
</template>
