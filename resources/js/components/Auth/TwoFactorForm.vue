<script setup lang="ts">
  import VarDump from '@/components/VarDump.vue';
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';

  defineProps<{
    authFormData: any;
  }>();
</script>

<template>
  <VarDump :data="authFormData" />
  <DynamicHtmlRenderer :html="authFormData.authForm"/>
</template>

<style scoped lang="scss"></style>
