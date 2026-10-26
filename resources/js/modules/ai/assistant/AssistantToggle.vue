<script setup lang="ts">
  /** The header button that opens and folds the AI assistant’s panel. */
  import {ButtonVariant, t} from '@craftcms/ui';
  import {ASSISTANT_TOGGLE_ID, useAssistantPanel} from './assistant';

  const panel = useAssistantPanel();
</script>

<template>
  <craft-button
    :id="ASSISTANT_TOGGLE_ID"
    icon
    :variant="ButtonVariant.Plain"
    type="button"
    size="small"
    :aria-expanded="panel.isOpen.value ? 'true' : 'false'"
    @click="panel.toggle"
  >
    <craft-icon name="sparkles" :label="t('Ask AI')"></craft-icon>
  </craft-button>
</template>
