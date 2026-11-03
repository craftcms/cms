<script setup lang="ts">
  import PluginCard from './PluginCard.vue';
  import type {PluginSummary} from '../types';

  const {plugins, trial = false} = defineProps<{
    plugins: readonly PluginSummary[];
    trial?: boolean;
  }>();
</script>

<template>
  <ul class="plugin-grid">
    <li v-for="plugin in plugins" :key="plugin.id">
      <PluginCard :plugin="plugin" :trial="trial" />
    </li>
  </ul>
</template>

<style scoped>
  .plugin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr));
    gap: var(--c-spacing-lg);
    margin-block: var(--c-spacing-lg) 0;
    margin-inline: 0;
    padding: 0;
    list-style: none;
  }
</style>
