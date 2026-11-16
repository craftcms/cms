<script setup lang="ts">
  /**
   * The buttons plugins add for the folder on screen, from
   * `FolderActionsResolving`. Each runs its own server-driven action.
   */
  import {ButtonVariant} from '@craftcms/ui';
  import type {ActionItemButton} from '@/common/types';

  defineProps<{
    actions: ActionItemButton[];
  }>();
</script>

<template>
  <!-- Fill unless they say otherwise, like “Upload files” beside them. -->
  <craft-button
    v-for="action in actions"
    :key="action.label"
    type="button"
    :variant="action.variant ?? ButtonVariant.Fill"
    :icon="action.icon"
    .action="action.action"
    .feedback="action.feedback"
    .disabled="action.disabled ?? false"
  >
    {{ action.label }}
  </craft-button>
</template>
