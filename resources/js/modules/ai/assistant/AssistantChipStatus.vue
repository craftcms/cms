<script setup lang="ts">
  /**
   * A context item’s status, in its chip’s `status` slot, as element chips
   * show it: a dot in the status’s color, or the draft icon.
   */
  import {t} from '@craftcms/ui';
  import type {AssistantContextStatus} from './context';

  defineProps<{status: AssistantContextStatus}>();
</script>

<template>
  <div slot="status">
    <craft-icon
      v-if="status.draft"
      name="scribble"
      :label="`${t('Status:')} ${status.label}`"
    ></craft-icon>
    <craft-indicator
      v-else
      :fill="status.fill"
      :label="`${t('Status:')} ${status.label}`"
    ></craft-indicator>
  </div>
</template>
