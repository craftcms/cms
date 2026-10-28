<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import PluginIndex from '@/modules/plugin-store/components/PluginIndex.vue';
  import StoreContainer from '@/modules/plugin-store/components/StoreContainer.vue';
  import {usePluginStoreLayout} from '@/modules/plugin-store/composables/usePluginStoreLayout';

  const props = defineProps<{query: string}>();

  usePluginStoreLayout({
    title: () =>
      props.query
        ? t('Showing results for “{searchQuery}”', {searchQuery: props.query})
        : null,
  });
</script>

<template>
  <StoreContainer>
    <PluginIndex v-if="query" :filter="{searchQuery: query}" />
    <craft-empty v-else icon="magnifying-glass" :label="t('Search plugins')">
    </craft-empty>
  </StoreContainer>
</template>
