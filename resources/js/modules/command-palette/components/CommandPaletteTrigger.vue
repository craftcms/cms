<script setup lang="ts">
  /**
   * Opens the command palette: a labelled Search button on large screens, and
   * an icon-only one with a tooltip where there's less room.
   */
  import {ButtonVariant} from '@craftcms/ui';
  import {t} from '@craftcms/ui/utilities/translate';
  import {cpBreakpoints} from '@/common/composables/useCpBreakpoints';
  import {openCommandPalette} from '@/modules/command-palette/registry';

  const isLarge = cpBreakpoints.greaterOrEqual('lg');

  /** The palette opens with Cmd+K on Apple platforms and Ctrl+K elsewhere. */
  const shortcut = /Mac|iPhone|iPad/.test(navigator.platform)
    ? 'Meta+K'
    : 'Control+K';
</script>

<template>
  <craft-button
    v-if="isLarge"
    :variant="ButtonVariant.Outline"
    type="button"
    size="small"
    icon="search"
    shortcut="K"
    class="command-palette-trigger"
    aria-haspopup="dialog"
    :aria-keyshortcuts="shortcut"
    @click="openCommandPalette"
  >
    {{ t('Search') }}
  </craft-button>
  <template v-else>
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
      {{ t('Search') }}
      <craft-shortcut
        class="command-palette-trigger__shortcut"
        aria-hidden="true"
        >K</craft-shortcut
      >
    </craft-tooltip>
  </template>
</template>

<style scoped>
  .command-palette-trigger__shortcut {
    margin-inline-start: var(--c-spacing-sm);
  }
</style>
