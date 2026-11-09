<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {usePage} from '@inertiajs/vue3';
  import {computed} from 'vue';
  import type {Plugin, PluginStoreContext} from '../types';

  const {plugin, labelSrOnly = false} = defineProps<{
    plugin: Plugin;
    /** Hides the label visually, for when a parent already shows one. */
    labelSrOnly?: boolean;
  }>();
  const page = usePage<{context: PluginStoreContext}>();

  const command = computed(() => {
    const {craftExecutable, composerExecutable} = page.props.context;

    return `${composerExecutable} require "${plugin.packageName}:^${plugin.version}" -w && ${craftExecutable} plugin/install ${plugin.handle}`;
  });
</script>

<template>
  <craft-input-copy
    :label="t('Installation Instructions')"
    :label-sr-only="labelSrOnly || undefined"
    readonly
    monospace
    :value="command"
  ></craft-input-copy>
</template>
