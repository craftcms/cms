<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';
  import {useAssetBridge} from '@/composables/useAssetBridge';

  defineProps<{
    contentHtml?: string;
  }>();

  useAssetBridge();
</script>

<template>
  <AppLayout title="Test Page">
    <DynamicHtmlRenderer v-if="contentHtml" :html="contentHtml" />
  </AppLayout>
</template>

<style scoped lang="scss"></style>
