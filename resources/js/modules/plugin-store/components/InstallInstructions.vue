<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import InstallCommands from './InstallCommands.vue';
  import MetaStat from './MetaStat.vue';
  import type {Plugin} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();
</script>

<template>
  <MetaStat :label="t('Installation Instructions')">
    <InstallCommands :plugin="plugin" />
    <p class="install-instructions__help">
      {{
        t(
          'To install this plugin with composer, copy the command above to your terminal.'
        )
      }}
    </p>
  </MetaStat>
</template>

<style scoped>
  .install-instructions__help {
    margin-block: var(--c-spacing-sm) 0;
    margin-inline: 0;
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
    font-weight: normal;
  }
</style>
