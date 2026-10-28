<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import MetaStat from './MetaStat.vue';
  import type {Plugin} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();

  const modes = computed(() => {
    const require = `composer require "${plugin.packageName}:^${plugin.version}" -w`;

    return [
      {
        label: 'Shell',
        command: `${require} && php craft plugin/install ${plugin.handle}`,
      },
      {
        label: 'DDEV',
        command: `ddev ${require} && ddev craft plugin/install ${plugin.handle}`,
      },
    ];
  });
</script>

<template>
  <MetaStat :label="t('Installation Instructions')">
    <craft-tabs size="small" :label="t('Installation Instructions')">
      <template v-for="mode in modes" :key="mode.label">
        <craft-tab slot="tab">{{ mode.label }}</craft-tab>
        <div slot="panel" class="install-instructions__panel">
          <craft-input-copy
            :label="`${mode.label} ${t('Installation Instructions')}`"
            label-sr-only
            readonly
            class="font-mono"
            :value="mode.command"
          ></craft-input-copy>
        </div>
      </template>
    </craft-tabs>
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
  .install-instructions__panel {
    padding-block-start: var(--c-spacing-sm);
  }

  .install-instructions__help {
    margin-block: var(--c-spacing-sm) 0;
    margin-inline: 0;
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
    font-weight: normal;
  }
</style>
