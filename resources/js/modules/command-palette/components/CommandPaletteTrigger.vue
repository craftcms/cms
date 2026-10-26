<script setup lang="ts">
  /** Opens the command palette from an icon-only Search button. */
  import {ButtonVariant} from '@craftcms/ui';
  import {t} from '@craftcms/ui/utilities/translate';
  import {openCommandPalette} from '@/modules/command-palette/registry';

  /** The palette opens with Cmd+K on Apple platforms and Ctrl+K elsewhere. */
  const shortcut = /Mac|iPhone|iPad/.test(navigator.platform)
    ? 'Meta+K'
    : 'Control+K';
</script>

<template>
  <craft-button
    id="command-palette-button"
    icon
    :variant="ButtonVariant.Plain"
    type="button"
    size="small"
    aria-haspopup="dialog"
    :aria-keyshortcuts="shortcut"
    @click="openCommandPalette"
  >
    <craft-icon name="search"></craft-icon>
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
