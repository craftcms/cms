<script setup lang="ts">
  import AuthBase from '@/layout/AuthBase.vue';
  import LoginOrchestrator from '@/components/Auth/LoginOrchestrator.vue';
</script>

<template>
  <AuthBase>
    <LoginOrchestrator context="page" />
  </AuthBase>
</template>

<style scoped lang="scss"></style>
