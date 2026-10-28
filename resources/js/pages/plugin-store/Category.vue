<script setup lang="ts">
  import {computed} from 'vue';
  import PluginIndex from '@/modules/plugin-store/components/PluginIndex.vue';
  import StoreContainer from '@/modules/plugin-store/components/StoreContainer.vue';
  import {useCoreData} from '@/modules/plugin-store/composables/usePluginStoreData';
  import {usePluginStoreLayout} from '@/modules/plugin-store/composables/usePluginStoreLayout';

  const props = defineProps<{categoryId: number}>();

  const {categories} = useCoreData();
  const category = computed(() =>
    categories.value.find((c) => Number(c.id) === props.categoryId)
  );

  usePluginStoreLayout({title: () => category.value?.title});
</script>

<template>
  <StoreContainer>
    <PluginIndex :filter="{categoryId}" />
  </StoreContainer>
</template>
