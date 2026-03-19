<script setup lang="ts">
  import {useAssetBridge} from '@/composables/useAssetBridge';
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';
  import AppLayout from '@/layout/AppLayout.vue';
  import {router} from '@inertiajs/vue3';
  import {store} from '@/actions/CraftCms/Cms/Http/Controllers/Settings/EntryTypesController';

  defineProps<{
    content?: string;
    sidebar?: string;
    details?: string;
    bodyHtml?: string;
    headHtml?: string;
    title?: string;
  }>();

  useAssetBridge();

  function handleSubmit(event: SubmitEvent): void {
    const form = event.target as HTMLFormElement;
    const formData = new FormData(form);
    router.post(store(), formData);
  }
</script>

<template>
  <AppLayout
    :title="title"
    class="cp--bridged"
    :full-page-form="true"
    @submit="handleSubmit"
  >
    <template #sidebar v-if="sidebar">
      <DynamicHtmlRenderer :html="sidebar" />
    </template>
    <DynamicHtmlRenderer v-if="content" :html="content" />
    <template #details v-if="details">
      <DynamicHtmlRenderer v-if="details" :html="details" />
    </template>
  </AppLayout>
</template>

<style scoped lang="scss">
  .l-legacy {
    display: grid;
    grid-template-columns: auto 1fr auto;
  }
</style>
