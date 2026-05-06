<script setup lang="ts">
  import AuthBase from '@/layout/AuthBase.vue';
  import LoginForm from '@/components/Auth/LoginForm.vue';

  defineProps<{
    logo?: string;
    errors?: Record<string, string[]>;
    authFormData?: Record<string, string>;
  }>();
</script>

<template>
  <AuthBase>
    <LoginForm :show-password-reset="true" :show-remember-checkbox="true" />
  </AuthBase>
</template>

<style scoped lang="scss"></style>
