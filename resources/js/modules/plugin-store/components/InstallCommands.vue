<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {usePage} from '@inertiajs/vue3';
  import {computed} from 'vue';
  import type {Plugin, PluginStoreContext} from '../types';

  const {plugin} = defineProps<{plugin: Plugin}>();
  const page = usePage<{context: PluginStoreContext}>();

  const command = computed(() => {
    const {craftExecutable, composerExecutable} = page.props.context;

    return `${composerExecutable} require "${plugin.packageName}:^${plugin.version}" -w && ${craftExecutable} plugin/install ${plugin.handle}`;
  });
</script>

<template>
  <craft-input-copy
    :label="t('Installation Instructions')"
    label-sr-only
    readonly
    class="font-mono"
    :value="command"
  ></craft-input-copy>
</template>
