<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import type {PluginInfo} from '@/types/plugins';
  import PluginsList from '@/components/Plugins/PluginsList.vue';

  const props = withDefaults(
    defineProps<{
      pluginInfo: Record<string, PluginInfo>;
      readOnly?: boolean;
    }>(),
    {pluginInfo: () => ({}), readOnly: false}
  );
</script>

<template>
  <AppLayout>
    <PluginsList :read-only="readOnly" :plugin-info="pluginInfo" />
  </AppLayout>
</template>

<style scoped lang="scss"></style>
