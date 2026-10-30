<script setup lang="ts">
  /** Opens the command palette from an icon-only Search button. */
  import {ButtonVariant} from '@craftcms/ui';
  import {t} from '@craftcms/ui/utilities/translate';
  import {openCommandPalette} from '@/modules/command-palette/registry';
  import {commandShortcut} from '@/common/utils/keyShortcuts';

  /** The palette opens with Cmd+K on Apple platforms and Ctrl+K elsewhere. */
  const shortcut = commandShortcut('K');
</script>

<template>
  <craft-button
    id="command-palette-button"
    class="command-palette-trigger"
    icon="search"
    :variant="ButtonVariant.Plain"
    type="button"
    size="small"
    aria-haspopup="dialog"
    :aria-keyshortcuts="shortcut"
    @click="openCommandPalette"
  >
  </craft-button>
  <!-- Labels the icon-only button rather than describing it, so the
    name isn't announced twice. The keys are left out of the name;
    `aria-keyshortcuts` announces them. -->
  <craft-tooltip for="command-palette-button" invoker-relation="label">
    <div class="flex gap-md items-center">
      {{ t('Search') }}
      <craft-shortcut aria-hidden="true">K</craft-shortcut>
    </div>
  </craft-tooltip>
</template>

<style scoped>
  /* Matches the notification bell beside it. */
  .command-palette-trigger {
    --c-icon-size: 1em;
  }
</style>
