<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
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
  <craft-tabs size="small" :label="t('Installation Instructions')">
    <template v-for="mode in modes" :key="mode.label">
      <craft-tab slot="tab">{{ mode.label }}</craft-tab>
      <div slot="panel" class="install-commands__panel">
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
</template>

<style scoped>
  .install-commands__panel {
    padding-block-start: var(--c-spacing-sm);
  }
</style>
