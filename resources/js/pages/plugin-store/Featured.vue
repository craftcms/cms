<script setup lang="ts">
  import {onScopeDispose, shallowRef} from 'vue';
  import PluginIndex from '@/modules/plugin-store/components/PluginIndex.vue';
  import StoreContainer from '@/modules/plugin-store/components/StoreContainer.vue';
  import {getFeaturedSection} from '@/modules/plugin-store/api';
  import {usePluginStoreLayout} from '@/modules/plugin-store/composables/usePluginStoreLayout';
  import type {FeaturedSection} from '@/modules/plugin-store/types';

  const props = defineProps<{handle: string}>();

  const section = shallowRef<FeaturedSection | null>(null);
  const controller = new AbortController();
  onScopeDispose(() => controller.abort());

  getFeaturedSection(props.handle, controller.signal)
    .then((response) => (section.value = response))
    .catch(() => {});

  usePluginStoreLayout({title: () => section.value?.title});
</script>

<template>
  <StoreContainer>
    <PluginIndex :filter="{featuredSectionHandle: handle}" :sortable="false" />
  </StoreContainer>
</template>
